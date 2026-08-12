<?php
// Admin-only move comment editor API
session_start();

require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Admin check
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User not logged in');
    }

    $query = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $user['is_admin'] != 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Admin only']);
        exit();
    }

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
    
    // Update the move with comment data (admin can edit any move)
    $stmt = $conn->prepare("
        UPDATE moves 
        SET user_comment = ?, comment_url = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $user_comment, $comment_url, $move_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            
            // Log admin action for audit trail
            error_log("Admin edit: User $user_id edited move $move_id");
            
            echo json_encode([
                'success' => true,
                'message' => 'Move comment updated successfully'
            ]);
        } else {
            throw new Exception('Move not found');
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