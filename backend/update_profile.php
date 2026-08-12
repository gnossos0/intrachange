<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Update profile fields
    $updates = [];
    $params = [];
    $types = "";
    
    if (isset($input['bio'])) {
        $bio = trim($input['bio']);
        if (strlen($bio) > 500) {
            throw new Exception('Bio must be 500 characters or less');
        }
        $updates[] = "bio = ?";
        $params[] = $bio;
        $types .= "s";
    }
    
    if (isset($input['signature'])) {
        $signature = trim($input['signature']);
        if (strlen($signature) > 100) {
            throw new Exception('Signature must be 100 characters or less');
        }
        $updates[] = "signature = ?";
        $params[] = $signature;
        $types .= "s";
    }
    
    if (isset($input['full_name'])) {
        $full_name = trim($input['full_name']);
        if (strlen($full_name) > 100) {
            throw new Exception('Full name must be 100 characters or less');
        }
        $updates[] = "full_name = ?";
        $params[] = $full_name;
        $types .= "s";
    }
    
    if (empty($updates)) {
        throw new Exception('No valid fields to update');
    }
    
    // Add updated_at and user_id to the query
    $updates[] = "updated_at = NOW()";
    $params[] = $user_id;
    $types .= "i";
    
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile');
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>