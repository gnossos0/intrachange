<?php
// Update move with comment data
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error_log.txt');
error_reporting(E_ALL);

require_once __DIR__ . '/db_connect.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$user_id = $_SESSION['user_id'] ?? 1;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $move_id = $data['move_id'] ?? null;
    $user_comment = $data['user_comment'] ?? '';
    $comment_url = $data['comment_url'] ?? '';
    
    if (!$move_id) {
        throw new Exception('Move ID is required');
    }
    
    // Update the move with comment data
    $stmt = $conn->prepare("
        UPDATE moves 
        SET user_comment = ?, comment_url = ? 
        WHERE id = ? AND player_id = ?
    ");
    $stmt->bind_param("ssii", $user_comment, $comment_url, $move_id, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => 'Comment saved successfully'
            ]);
        } else {
            throw new Exception('Move not found or you do not have permission to update it');
        }
    } else {
        throw new Exception('Failed to update move: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
