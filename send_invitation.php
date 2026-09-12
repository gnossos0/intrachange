<?php
session_start();
$timing_log = [];
$timing_log['start'] = microtime(true);

// Remove Composer autoload, use manual PHPMailer includes
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invite_friends.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$friend_email = $_POST['friend_email'] ?? '';
$friend_name = $_POST['friend_name'] ?? '';
$personal_message = $_POST['personal_message'] ?? '';

// Validate email
if (!filter_var($friend_email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
    include 'invite_friends.php';
    exit();
}

// Email template content: build app base (e.g., /intrachange_test)
$scheme_host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$app_base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$app_base_url = $scheme_host . $app_base_path;
$register_url = $app_base_url . '/register.php';
$icon_url = $app_base_url . '/img/icon.jpg';

$email_template = '
<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; overflow: hidden;">
    <div style="background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;">
    <img src="' . $icon_url . '" alt="Intrachange" width="80" height="80" style="display:block; margin:0 auto 12px auto; width:80px; max-width:80px; height:80px; border-radius:4px;">
        <h1 style="color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;">
            Intrachange
        </h1>
        <p style="color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;">The Game of Changes</p>
    </div>
    
    <div style="padding: 30px; line-height: 1.6; color: #2c2c2c;">
        <p style="margin-bottom: 20px;">Dear [FRIEND_NAME],</p>
        
        <p style="margin-bottom: 20px; font-size: 1.1em;">
            Hello! I\'ve stumbled into something unusual and extraordinary — a game that weaves together the strategy of Chess with the timeless wisdom of the I Ching. It\'s called <strong>Intrachange</strong>, and it feels like stepping into an ancient archive where every move has meaning.
        </p>
        
        <p style="margin-bottom: 20px; font-size: 1.1em;">
            I\'d love for you to join me in exploring it — not just to play, but to witness how patterns of change reveal themselves on the board. Click below to enter, and let\'s see what insights the Game of Changes offers us both.
        </p>
        
        [PERSONAL_MESSAGE]
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $register_url . '" 
               style="background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif;">
                🎯 Join Intrachange
            </a>
        </div>
        
        <div style="background: #ffffff; border: 1px solid #e0d6c7; border-radius: 6px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #1a1a1a; margin-top: 0;">What makes Intrachange special?</h3>
            <ul style="color: #5a5a5a; margin: 0;">
                <li><strong>Esoteric Moves:</strong> Pieces can "bounce" to unexpected squares based on I Ching hexagrams</li>
                <li><strong>Strategic Depth:</strong> Every square corresponds to one of the 64 hexagrams from the I Ching</li>
                <li><strong>Meaningful Play:</strong> Experience how ancient wisdom guides modern strategy</li>
                <li><strong>Contemplative Gaming:</strong> More than just winning — it\'s about understanding patterns of change</li>
            </ul>
        </div>
        
        <p style="margin-bottom: 0; color: #5a5a5a;">
            Once you\'ve registered, you can find me as <strong>[SENDER_NAME]</strong> and we can start a game together.
        </p>
    </div>
    
    <div style="background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;">
        <p style="margin: 0; color: #666; font-size: 0.9em;">
            Intrachange • Where Chess meets the I Ching • The Game of Changes
        </p>
    </div>
</div>
';

// Prepare email content
$friend_display_name = !empty($friend_name) ? $friend_name : 'Friend';
$email_subject = "$username has invited you to play Intrachange!";

// Use the highlighted beautiful HTML template for the email body
$email_content = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; overflow: hidden;">
    <div style="background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;">
    <img src="' . $icon_url . '" alt="Intrachange" width="80" height="80" style="display:block; margin:0 auto 12px auto; width:80px; max-width:80px; height:80px; border-radius:4px;">
        <h1 style="color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;">
            Intrachange
        </h1>
        <p style="color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;">The Game of Changes</p>
    </div>
    
    <div style="padding: 30px; line-height: 1.6; color: #2c2c2c;">
        <p style="margin-bottom: 20px;">Dear ' . htmlspecialchars($friend_display_name) . ',</p>
        
        <p style="margin-bottom: 20px; font-size: 1.1em;">
            Hello! I\'ve stumbled into something unusual and extraordinary — a game that weaves together the strategy of Chess with the timeless wisdom of the I Ching. It\'s called <strong>Intrachange</strong>, and it feels like stepping into an ancient archive where every move has meaning.
        </p>
        
        <p style="margin-bottom: 20px; font-size: 1.1em;">
            I\'d love for you to join me in exploring it — not just to play, but to witness how patterns of change reveal themselves on the board. Click below to enter, and let\'s see what insights the Game of Changes offers us both.
        </p>
        
        ' . (!empty($personal_message) ? "<div style='background: #f0f8ff; padding: 15px; border-left: 3px solid #4a90e2; margin: 20px 0;'><strong>Personal message from $username:</strong><br>" . nl2br(htmlspecialchars($personal_message)) . "</div>" : "") . '
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $register_url . '" 
               style="background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Arial, sans-serif;">
                🎯 Join Intrachange
            </a>
        </div>
        
        <div style="background: #ffffff; border: 1px solid #e0d6c7; border-radius: 6px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #1a1a1a; margin-top: 0;">What makes Intrachange special?</h3>
            <ul style="color: #5a5a5a; margin: 0;">
                <li><strong>Esoteric Moves:</strong> Pieces can "bounce" to unexpected squares based on I Ching hexagrams</li>
                <li><strong>Strategic Depth:</strong> Every square corresponds to one of the 64 hexagrams from the I Ching</li>
                <li><strong>Meaningful Play:</strong> Experience how ancient wisdom guides modern strategy</li>
                <li><strong>Contemplative Gaming:</strong> More than just winning — it\'s about understanding patterns of change</li>
            </ul>
        </div>
        
        <p style="margin-bottom: 0; color: #5a5a5a;">
            Once you\'ve registered, you can find me as <strong>' . htmlspecialchars($username) . '</strong> and we can start a game together.
        </p>
    </div>
    
    <div style="background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;">
        <p style="margin: 0; color: #666; font-size: 0.9em;">
            Intrachange • Where Chess meets the I Ching • The Game of Changes
        </p>
    </div>
</div>';
// ----------------------------

// PHPMailer block (timing + error log, mail send commented out for bottleneck test)
$timing_log['before_mail'] = microtime(true);
$mail_sent = false;
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
    $mailer->addAddress($friend_email, $friend_display_name);
    $mailer->Subject = $email_subject;
    $mailer->Body    = $email_content;
    $mailer->isHTML(true);
    // Send mail and set mail_sent flag
    $mailer->send();
    $mail_sent = true;
} catch (Exception $e) {
    $mail_sent = false;
    file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | PHPMailer error: " . $mailer->ErrorInfo . " | Exception: " . $e->getMessage() . "\n", FILE_APPEND);
}
// Direct test log to verify logging works
file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | Log test\n", FILE_APPEND);
$timing_log['after_mail'] = microtime(true);


