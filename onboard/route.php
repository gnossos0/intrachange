<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?next=' . urlencode('onboard/route.php'));
    exit();
}

require_once __DIR__ . '/../backend/db_connect.php';

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare('SELECT current_lesson, onboarding_game_id, completed FROM user_onboarding WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$onboarding = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Users created before onboarding was introduced have no row and remain in the normal application.
if (!$onboarding || (bool)$onboarding['completed']) {
    header('Location: ../dashboard.php');
    exit();
}

$game_id = (int)$onboarding['onboarding_game_id'];
switch ($onboarding['current_lesson']) {
    case 'lesson1':
        header('Location: pages/lesson1.html');
        break;
    case 'lesson2':
        header('Location: pages/lesson2.php');
        break;
    case 'lesson3':
        if ($game_id > 0) {
            header('Location: pages/lesson3.php?game_id=' . $game_id);
        } else {
            header('Location: pages/lesson2.php');
        }
        break;
    case 'lesson4':
        if ($game_id > 0) {
            header('Location: pages/lesson4.php?game_id=' . $game_id);
        } else {
            header('Location: pages/lesson2.php');
        }
        break;
    default:
        header('Location: ../dashboard.php');
        break;
}
exit();