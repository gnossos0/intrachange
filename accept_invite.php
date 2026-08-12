<?php
// accept_invite.php - Complete invitation acceptance handler

session_start();
require_once __DIR__ . '/backend/db_connect.php';

// PHPMailer includes for sending notification email
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Get token early so we can preserve it through login
$token = $_GET['token'] ?? '';

// If not logged in, redirect to login with return path to accept_invite
if (!isset($_SESSION['user_id'])) {
    $next = 'accept_invite.php';
    if (!empty($token)) {
        $next .= '?token=' . urlencode($token);
    }
    header('Location: login.php?next=' . urlencode($next));
    exit();
}
if (empty($token)) {
    die("Error: No invitation token provided.");
}

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Retrieve invitation
    $stmt = $conn->prepare("
        SELECT id, sender_id, invitee_email, game_id, accepted_at, status
        FROM game_invitations
        WHERE token = ? FOR UPDATE
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $inv_result = $stmt->get_result();
    if ($inv_result->num_rows === 0) {
        throw new Exception("Invalid invitation token.");
    }
    $invitation = $inv_result->fetch_assoc();
    $stmt->close();

    // 2. Check if already accepted
    if (!empty($invitation['accepted_at']) || $invitation['status'] === 'accepted') {
        throw new Exception("This invitation has already been accepted.");
    }

    // 4. Verify recipient email matches current user (temporarily disabled for testing)
    $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    // Temporarily commented out for testing
    /*
    if (trim(strtolower($user_data['email'])) !== trim(strtolower($invitation['invitee_email']))) {
        throw new Exception("This invitation was sent to a different email address.");
    }
    */

    // 5. Mark invitation as accepted
    $accept_stmt = $conn->prepare("
        UPDATE game_invitations 
        SET accepted_at = NOW(), status = 'accepted'
        WHERE id = ?
    ");
    $accept_stmt->bind_param("i", $invitation['id']);
    $accept_stmt->execute();
    $accept_stmt->close();

    // 6. Add user as white player (makes first move), activate the game, and set initial turn to white
    // Set the accepting user as black_player_id, not white_player_id
    // Set both white_player_id (sender) and black_player_id (accepting user)
    // Set black_player_id to sender (originator), white_player_id to accepting user (invitee)
    $update_game_stmt = $conn->prepare("UPDATE games SET black_player_id = ?, white_player_id = ?, status = 'active', current_turn = 'white' WHERE id = ?");
    $update_game_stmt->bind_param("iii", $invitation['sender_id'], $_SESSION['user_id'], $invitation['game_id']);
    $update_game_stmt->execute();
    $update_game_stmt->close();

    $conn->commit();

    // 7. Get sender username and email for notification
    $sender_stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $sender_stmt->bind_param("i", $invitation['sender_id']);
    $sender_stmt->execute();
    $sender_data = $sender_stmt->get_result()->fetch_assoc();
    $sender_username = $sender_data['username'] ?? 'Unknown';
    $sender_email = $sender_data['email'] ?? '';
    $sender_stmt->close();

    // 8. Get accepter username for email
    $accepter_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $accepter_stmt->bind_param("i", $_SESSION['user_id']);
    $accepter_stmt->execute();
    $accepter_data = $accepter_stmt->get_result()->fetch_assoc();
    $accepter_username = $accepter_data['username'] ?? 'Someone';
    $accepter_stmt->close();

    // 9. Send notification email to white player (game creator)
    if (!empty($sender_email)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $game_url = $protocol . $host . $basePath . "/game.php?game_id=" . urlencode($invitation['game_id']);
        
        $notification_subject = "Your Intrachange invitation was accepted!";
        $notification_content = "
        <div style=\"font-family: Georgia, 'Times New Roman', serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px;\">
            <div style=\"background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;\">
                <h1 style=\"color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;\">Intrachange</h1>
                <p style=\"color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;\">Game Ready!</p>
            </div>
                <div style=\"padding: 30px; line-height: 1.6; color: #2c2c2c;\">
                <p>Great news, {$sender_username}!</p>
                <p><strong>{$accepter_username}</strong> has accepted your invitation and joined your game!</p>
                <div style=\"background: #f0f8ff; padding: 15px; border-left: 3px solid #4a90e2; margin: 20px 0;\">
                    <strong>Game Details:</strong><br>
                    • Game ID: #{$invitation['game_id']}<br>
                    • Your role: Black Player<br>
                    • Opponent: {$accepter_username} (White Player — they have made the first move)
                </div>
                <div style=\"text-align: center; margin: 30px 0;\">
                    <a href=\"{$game_url}\" 
                       style=\"background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: Georgia, serif;\">
                       Start Playing Now
                    </a>
                </div>
                <p style=\"margin-bottom: 0; color: #5a5a5a; text-align: center;\">The game is ready and waiting for your first move!</p>
            </div>
            <div style=\"background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;\">
                <p style=\"margin: 0; color: #666; font-size: 0.9em;\">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>
            </div>
        </div>";

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host       = 'intrachange.net';
            $mailer->SMTPAuth   = true;
            $mailer->Username   = 'info@intrachange.net';
            $mailer->Password   = 'Intr4ch4ng3!';
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port       = 465;
            $mailer->setFrom('info@intrachange.net', 'Intrachange');
            $mailer->addAddress($sender_email, $sender_username);
            $mailer->Subject = $notification_subject;
            $mailer->Body    = $notification_content;
            $mailer->isHTML(true);
            $mailer->send();
        } catch (PHPMailerException $e) {
            // Log email error but don't stop the acceptance process
            error_log("Failed to send game ready notification: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    die("Error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invitation Accepted - Intrachange</title>
<style>
body { font-family: Georgia, serif; background: linear-gradient(135deg,#f4f1e8 0%,#e8e0d0 100%); margin:0; padding:20px; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.container { background:#faf8f3; border:2px solid #d4c4a8; border-radius:8px; padding:40px; max-width:500px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h1 { color:#1a1a1a; margin-bottom:10px; font-size:2.2em; font-weight:normal;}
.success-icon { font-size:3em; color:#4a90e2; margin-bottom:20px;}
.message { color:#2c2c2c; line-height:1.6; margin-bottom:30px; }
.game-info { background:#f0f8ff; padding:15px; border-left:3px solid #4a90e2; margin:20px 0; text-align:left; }
.buttons { margin-top:30px; }
.btn { display:inline-block; background:#1a1a1a; color:#faf8f3; padding:12px 25px; text-decoration:none; border-radius:4px; margin:0 10px; font-family:Georgia, serif; font-size:16px; transition:background-color 0.3s; }
.btn:hover { background:#333; }
.btn-secondary { background:#666; }
.btn-secondary:hover { background:#888; }
</style>
</head>
<body>
<div class="container">
<div class="success-icon">🎯</div>
<h1>Invitation Accepted! </h1>
<div class="message">
<p><strong>Congratulations!</strong> You have joined the game successfully.</p>
</div>
<div class="game-info">
<strong>Game Details:</strong><br>
• Invited by: <?php echo htmlspecialchars($sender_username); ?><br>
• Game ID: #<?php echo htmlspecialchars($invitation['game_id']); ?><br>
• Your role: White Player (you make the first move)
</div>
<p class="message">
The game is now ready to begin. You and <?php echo htmlspecialchars($sender_username); ?> can start your strategic journey!
</p>
<div class="buttons">
<a href="game.php?game_id=<?php echo urlencode($invitation['game_id']); ?>" class="btn">🎮 Start Playing</a>
<a href="dashboard.php" class="btn btn-secondary">📋 View All Games</a>
</div>
</div>
</body>
</html>
