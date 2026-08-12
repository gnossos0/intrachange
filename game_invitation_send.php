<?php
// game_invitation_send.php - Full invitation system

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// PHPMailer manual includes
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

// Database connection
require_once __DIR__ . '/backend/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: invite_friends.php");
    exit();
}

// Grab POST variables
$game_id = $_POST['game_id'] ?? '';
$invitee_email = trim(strtolower($_POST['email'] ?? ''));
$invitee_name  = trim($_POST['username'] ?? '');
$personal_message = trim($_POST['personal_message'] ?? '');

// Relaxed email validation
if (empty($invitee_email) || !filter_var($invitee_email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Please provide a valid email address.");
}

// Prevent self-invitations by checking if the email belongs to the current user
$self_check_stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id = ?");
$self_check_stmt->bind_param("si", $invitee_email, $_SESSION['user_id']);
$self_check_stmt->execute();
$self_result = $self_check_stmt->get_result();

if ($self_result->num_rows > 0) {
    $self_check_stmt->close();
    $conn->close();
    die("Error: You cannot send an invitation to yourself. Please invite a different player.");
}
$self_check_stmt->close();

// Validate game ID
if (empty($game_id) || !is_numeric($game_id)) {
    die("Error: Valid game ID is required.");
}

// Check game exists and user is authorized (must be the creator - black player)
$game_stmt = $conn->prepare("SELECT id, black_player_id FROM games WHERE id = ? AND black_player_id = ?");
$game_stmt->bind_param("ii", $game_id, $_SESSION['user_id']);
$game_stmt->execute();
$game_result = $game_stmt->get_result();

if ($game_result->num_rows === 0) {
    $game_stmt->close();
    $conn->close();
    die("Error: Game not found or you are not authorized to send invitations for this game.");
}

$game_data = $game_result->fetch_assoc();
$game_stmt->close();

// Game validation passed - continue with invitation

// Generate secure token
$token = bin2hex(random_bytes(16));

// Insert invitation into DB using actual table structure
$stmt = $conn->prepare("
    INSERT INTO game_invitations
    (game_id, sender_id, invitee_email, token, status, personal_message, created_at)
    VALUES (?, ?, ?, ?, 'pending', ?, NOW())
");
$stmt->bind_param(
    "iisss",
    $game_id,
    $_SESSION['user_id'],
    $invitee_email,
    $token,
    $personal_message
);

if (!$stmt->execute()) {
    die("Error saving invitation: " . $stmt->error);
}
$invitation_id = $stmt->insert_id;
$stmt->close();

// Get sender username for email
$sender_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$sender_stmt->bind_param("i", $_SESSION['user_id']);
$sender_stmt->execute();
$sender_result = $sender_stmt->get_result();
$sender_info = $sender_result->fetch_assoc();
$sender_name = $sender_info['username'] ?? 'Someone';
$sender_stmt->close();

// Build acceptance URL relative to current base (avoid hardcoded folder mismatches)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// Ensure we point to accept_invite.php in the same directory tree
$accept_url = "{$protocol}{$host}{$basePath}/accept_invite.php?token=" . urlencode($token);

// Prepare personal message section for email
$personal_section = '';
if (!empty($personal_message)) {
    $personal_section = "
    <div style='background: #f0f8ff; padding: 15px; border-left: 3px solid #4a90e2; margin: 20px 0;'>
        <strong>Personal message from $sender_name:</strong><br>
        " . nl2br(htmlspecialchars($personal_message)) . "
    </div>";
}

// Prepare email content
$email_subject = "$sender_name invited you to play Intrachange!";
$email_content = "
<div style=\"font-family: Georgia, 'Times New Roman', serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px;\">
    <div style=\"background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;\">
        <h1 style=\"color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;\"> Intrachange </h1
        <p style=\"color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;\">Game Invitation</p>
    </div>
    <div style=\"padding: 30px; line-height: 1.6; color: #2c2c2c;\">
        <p>Hello!</p>
        <p><strong>$sender_name</strong> has invited you to play Intrachange - where Chess meets the I Ching!</p>
        $personal_section
        <div style=\"text-align: center; margin: 30px 0;\">
            <a href=\"$accept_url\" 
               style=\"background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: Georgia, serif;\">
               🎯 Accept Invitation
            </a>
        </div>
        <p style=\"margin-bottom: 0; color: #5a5a5a; text-align: center;\">Click the link above to join the game and begin your strategic journey.</p>
    </div>
    <div style=\"background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;\">
        <p style=\"margin: 0; color: #666; font-size: 0.9em;\">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>
    </div>
</div>";

// Send email using PHPMailer
$mailer = new PHPMailer(true);
$mail_sent = false;
try {
    $mailer->isSMTP();
    $mailer->Host       = 'mail.intrachange.net';
    $mailer->SMTPAuth   = true;
    $mailer->Username   = 'info@intrachange.net';
    $mailer->Password   = 'Intr4ch4ng3!';
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mailer->Port       = 465;
    $mailer->setFrom('info@intrachange.net', 'Intrachange');
    $mailer->addAddress($invitee_email, $invitee_name ?: '');
    $mailer->Subject = $email_subject;
    $mailer->Body    = $email_content;
    $mailer->isHTML(true);
    $mailer->send();
    $mail_sent = true;

    echo "Invitation sent successfully to " . htmlspecialchars($invitee_email);
    echo "<script>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 3000);
    </script>";
    echo "<p>Redirecting to game selection in 3 seconds...</p>";

} catch (Exception $e) {
    $error_message = "PHPMailer error: " . $mailer->ErrorInfo . " | Exception: " . $e->getMessage();
    file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | " . $error_message . "\n", FILE_APPEND);
    echo "Failed to send invitation. Error logged.";
}

$conn->close();
?>
