<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'backend/db_connect.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/mail_utils.php';

// Sanitize input
$username = $conn->real_escape_string($_POST['username']);
$email = $conn->real_escape_string($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if email already exists
$check = $conn->query("SELECT id FROM users WHERE email='$email'");
if ($check && $check->num_rows > 0) {
    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:4px;margin-bottom:20px;'>
            This email is already registered. <a href='login.php'>Log in here</a> or use a different email address.
          </div>";
    exit();
}

# Insert user
$sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
if ($conn->query($sql) === TRUE) {

    // Capture registration details for admin notification
    $registrationTime = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ipAddress = $_SERVER['HTTP_X_REAL_IP'];
    }

    // --- SEND ADMIN NOTIFICATION EMAIL ---
    try {
        $adminSubject = 'New Intrachange User Registered';
        $adminBody = "A new user has registered on Intrachange:\n\n"
                   . "Username: $username\n"
                   . "Email: $email\n" 
                   . "Registration Time: $registrationTime\n"
                   . "IP Address: $ipAddress\n\n"
                   . "User can now log in at: https://intrachange.net/login.php";
        
        $adminNotificationSent = sendIntrachangeMail('info@fertileground.org', $adminSubject, $adminBody, false);
        if (!$adminNotificationSent) {
            error_log('Admin notification email failed for new user: ' . $username);
        }
    } catch (Exception $e) {
        error_log('Admin notification error: ' . $e->getMessage());
    }

    // --- SEND WELCOME EMAIL ---
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
        $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Welcome to Intrachange';

        $login_url = 'https://intrachange.net/login.php';

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;">
            <div style="background: #f8f8f8; padding: 20px; text-align: center;">
                <img src="https://intrachange.net/img/icon.jpg" alt="Intrachange" style="width: 80px; height: auto;">
            </div>
            <div style="padding: 30px; color: #333;">
                <h2 style="margin-top: 0;">Welcome to Intrachange</h2>
                <p>Hello ' . htmlspecialchars($username) . ',</p>
                <p>Welcome aboard! You can begin in <strong>Single Player Mode</strong> to explore the game at your own pace. When you’re ready, invite a friend to play a <strong>Two-Player Game</strong>.</p>
                <p>Check out the <strong>Tutorial</strong> to get oriented — it’s still under construction, so expect a few rough edges.</p>
                <p><em>Intrachange is currently in beta</em>. If you run into bugs or unexpected behavior, we’d love your feedback — you can submit it from your dashboard.</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $login_url . '" style="background: #4a5568; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-weight: bold;">Log In</a>
                </div>
                <p>We’re glad you’re here.<br>— The Intrachange Team</p>
            </div>
        </div>';

        $mail->send();
    } catch (Exception $e) {
        error_log('Welcome email error: ' . $mail->ErrorInfo);
    }

    // Redirect to login page with success flag
    header("Location: login.php?registered=1");
    exit();

} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
?>
