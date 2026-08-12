<?php
// validate_game_access.php - Check if user can access a game
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$game_id = $_GET['game_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!$game_id) {
    echo json_encode(['success' => false, 'error' => 'Game ID required']);
    exit;
}

try {
    // Check if game exists and get details
    $stmt = $conn->prepare("
        SELECT g.id, g.white_player_id, g.black_player_id, g.status, g.archived, g.deleted,
               u1.username as white_username, u2.username as black_username,
               COUNT(m.id) as move_count
        FROM games g
        LEFT JOIN users u1 ON g.white_player_id = u1.id
        LEFT JOIN users u2 ON g.black_player_id = u2.id
        LEFT JOIN moves m ON g.id = m.game_id
        WHERE g.id = ?
        GROUP BY g.id
    ");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_assoc();
    
    if (!$game) {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
        exit;
    }
    
    // Determine user's access level
    $is_player = ($game['white_player_id'] == $user_id || $game['black_player_id'] == $user_id);
    $can_move = $is_player && $game['status'] == 'active' && !$game['archived'] && !$game['deleted'];
    $can_view = true; // All users can spectate
    
    $access_type = $can_move ? 'player' : 'spectator';
    $your_role = '';
    if ($game['white_player_id'] == $user_id) {
        $your_role = 'white';
    } elseif ($game['black_player_id'] == $user_id) {
        $your_role = 'black';
    }
    
    echo json_encode([
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'white_username' => $game['white_username'],
            'black_username' => $game['black_username'],
            'status' => $game['status'],
            'move_count' => $game['move_count'],
            'archived' => (bool)$game['archived'],
            'deleted' => (bool)$game['deleted']
        ],
        'access' => [
            'type' => $access_type,
            'can_move' => $can_move,
            'can_view' => $can_view,
            'your_role' => $your_role
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
