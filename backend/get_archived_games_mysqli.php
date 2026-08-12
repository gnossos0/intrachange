<?php
// Start output buffering to prevent any stray output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';

try {
    // Get query parameters
    $user_id = $_GET['user_id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 20), 100); // Max 100 results
    $offset = (int)($_GET['offset'] ?? 0);
    $include_test = $_GET['include_test'] ?? false;
    
    // Build query conditions
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($user_id) {
        $conditions[] = "(white_player_id = ? OR black_player_id = ?)";
        $params[] = $user_id;
        $params[] = $user_id;
        $types .= 'ii';
    }
    
    if (!$include_test) {
        $conditions[] = "test_mode = ?";
        $params[] = 0;
        $types .= 'i';
    }
    
    $where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Add limit and offset parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    // Get archived games with user info
    $sql = "
        SELECT 
            a.*,
            w.username as white_username,
            b.username as black_username
        FROM game_archives a
        JOIN users w ON a.white_player_id = w.id
        JOIN users b ON a.black_player_id = b.id
        $where_clause
        ORDER BY a.archived_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $games = [];
    while ($row = $result->fetch_assoc()) {
        // Format the game data
        $games[] = [
            'id' => $row['id'],
            'game_id' => $row['game_id'],
            'game_name' => $row['game_name'],
            'white_player' => [
                'id' => $row['white_player_id'],
                'username' => $row['white_username']
            ],
            'black_player' => [
                'id' => $row['black_player_id'],
                'username' => $row['black_username']
            ],
            'game_result' => $row['game_result'],
            'end_reason' => $row['end_reason'],
            'move_count' => $row['move_count'],
            'game_summary' => $row['game_summary'],
            'start_timestamp' => $row['start_timestamp'],
            'end_timestamp' => $row['end_timestamp'],
            'archived_at' => $row['archived_at'],
            'test_mode' => (bool)$row['test_mode']
        ];
    }
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM game_archives a
        $where_clause
    ";
    
    if ($conditions) {
        // Remove limit/offset params for count query
        $count_params = array_slice($params, 0, -2);
        $count_types = substr($types, 0, -2);
        
        $count_stmt = $conn->prepare($count_sql);
        if ($count_params) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];
    } else {
        $count_result = $conn->query($count_sql);
        $total = $count_result->fetch_assoc()['total'];
    }
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Clean output buffer and send
ob_end_flush();
