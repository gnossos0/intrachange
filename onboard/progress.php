<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$next_lesson = $_POST['next_lesson'] ?? '';
$allowed_lessons = ['lesson2', 'lesson3', 'lesson4'];
if (!in_array($next_lesson, $allowed_lessons, true)) {
    http_response_code(422);
    exit('Invalid onboarding lesson');
}

$game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : null;
$user_id = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../backend/db_connect.php';

if ($game_id !== null && $game_id > 0) {
    $stmt = $conn->prepare('UPDATE user_onboarding SET current_lesson = ?, onboarding_game_id = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND completed = FALSE');
    $stmt->bind_param('sii', $next_lesson, $game_id, $user_id);
} else {
    $stmt = $conn->prepare('UPDATE user_onboarding SET current_lesson = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND completed = FALSE');
    $stmt->bind_param('si', $next_lesson, $user_id);
}
$stmt->execute();
$updated = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($updated !== 1) {
    http_response_code(409);
    exit('Onboarding progress could not be updated');
}

header('Location: route.php');
exit();