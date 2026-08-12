<?php
// get_available_games.php - Get list of non-archived games

// Start output buffering to prevent any stray output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

require_once 'db_connect.php';

try {
    // Get non-archived games with player information and move counts
    $stmt = $conn->prepare("
        SELECT 
            g.id,
            g.white_player_id,
            g.black_player_id,
            g.winner_id,
            g.status,
            g.current_turn,
            g.created_at,
            g.updated_at,
            g.archived,
            g.deleted,
            wp.username as white_player_name,
            bp.username as black_player_name,
            COUNT(m.id) as move_count
        FROM games g
        LEFT JOIN users wp ON g.white_player_id = wp.id
        LEFT JOIN users bp ON g.black_player_id = bp.id
        LEFT JOIN moves m ON g.id = m.game_id
        WHERE g.archived = FALSE AND g.deleted = FALSE
        GROUP BY g.id
        ORDER BY g.updated_at DESC
        LIMIT 20
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = [
            'id' => (int)$row['id'],
            'white_player_id' => (int)$row['white_player_id'],
            'black_player_id' => (int)$row['black_player_id'],
            'winner_id' => $row['winner_id'] ? (int)$row['winner_id'] : null,
            'white_player_name' => $row['white_player_name'] ?: 'Player ' . $row['white_player_id'],
            'black_player_name' => $row['black_player_name'] ?: 'Player ' . $row['black_player_id'],
            'status' => $row['status'],
            'current_turn' => $row['current_turn'],
            'move_count' => (int)$row['move_count'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'total' => count($games)
    ]);
    
} catch (Exception $e) {
    error_log("Get available games error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch games: ' . $e->getMessage(),
        'games' => []
    ]);
}

// Clean output buffer and send
ob_end_flush();