<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// Check if we're looking up a specific user by username, otherwise use current user
$target_username = isset($_GET['user']) ? $_GET['user'] : $current_username;

try {
    // Get user profile data using username
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.username,
            u.display_name,
            u.full_name,
            u.email,
            u.bio,
            u.signature,
            u.avatar,
            u.created_at,
            (SELECT COUNT(*) FROM games g 
             JOIN users u1 ON g.white_player_id = u1.id 
             JOIN users u2 ON g.black_player_id = u2.id 
             WHERE (u1.username = ? OR u2.username = ?) AND g.status = 'completed') as games_completed,
            (SELECT COUNT(*) FROM games g 
             JOIN users u1 ON g.white_player_id = u1.id 
             JOIN users u2 ON g.black_player_id = u2.id 
             WHERE (u1.username = ? OR u2.username = ?) AND g.status = 'active') as games_active,
            (SELECT COUNT(*) FROM game_archives ga 
             JOIN users u1 ON ga.white_player_id = u1.id 
             JOIN users u2 ON ga.black_player_id = u2.id 
             WHERE u1.username = ? OR u2.username = ?) as games_archived
        FROM users u 
        WHERE u.username = ?
    ");
    
    $stmt->bind_param("sssssss", $target_username, $target_username, $target_username, $target_username, $target_username, $target_username, $target_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Calculate win rate and other stats  
        $win_stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN g.winner_id IS NOT NULL AND u_winner.username = ? THEN 1 
                         ELSE 0 END) as wins,
                COUNT(*) as total_games
            FROM games g
            JOIN users u1 ON g.white_player_id = u1.id 
            JOIN users u2 ON g.black_player_id = u2.id 
            LEFT JOIN users u_winner ON g.winner_id = u_winner.id
            WHERE (u1.username = ? OR u2.username = ?) 
            AND g.status = 'completed'
        ");
        
        $win_stmt->bind_param("sss", $target_username, $target_username, $target_username);
        $win_stmt->execute();
        $win_result = $win_stmt->get_result();
        $stats = $win_result->fetch_assoc();
        
        $row['wins'] = $stats['wins'] ?? 0;
        $row['losses'] = $row['games_completed'] - $row['wins'];
        $row['draws'] = 0; // For now, since we don't track draws separately in this schema
        
        if ($row['games_completed'] > 0) {
            $row['win_rate'] = round(($row['wins'] / $row['games_completed']) * 100, 1);
        } else {
            $row['win_rate'] = 0;
        }
        
        // Ensure avatar URL is properly formatted
        if ($row['avatar'] && !empty($row['avatar'])) {
            // If it doesn't start with http or /, add relative path
            if (!preg_match('/^(https?:\/\/|\/)/', $row['avatar'])) {
                $row['avatar'] = './' . $row['avatar'];
            }
        } else {
            $row['avatar'] = null;
        }
        
        echo json_encode([
            'success' => true,
            'profile' => $row
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>