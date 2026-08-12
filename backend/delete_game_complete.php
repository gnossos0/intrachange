<?php
// delete_game_complete.php - Completely delete a game and all associated data

// Start output buffering to prevent any stray output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['game_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing game_id']);
    exit;
}

$game_id = (int)$input['game_id'];

try {
    // Start transaction
    $conn->autocommit(false);
    
    // Get game info before deletion for logging
    $game_stmt = $conn->prepare("
        SELECT id, white_player_id, black_player_id, status, game_mode, created_at
        FROM games 
        WHERE id = ?
    ");
    $game_stmt->bind_param("i", $game_id);
    $game_stmt->execute();
    $game_result_obj = $game_stmt->get_result();
    $game = $game_result_obj->fetch_assoc();
    
    if (!$game) {
        echo json_encode(['status' => 'error', 'message' => 'Game not found']);
        $conn->rollback();
        exit;
    }
    
    // Count moves for logging
    $moves_stmt = $conn->prepare("SELECT COUNT(*) as count FROM moves WHERE game_id = ?");
    $moves_stmt->bind_param("i", $game_id);
    $moves_stmt->execute();
    $moves_result = $moves_stmt->get_result();
    $move_count = $moves_result->fetch_assoc()['count'];
    
    // Delete all moves associated with this game
    $delete_moves_stmt = $conn->prepare("DELETE FROM moves WHERE game_id = ?");
    $delete_moves_stmt->bind_param("i", $game_id);
    
    if (!$delete_moves_stmt->execute()) {
        throw new Exception("Failed to delete moves: " . $delete_moves_stmt->error);
    }
    
    $deleted_moves = $conn->affected_rows;
    
    // Delete the game from games table
    $delete_game_stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
    $delete_game_stmt->bind_param("i", $game_id);
    
    if (!$delete_game_stmt->execute()) {
        throw new Exception("Failed to delete game: " . $delete_game_stmt->error);
    }
    
    // Check if game was actually deleted
    if ($conn->affected_rows == 0) {
        throw new Exception("Game not found or already deleted");
    }
    
    // Also remove from archive table if it exists there
    $delete_archive_stmt = $conn->prepare("DELETE FROM game_archives WHERE game_id = ?");
    $delete_archive_stmt->bind_param("i", $game_id);
    $delete_archive_stmt->execute(); // Don't fail if this doesn't exist
    $deleted_archives = $conn->affected_rows;
    
    // Commit transaction
    $conn->commit();
    
    // Create summary message
    $summary = "Game #{$game_id} completely deleted";
    if ($deleted_moves > 0) {
        $summary .= " (including {$deleted_moves} moves)";
    }
    if ($deleted_archives > 0) {
        $summary .= " and removed from archives";
    }
    
    // Log the deletion
    error_log("Game deletion: Game #{$game_id} deleted - {$game['game_mode']} game with {$move_count} moves, status: {$game['status']}");
    
    echo json_encode([
        'status' => 'ok',
        'message' => $summary,
        'deleted_data' => [
            'game_id' => $game_id,
            'moves_deleted' => $deleted_moves,
            'archive_deleted' => $deleted_archives > 0,
            'game_info' => [
                'mode' => $game['game_mode'],
                'status' => $game['status'],
                'move_count' => $move_count,
                'created_at' => $game['created_at']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Delete game error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to delete game: ' . $e->getMessage()
    ]);
} finally {
    $conn->autocommit(true);
}

// Clean output buffer and send
ob_end_flush();