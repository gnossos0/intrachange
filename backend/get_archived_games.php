<?php
// get_archived_games.php - Retrieve archived games

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Only GET method allowed']);
    exit;
}

try {
    // Get query parameters
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $include_test = isset($_GET['include_test']) ? (bool)$_GET['include_test'] : false;
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if ($user_id) {
        $where_conditions[] = "(white_player_id = ? OR black_player_id = ?)";
        $params[] = $user_id;
        $params[] = $user_id;
    }
    
    if ($game_id) {
        $where_conditions[] = "game_id = ?";
        $params[] = $game_id;
    }
    
    if (!$include_test) {
        $where_conditions[] = "test_mode = FALSE";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get archived games with player names
    $sql = "
        SELECT 
            ga.id as archive_id,
            ga.game_id,
            ga.white_player_id,
            ga.black_player_id,
            wp.username as white_player_name,
            bp.username as black_player_name,
            ga.game_result,
            ga.end_reason,
            ga.move_count,
            ga.game_summary,
            ga.start_timestamp,
            ga.end_timestamp,
            ga.archived_at,
            ga.test_mode
        FROM game_archives ga
        LEFT JOIN users wp ON ga.white_player_id = wp.id
        LEFT JOIN users bp ON ga.black_player_id = bp.id
        {$where_clause}
        ORDER BY ga.archived_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM game_archives ga
        {$where_clause}
    ";
    
    $count_params = array_slice($params, 0, -2); // Remove limit and offset
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Format timestamps and add additional info
    foreach ($games as &$game) {
        $game['archived_at_formatted'] = date('Y-m-d H:i:s', strtotime($game['archived_at']));
        $game['end_timestamp_formatted'] = $game['end_timestamp'] ? date('Y-m-d H:i:s', strtotime($game['end_timestamp'])) : null;
        $game['start_timestamp_formatted'] = $game['start_timestamp'] ? date('Y-m-d H:i:s', strtotime($game['start_timestamp'])) : null;
        
        // Add duration if both timestamps exist
        if ($game['start_timestamp'] && $game['end_timestamp']) {
            $start = new DateTime($game['start_timestamp']);
            $end = new DateTime($game['end_timestamp']);
            $duration = $start->diff($end);
            $game['game_duration'] = $duration->format('%h hours, %i minutes');
        }
    }
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $total
    ]);
    
} catch (Exception $e) {
    error_log("Get archived games error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve archived games']);
}
?>
