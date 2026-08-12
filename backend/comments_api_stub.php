<?php
// Simple comments API stub - prevents 500 errors
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Return empty comments for now
    echo json_encode([
        'success' => true,
        'comments' => []
    ]);
} elseif ($method === 'POST') {
    // Accept comment but don't save it yet
    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => time(),
            'content' => 'Comment saved (stub)',
            'username' => 'Player',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
?>
