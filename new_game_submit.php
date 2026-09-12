<?php
session_start();
include 'backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to start a game. <a href='login.php'>Login here</a>");
}

// Prevent direct access without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("This page requires form data. <a href='dashboard.php'>Please go to dashboard first</a>");
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : 'multiplayer';
$white_id = $_SESSION['user_id'];

if ($mode === 'singleplayer') {
    // --- SINGLEPLAYER ---
    $stmt = $conn->prepare("INSERT INTO games (white_player_id, black_player_id, status, is_singleplayer) VALUES (?, ?, 'active', 1)");
    $stmt->bind_param("ii", $white_id, $white_id);
    $stmt->execute();
    $game_id = $stmt->insert_id;
    $stmt->close();

    header("Location: game.php?game_id=$game_id&user_id=$white_id");
    exit;

} elseif ($mode === 'multiplayer') {
    // --- MULTIPLAYER (username lookup) ---
    $opponent_username = isset($_POST['opponent']) ? trim($_POST['opponent']) : '';
    if (empty($opponent_username)) {
        die("Opponent username is required. <a href='new_game.php?mode=multiplayer'>Try again</a>");
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $opponent_username);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        die("Opponent '$opponent_username' not found. <a href='new_game.php?mode=multiplayer'>Try again</a>");
    }
    $stmt->bind_result($black_id);
    $stmt->fetch();
    $stmt->close();

    // Check for existing active/pending game
    $stmt = $conn->prepare("SELECT id FROM games WHERE ((white_player_id=? AND black_player_id=?) OR (white_player_id=? AND black_player_id=?)) AND (status='active' OR status='pending') LIMIT 1");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iiii", $white_id, $black_id, $black_id, $white_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($existing_game_id);
        $stmt->fetch();
        $game_id = $existing_game_id;
        header("Location: game.php?game_id=$game_id&user_id=$white_id");
        exit;
    }
    $stmt->close();

    // Create new active multiplayer game
    $stmt = $conn->prepare("INSERT INTO games (white_player_id, black_player_id, status, is_singleplayer) VALUES (?, ?, 'active', 0)");
    $stmt->bind_param("ii", $white_id, $black_id);
    $stmt->execute();
    $game_id = $stmt->insert_id;
    $stmt->close();

    header("Location: game.php?game_id=$game_id&user_id=$white_id");
    exit;

} elseif ($mode === 'twoplayer') {
    // --- TWO-PLAYER (email invitation) ---
    $invite_email = isset($_POST['invite_email']) ? trim($_POST['invite_email']) : '';
    if (empty($invite_email) || !filter_var($invite_email, FILTER_VALIDATE_EMAIL)) {
        die("A valid email address is required. <a href='new_game.php?mode=twoplayer'>Try again</a>");
    }

    // Create pending game (opponent unknown)
    $stmt = $conn->prepare("INSERT INTO games (white_player_id, status, is_singleplayer) VALUES (?, 'pending', 0)");
    $stmt->bind_param("i", $white_id);
    $stmt->execute();
    $game_id = $stmt->insert_id;
    $stmt->close();

    // Insert invitation (match table schema)
    $token = bin2hex(random_bytes(16));
    $personal_message = '';
    $stmt = $conn->prepare("INSERT INTO game_invitations (game_id, sender_id, invitee_email, token, status, personal_message, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
    $stmt->bind_param("issss", $game_id, $white_id, $invite_email, $token, $personal_message);
    $stmt->execute();
    $invitation_id = $stmt->insert_id;
    $stmt->close();

    // Send invitation email using PHPMailer
    require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/phpmailer/src/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'intrachange.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@intrachange.net';
        $mail->Password = 'Intr4ch4ng3!';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('info@intrachange.net', 'Intrachange');
        $mail->addAddress($invite_email);

        $mail->Subject = 'Intrachange Game Invitation';
        $accept_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
            . $_SERVER['HTTP_HOST'] . '/accept_invite.php?token=' . urlencode($token);

        // Beautiful HTML email body
        $icon_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/img/icon.jpg';
        $mail->isHTML(true);
        $mail->Body = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; overflow: hidden;">'
            . '<div style="background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;">'
            . '<img src="' . $icon_url . '" alt="Intrachange" width="80" height="80" style="display:block; margin:0 auto 12px auto; width:80px; max-width:80px; height:80px; border-radius:4px;">'
            . '<h1 style="color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;">Intrachange</h1>'
            . '<p style="color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;">The Game of Changes</p>'
            . '</div>'
            . '<div style="padding: 30px; line-height: 1.6; color: #2c2c2c;">'
            . '<p style="margin-bottom: 20px;">You\'ve been invited to play Intrachange!</p>'
            . '<p style="margin-bottom: 20px; font-size: 1.1em;">Click below to accept your invitation and begin your journey:</p>'
            . '<div style="text-align: center; margin: 30px 0;"><a href="' . $accept_url . '" style="background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif;">Accept Invitation</a></div>'
            . '<div style="background: #ffffff; border: 1px solid #e0d6c7; border-radius: 6px; padding: 20px; margin: 20px 0;">'
            . '<h3 style="color: #1a1a1a; margin-top: 0;">What makes Intrachange special?</h3>'
            . '<ul style="color: #5a5a5a; margin: 0;">'         
            . '<li><strong>Esoteric Moves:</strong> Pieces can "bounce" to unexpected squares based on I Ching hexagrams</li>'
            . '<li><strong>Strategic Depth:</strong> Every square corresponds to one of the 64 hexagrams from the I Ching</li>'
            . '<li><strong>Meaningful Play:</strong> Experience how ancient wisdom guides modern strategy</li>'
            . '<li><strong>Contemplative Gaming:</strong> More than just winning — it\'s about understanding patterns of change</li>'
            . '</ul></div>'
            . '<p style="margin-bottom: 0; color: #5a5a5a;">Game ID: ' . $game_id . ' (status: pending)</p>'
            . '</div>'
            . '<div style="background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;">'
            . '<p style="margin: 0; color: #666; font-size: 0.9em;">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>'
            . '</div>'
            . '</div>';
        $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
    }

    // Show confirmation HTML
    echo "<!DOCTYPE html>
<html>
<head>
    <!-- Google tag (gtag.js) -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-HQRDF2FZBV\"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-HQRDF2FZBV');
    </script>
    <title>Invitation Sent</title>
</head>
<body>
    <h2>Invitation Sent</h2>
    <p>Your friend at <strong>" . htmlspecialchars($invite_email) . "</strong> has been invited to join your game.</p>
    <p>Game ID: $game_id (status: pending)</p>
    <a href='dashboard.php'>Back to Dashboard</a>
</body>
</html>";
    exit;

} else {
    die("Unknown game mode.");
}
?>
