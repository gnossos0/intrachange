<?php
// archive_game.php - Archive a finished game

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Required fields
$required = ['game_id', 'action']; // action: 'archive' or 'delete'
foreach ($required as $field) {
    if (!isset($input[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

$game_id = (int)$input['game_id'];
$action = $input['action'];

if (!in_array($action, ['archive', 'delete'])) {
    echo json_encode(['success' => false, 'error' => 'Action must be "archive" or "delete"']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get game data with moves
    $stmt = $pdo->prepare("
        SELECT g.*, 
               JSON_ARRAYAGG(
                   JSON_OBJECT(
                       'id', m.id,
                       'move_number', m.move_number,
                       'piece_type', m.piece_type,
                       'from_square', m.from_square,
                       'to_square', m.to_square,
                       'chess_coordinates', m.chess_coordinates,
                       'hex_from', m.hex_from,
                       'hex_to', m.hex_to,
                       'keyword_from', m.keyword_from,
                       'keyword_to', m.keyword_to,
                       'user_comment', m.user_comment,
                       'comment_url', m.comment_url,
                       'timestamp', m.created_at
                   )
               ) as moves_data
        FROM games g
        LEFT JOIN moves m ON g.id = m.game_id
        WHERE g.id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        throw new Exception("Game not found");
    }
    
    // Check if already archived or deleted
    if ($game['archived'] || $game['deleted']) {
        $status = $game['archived'] ? 'archived' : 'deleted';
        echo json_encode(['success' => true, 'message' => "Game already $status", 'already_processed' => true]);
        $pdo->commit();
        exit;
    }
    
    if ($action === 'delete') {
        // Mark game as deleted
        $stmt = $pdo->prepare("UPDATE games SET deleted = TRUE WHERE id = ?");
        $stmt->execute([$game_id]);
        
        echo json_encode(['success' => true, 'message' => 'Game deleted successfully']);
        $pdo->commit();
        exit;
    }
    
    // Archive the game
    $moves_json = $game['moves_data'] ? json_decode($game['moves_data'], true) : [];
    
    // Generate game summary
    $move_count = count($moves_json);
    $game_summary = generateGameSummary($game, $moves_json);
    
    // Determine game result from provided data or game state
    $game_result = $input['game_result'] ?? 'DRAW';
    $end_reason = $input['end_reason'] ?? 'manual_archive';
    $board_final_state = $input['board_final_state'] ?? null;
    
    // Insert into archives (idempotent - use ON DUPLICATE KEY UPDATE)
    $stmt = $pdo->prepare("
        INSERT INTO game_archives (
            game_id, white_player_id, black_player_id, game_result, end_reason,
            move_count, game_summary, moves_json, board_final_state,
            start_timestamp, end_timestamp, test_mode
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
            game_result = VALUES(game_result),
            end_reason = VALUES(end_reason),
            end_timestamp = NOW()
    ");
    
    $test_mode = isset($input['test_mode']) ? (bool)$input['test_mode'] : false;
    
    $stmt->execute([
        $game_id,
        $game['white_player_id'],
        $game['black_player_id'],
        $game_result,
        $end_reason,
        $move_count,
        $game_summary,
        json_encode($moves_json),
        $board_final_state ? json_encode($board_final_state) : null,
        $game['created_at'],
        $test_mode
    ]);
    
    // Mark original game as archived
    $stmt = $pdo->prepare("UPDATE games SET archived = TRUE WHERE id = ?");
    $stmt->execute([$game_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Game archived successfully',
        'archive_id' => $pdo->lastInsertId(),
        'game_summary' => $game_summary
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Archive game error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to archive game: ' . $e->getMessage()]);
}

function generateGameSummary($game, $moves) {
    $move_count = count($moves);
    
    if ($move_count === 0) {
        return "Game with no moves";
    }
    
    $final_move = end($moves);
    $piece_name = getPieceName($final_move['piece_type'] ?? '');
    
    // Check if final move was esoteric
    $is_esoteric = strpos($final_move['chess_coordinates'] ?? '', '→') !== false;
    $move_type = $is_esoteric ? 'Esoteric' : 'Exoteric';
    
    // Extract keywords for flavor
    $keywords = [];
    if (!empty($final_move['keywords'])) {
        $keyword_array = json_decode($final_move['keywords'], true);
        if (is_array($keyword_array)) {
            $keywords = array_slice($keyword_array, -2); // Last 2 keywords
        }
    }
    
    $keyword_text = !empty($keywords) ? ' (' . implode(' → ', $keywords) . ')' : '';
    
    return "Game completed after {$move_count} moves. Final move: {$move_type} {$piece_name}{$keyword_text}";
}

function getPieceName($piece_code) {
    $pieces = [
        'P' => 'Pawn', 'R' => 'Rook', 'N' => 'Knight', 
        'B' => 'Bishop', 'Q' => 'Queen', 'K' => 'King'
    ];
    
    $piece_type = substr($piece_code, 1, 1); // Get second character (piece type)
    return $pieces[$piece_type] ?? 'Piece';
}
?>