$timing_log['before_db'] = microtime(true);
include 'backend/db_connect.php';
$db_test_start = microtime(true);
$db_test_stmt = $conn->prepare('SELECT 1');
$db_test = false;
if ($db_test_stmt) {
    $db_test = $db_test_stmt->execute();
    if (!$db_test) {
        file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB SELECT 1 failed: " . $db_test_stmt->error . "\n", FILE_APPEND);
    }
    $db_test_stmt->close();
} else {
    file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB SELECT 1 prepare failed: " . $conn->error . "\n", FILE_APPEND);
}
$db_test_end = microtime(true);
$timing_log['after_db_test'] = $db_test_end;


// Log the invitation attempt (timing + error log)
$timing_log['before_db_insert'] = microtime(true);
try {
    $stmt = $conn->prepare("INSERT INTO invitations (sender_id, friend_email, friend_name, personal_message, sent_at, email_sent) VALUES (?, ?, ?, ?, NOW(), ?)");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB prepare failed: " . $conn->error . "\n", FILE_APPEND);
        throw new \Exception('Invitation table insert prepare failed');
    }
    $email_sent = $mail_sent ? 1 : 0;
    $stmt->bind_param("isssi", $user_id, $friend_email, $friend_name, $personal_message, $email_sent);
    $stmt->execute();
    if ($stmt->error) {
        file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB execute failed: " . $stmt->error . "\n", FILE_APPEND);
        throw new \Exception('Invitation table insert execute failed');
    }
    $stmt->close();
} catch (\Exception $e) {
    file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    // If table doesn't exist, create it
    $create_table = "CREATE TABLE IF NOT EXISTS invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        friend_email VARCHAR(255) NOT NULL,
        friend_name VARCHAR(100),
        personal_message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        email_sent BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (sender_id) REFERENCES users(id)
    )";
    $create_stmt = $conn->prepare($create_table);
    if ($create_stmt && $create_stmt->execute()) {
        $create_stmt->close();
        $stmt = $conn->prepare("INSERT INTO invitations (sender_id, friend_email, friend_name, personal_message, sent_at, email_sent) VALUES (?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB prepare failed (after create): " . $conn->error . "\n", FILE_APPEND);
        } else {
            $email_sent = $mail_sent ? 1 : 0;
            $stmt->bind_param("isssi", $user_id, $friend_email, $friend_name, $personal_message, $email_sent);
            $stmt->execute();
            if ($stmt->error) {
                file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB execute failed (after create): " . $stmt->error . "\n", FILE_APPEND);
            }
            $stmt->close();
        }
    } else {
        if ($create_stmt) {
            file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB create table failed: " . $create_stmt->error . "\n", FILE_APPEND);
            $create_stmt->close();
        } else {
            file_put_contents(__DIR__ . '/invitation_error_log.txt', date('Y-m-d H:i:s') . " | DB create table prepare failed: " . $conn->error . "\n", FILE_APPEND);
        }
    }
}
$timing_log['after_db_insert'] = microtime(true);

