<?php
require_once __DIR__ . '/db_connect.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Mock user for testing
$user_id = $_SESSION['user_id'] ?? 1;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get comments for a specific move or game
        if (isset($_GET['move_id'])) {
            $move_id = (int)$_GET['move_id'];
            
            // Check if comments table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->num_rows == 0) {
                echo json_encode(['success' => true, 'comments' => []]);
                exit();
            }
            
            $query = "SELECT c.*, u.username 
                      FROM comments c 
                      LEFT JOIN users u ON c.user_id = u.id 
                      WHERE c.move_id = ? 
                      ORDER BY c.created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $move_id);
            
        } elseif (isset($_GET['game_id'])) {
            $game_id = (int)$_GET['game_id'];
            
            // Check if necessary tables exist
            $tablesExist = true;
            $requiredTables = ['comments', 'users', 'moves'];
            foreach ($requiredTables as $table) {
                $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
                if ($tableCheck->num_rows == 0) {
                    $tablesExist = false;
                    break;
                }
            }
            
            if (!$tablesExist) {
                echo json_encode(['success' => true, 'comments' => [], 'moves' => []]);
                exit();
            }
            
            $query = "SELECT c.*, u.username, m.move_number, m.chess_coordinates 
                      FROM comments c 
                      LEFT JOIN users u ON c.user_id = u.id 
                      LEFT JOIN moves m ON c.move_id = m.id 
                      WHERE m.game_id = ? 
                      ORDER BY m.move_number ASC, c.created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $game_id);
            
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing move_id or game_id parameter']);
            exit();
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        
        while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => (int)$row['id'],
            'move_id' => (int)$row['move_id'],
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'content' => $row['content'],
            'hex_from' => $row['hex_from'] ? (int)$row['hex_from'] : null,
            'hex_to' => $row['hex_to'] ? (int)$row['hex_to'] : null,
            'keyword_from' => $row['keyword_from'],
            'keyword_to' => $row['keyword_to'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'editable_until' => $row['editable_until'],
            'is_editable' => $row['editable_until'] && new DateTime() < new DateTime($row['editable_until']),
            'move_number' => isset($row['move_number']) ? (int)$row['move_number'] : null,
            'chess_coordinates' => $row['chess_coordinates'] ?? null
        ];
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'comments' => $comments]);

} elseif ($method === 'POST') {
    // Create new comment attached to a move (no move creation here)
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['move_id']) || !isset($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }
    
    $move_id = (int)$data['move_id'];
    $content = trim($data['content']);
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment content cannot be empty']);
        exit();
    }
    
    // Get hex data from request (pre-filled by frontend from move data)
    $hex_from = isset($data['hex_from']) ? (int)$data['hex_from'] : null;
    $hex_to = isset($data['hex_to']) ? (int)$data['hex_to'] : null;
    $keyword_from = isset($data['keyword_from']) ? $data['keyword_from'] : null;
    $keyword_to = isset($data['keyword_to']) ? $data['keyword_to'] : null;
    
    // Set editable window (3 minutes from now)
    $editable_until = date('Y-m-d H:i:s', time() + 180);
    
    $stmt = $conn->prepare("INSERT INTO comments (move_id, user_id, content, hex_from, hex_to, keyword_from, keyword_to, editable_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iisiisss', $move_id, $user_id, $content, $hex_from, $hex_to, $keyword_from, $keyword_to, $editable_until);
    
    if ($stmt->execute()) {
        $comment_id = $conn->insert_id;
        $stmt->close();
        
        // Get user info (with fallback for missing users table)
        $username = 'Player1'; // Default fallback
        try {
            $user_query = $conn->prepare("SELECT username FROM users WHERE id = ?");
            if ($user_query) {
                $user_query->bind_param('i', $user_id);
                $user_query->execute();
                $result = $user_query->get_result();
                if ($result->num_rows > 0) {
                    $username = $result->fetch_assoc()['username'];
                }
                $user_query->close();
            }
        } catch (Exception $e) {
            // Use fallback username if users table doesn't exist
        }
        
        echo json_encode([
            'success' => true,
            'comment' => [
                'id' => $comment_id,
                'move_id' => $move_id,
                'user_id' => $user_id,
                'username' => $username,
                'content' => $content,
                'hex_from' => $hex_from,
                'hex_to' => $hex_to,
                'keyword_from' => $keyword_from,
                'keyword_to' => $keyword_to,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'editable_until' => $editable_until,
                'is_editable' => true
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save comment']);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
