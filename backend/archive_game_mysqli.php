<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $game_id = $input['game_id'] ?? null;
    $action = $input['action'] ?? 'archive'; // 'archive' or 'delete'
    $game_result = $input['game_result'] ?? null;
    $end_reason = $input['end_reason'] ?? null;
    $test_mode = $input['test_mode'] ?? false;
    
    if (!$game_id) {
        throw new Exception('Game ID is required');
    }
    
    // Check if game already archived or deleted to prevent duplicates
    $stmt = $conn->prepare("SELECT archived, deleted FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game_status = $result->fetch_assoc();
    
    if (!$game_status) {
        throw new Exception('Game not found');
    }
    
    if ($game_status['archived'] && $action === 'archive') {
        echo json_encode(['success' => true, 'message' => 'Game already archived']);
        exit;
    }
    
    if ($game_status['deleted'] && $action === 'delete') {
        echo json_encode(['success' => true, 'message' => 'Game already deleted']);
        exit;
    }
    
    if ($action === 'archive') {
        // Check if already in archives table
        $stmt = $conn->prepare("SELECT id FROM game_archives WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Game already archived']);
            exit;
        }
        
        // Get complete game data
        $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
        
        if (!$game) {
            throw new Exception('Game not found');
        }
        
        // Get all moves for this game
        $stmt = $conn->prepare("SELECT * FROM moves WHERE game_id = ? ORDER BY move_number");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $moves_result = $stmt->get_result();
        $moves = [];
        while ($move = $moves_result->fetch_assoc()) {
            $moves[] = $move;
        }
        
        $move_count = count($moves);
        
        // Generate game summary
        $white_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $white_stmt->bind_param("i", $game['white_player_id']);
        $white_stmt->execute();
        $white_result = $white_stmt->get_result();
        $white_user = $white_result->fetch_assoc();
        
        $black_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $black_stmt->bind_param("i", $game['black_player_id']);
        $black_stmt->execute();
        $black_result = $black_stmt->get_result();
        $black_user = $black_result->fetch_assoc();
        
        $game_summary = sprintf(
            "Game between %s (White) and %s (Black) - %d moves - Result: %s",
            $white_user['username'] ?? 'Unknown',
            $black_user['username'] ?? 'Unknown',
            $move_count,
            $game_result ?? 'Unknown'
        );
        
        // Archive the game
        $stmt = $conn->prepare("
            INSERT INTO game_archives (
                game_id, white_player_id, black_player_id, game_result, end_reason,
                move_count, game_summary, moves_json, start_timestamp, test_mode
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $moves_json = json_encode($moves);
        $test_mode_int = $test_mode ? 1 : 0;
        
        $stmt->bind_param("iiississsi",
            $game_id,
            $game['white_player_id'],
            $game['black_player_id'],
            $game_result,
            $end_reason,
            $move_count,
            $game_summary,
            $moves_json,
            $game['created_at'],
            $test_mode_int
        );
        $stmt->execute();
        
        // Mark original game as archived
        $stmt = $conn->prepare("UPDATE games SET archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Game archived successfully',
            'game_summary' => $game_summary,
            'move_count' => $move_count
        ]);
        
    } else if ($action === 'delete') {
        // Mark game as deleted (soft delete)
        $stmt = $conn->prepare("UPDATE games SET deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Game marked as deleted'
        ]);
    } else {
        throw new Exception('Invalid action. Use "archive" or "delete"');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