// Write timing log
$timing_log['end'] = microtime(true);
$timing_report = "TIMING LOG:\n";
foreach ($timing_log as $k => $v) {
    $timing_report .= "$k: $v\n";
}
$timing_report .= "DB SELECT 1 duration: " . ($db_test_end - $db_test_start) . "\n";
$timing_report .= "Total duration: " . ($timing_log['end'] - $timing_log['start']) . "\n";
file_put_contents(__DIR__ . '/invitation_error_log.txt', $timing_report, FILE_APPEND);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-HQRDF2FZBV"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-HQRDF2FZBV');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Sent - Intrachange</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c2c2c;
            margin: 0;
        }
        .result-container {
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 90%;
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        .success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .btn {
            background: #1a1a1a;
            color: #faf8f3;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .btn:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <h1>📧 Invitation Status</h1>
        
        <?php if ($mail_sent): ?>
            <div class="success">
                <strong>✅ Invitation sent successfully!</strong><br>
                Your friend <?php echo htmlspecialchars($friend_display_name); ?> should receive the invitation at 
                <?php echo htmlspecialchars($friend_email); ?> shortly.
            </div>
            <p>They'll be able to register for Intrachange and start playing with you right away!</p>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Email delivery issue</strong><br>
                We've saved your invitation, but there may have been an issue sending the email.
                You might want to reach out to <?php echo htmlspecialchars($friend_display_name); ?> directly and share this link:
            </div>
            <p style="background: #f8f6f1; padding: 15px; border-radius: 4px; font-family: monospace; word-break: break-all;">
                <?php echo htmlspecialchars($app_base_url . '/register.php'); ?>
            </p>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="invite_friends.php" class="btn">Invite Another Friend</a>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
