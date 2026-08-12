<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include __DIR__ . '/backend/db_connect.php';

// Debug: Show POST data
error_log('POST: ' . print_r($_POST, true));

if (!isset($_POST['email'], $_POST['password'])) {
    error_log('Missing email or password');
    die('Email and password required');
}

$email = $_POST['email'];
$password = $_POST['password'];
error_log('Email: ' . $email);

// Prepared statement for security
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    die('Database error');
}
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->error) {
    error_log('MySQLi execute error: ' . $stmt->error);
    die('Database error');
}
$result = $stmt->get_result();
if (!$result) {
    error_log('get_result failed: ' . $stmt->error);
    die('Database error');
}
error_log('Num rows: ' . $result->num_rows);

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    error_log('User found: ' . print_r($row, true));
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $next = isset($_POST['next']) ? $_POST['next'] : '';
        error_log('Login success, next: ' . $next);
        // Allow only relative paths without protocol/host to avoid open redirects
        if (!empty($next) && !preg_match('~^https?://~i', $next) && strpos($next, "\n") === false && strpos($next, "\r") === false) {
            header('Location: ' . $next);
        } else {
            // Default to dashboard after login
            header('Location: dashboard.php');
        }
        exit();
    } else {
        error_log('Incorrect password');
        echo "Incorrect password!";
    }
} else {
    error_log('Email not found');
    echo "Email not found!";
}

$stmt->close();
$conn->close();
?>
