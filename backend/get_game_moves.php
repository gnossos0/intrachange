<?php
// get_game_moves.php - API to retrieve game and move data for resume functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$game_id = $_GET['game_id'] ?? null;

if (!$game_id) {
    echo json_encode(['success' => false, 'error' => 'Game ID required']);
    exit;
}

// Check if this is an archived game - archived games don't require login
$stmt = $conn->prepare("SELECT archived FROM games WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game_check = $result->fetch_assoc();

$is_archived = $game_check && $game_check['archived'] == 1;

// Also check if game exists in game_archives table
if (!$is_archived) {
    $archive_stmt = $conn->prepare("SELECT id FROM game_archives WHERE game_id = ?");
    $archive_stmt->bind_param("i", $game_id);
    $archive_stmt->execute();
    $archive_result = $archive_stmt->get_result();
    $is_archived = $archive_result->num_rows > 0;
    $archive_stmt->close();
}

// For non-archived games, require login
if (!$is_archived && !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    // Get game details with player info
    $game_stmt = $conn->prepare("
        SELECT g.*, 
               u1.username as white_username, u2.username as black_username
        FROM games g
        LEFT JOIN users u1 ON g.white_player_id = u1.id
        LEFT JOIN users u2 ON g.black_player_id = u2.id
        WHERE g.id = ?
    ");
    $game_stmt->bind_param("i", $game_id);
    $game_stmt->execute();
    $game_result = $game_stmt->get_result();
    $game = $game_result->fetch_assoc();

    // If not found in games table, check game_archives
    if (!$game) {
        $archive_game_stmt = $conn->prepare("
            SELECT ga.*, 
                   u1.username as white_username, u2.username as black_username,
                   'completed' as status, 1 as archived
            FROM game_archives ga
            LEFT JOIN users u1 ON ga.white_player_id = u1.id
            LEFT JOIN users u2 ON ga.black_player_id = u2.id
            WHERE ga.game_id = ?
        ");
        $archive_game_stmt->bind_param("i", $game_id);
        $archive_game_stmt->execute();
        $archive_result = $archive_game_stmt->get_result();
        $game = $archive_result->fetch_assoc();
        $archive_game_stmt->close();
    }

    if (!$game) {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
        exit;
    }

    // Get all moves for this game ordered by move number (remove duplicates)
    $moves_stmt = $conn->prepare("
        SELECT m.*, u.username as player_name
        FROM moves m
        LEFT JOIN users u ON m.player_id = u.id
        WHERE m.game_id = ? 
        GROUP BY m.move_number, m.player_id, m.from_position, m.to_position
        ORDER BY m.move_number ASC
    ");
    $moves_stmt->bind_param("i", $game_id);
    $moves_stmt->execute();
    $moves_result = $moves_stmt->get_result();

    $moves = [];
    while ($move = $moves_result->fetch_assoc()) {
        // Parse JSON fields if they exist
        if ($move['from_square']) {
            $move['from_square'] = json_decode($move['from_square'], true);
        }
        if ($move['to_square']) {
            $move['to_square'] = json_decode($move['to_square'], true);
        }
        if ($move['move_data']) {
            $move['move_data'] = json_decode($move['move_data'], true);
        }
        if ($move['esoteric']) {
            $move['esoteric'] = json_decode($move['esoteric'], true);
        }
        
        $moves[] = $move;
    }

    // Determine user's access and role
    $is_player = $user_id && ($game['white_player_id'] == $user_id || $game['black_player_id'] == $user_id);
    $can_move = $is_player && $game['status'] == 'active' && !$game['archived'] && !$game['deleted'];
    
    // Check if user is admin for edit permissions (only if logged in)
    $is_admin = false;
    if ($user_id) {
        $admin_query = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $admin_query->bind_param("i", $user_id);
        $admin_query->execute();
        $admin_result = $admin_query->get_result();
        $user_data = $admin_result->fetch_assoc();
        if ($user_data && $user_data['is_admin'] == 1) {
            $is_admin = true;
        }
        $admin_query->close();
    }
    
    $your_role = '';
    if ($user_id && $game['white_player_id'] == $user_id) {
        $your_role = 'white';
    } elseif ($user_id && $game['black_player_id'] == $user_id) {
        $your_role = 'black';
    }
    
    $is_your_turn = false;
    if ($can_move) {
        $is_your_turn = (($game['current_turn'] == 'white' && $your_role == 'white') || 
                         ($game['current_turn'] == 'black' && $your_role == 'black'));
    }

    // Add player names to game data for easy access
    $game['white_player'] = $game['white_username'];
    $game['black_player'] = $game['black_username'];

    // Return comprehensive game data
    echo json_encode([
        'success' => true,
        'game' => $game,
        'moves' => $moves,
        'user_context' => [
            'user_id' => $user_id,
            'role' => $your_role,
            'is_player' => $is_player,
            'can_move' => $can_move,
            'is_your_turn' => $is_your_turn,
            'is_admin' => $is_admin
        ],
        'stats' => [
            'total_moves' => count($moves),
            'last_move_time' => !empty($moves) ? end($moves)['created_at'] : $game['created_at']
        ]
    ]);

    $moves_stmt->close();
    $game_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
