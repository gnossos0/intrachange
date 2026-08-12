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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['avatar'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed']);
    exit();
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please use JPG, PNG, or WebP.']);
    exit();
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 2MB.']);
    exit();
}

// Create avatars directory if it doesn't exist
$avatars_dir = dirname(__DIR__) . '/assets/avatars';
if (!is_dir($avatars_dir)) {
    if (!mkdir($avatars_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create avatars directory', 'path' => $avatars_dir]);
        exit();
    }
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $avatars_dir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    $error_details = [
        'tmp_name' => $file['tmp_name'],
        'target_path' => $filepath,
        'avatars_dir_exists' => is_dir($avatars_dir),
        'avatars_dir_writable' => is_writable($avatars_dir),
        'tmp_file_exists' => file_exists($file['tmp_name']),
        'last_error' => error_get_last()
    ];
    echo json_encode(['success' => false, 'message' => 'Failed to save file', 'debug' => $error_details]);
    exit();
}

// Resize and crop image to 200x200px square
try {
    $image_info = getimagesize($filepath);
    $mime_type = $image_info['mime'];
    
    // Create image resource from file
    switch ($mime_type) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            throw new Exception('Unsupported image type');
    }
    
    // Get current dimensions
    $width = imagesx($source);
    $height = imagesy($source);
    
    // Calculate crop dimensions (square from center)
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    // Create new 200x200 image
    $resized = imagecreatetruecolor(200, 200);
    
    // Preserve transparency for PNG
    if ($mime_type === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefill($resized, 0, 0, $transparent);
    }
    
    // Copy and resize
    imagecopyresampled($resized, $source, 0, 0, $x, $y, 200, 200, $size, $size);
    
    // Save resized image
    switch ($mime_type) {
        case 'image/jpeg':
            imagejpeg($resized, $filepath, 90);
            break;
        case 'image/png':
            imagepng($resized, $filepath, 8);
            break;
        case 'image/webp':
            imagewebp($resized, $filepath, 90);
            break;
    }
    
    // Clean up memory
    imagedestroy($source);
    imagedestroy($resized);
    
} catch (Exception $e) {
    // If image processing fails, delete the file and return error
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Failed to process image: ' . $e->getMessage()]);
    exit();
}

// Update database with avatar path
$relative_path = 'assets/avatars/' . $filename;

try {
    // Delete old avatar file if it exists
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();
    
    if ($old_data && $old_data['avatar']) {
        $old_file_path = dirname(__DIR__) . '/' . $old_data['avatar'];
        if (file_exists($old_file_path) && strpos($old_data['avatar'], 'avatars/') !== false) {
            unlink($old_file_path);
        }
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $relative_path, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar updated successfully',
            'avatar_url' => $relative_path
        ]);
    } else {
        // Delete uploaded file if database update fails
        unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
    
} catch (Exception $e) {
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>