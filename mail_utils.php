<?php
/**
 * Intrachange Email Utilities
 * Centralized email functions for the Intrachange project
 */

require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using standardized Intrachange SMTP configuration
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body content
 * @param bool $isHTML Whether the body is HTML (default: true)
 * @param string $recipientName Optional recipient name
 * @return bool True on success, false on failure
 */
function sendIntrachangeMail($to, $subject, $body, $isHTML = true, $recipientName = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'intrachange.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@intrachange.net';
        $mail->Password   = 'Intr4ch4ng3!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('info@intrachange.net', 'Intrachange');
        $mail->addAddress($to, $recipientName);

        // Content
        $mail->isHTML($isHTML);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Intrachange Mail Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send game invitation email
 *
 * @param string $to Recipient email address
 * @param string $token Invitation token
 * @param string $recipientName Optional recipient name
 * @return bool True on success, false on failure
 */
function sendGameInvitationEmail($to, $token, $recipientName = '') {
    $accept_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST'] . '/accept_invite.php?token=' . urlencode($token);
    
    $icon_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') 
        . $_SERVER['HTTP_HOST'] . '/img/icon.jpg';
    
    $subject = 'Intrachange Game Invitation';
    
    $body = '<div style="font-family: Georgia, \'Times New Roman\', serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; overflow: hidden;">'
        . '<div style="background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%); padding: 30px; text-align: center;">'
        . '<img src="' . $icon_url . '" alt="Intrachange" width="80" height="80" style="display:block; margin:0 auto 12px auto; width:80px; max-width:80px; height:80px; border-radius:4px;">'
        . '<h1 style="color: #1a1a1a; margin: 0; font-size: 2.2em; font-weight: normal;">Intrachange</h1>'
        . '<p style="color: #5a5a5a; font-style: italic; margin: 10px 0 0 0;">The Game of Changes</p>'
        . '</div>'
        . '<div style="padding: 30px; line-height: 1.6; color: #2c2c2c;">'
        . '<p style="margin-bottom: 20px;">You\'ve been invited to play Intrachange!</p>'
        . '<p style="margin-bottom: 20px; font-size: 1.1em;">Click below to accept your invitation and begin your journey:</p>'
        . '<div style="text-align: center; margin: 30px 0;"><a href="' . $accept_url . '" style="background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: Georgia, serif;">Accept Invitation</a></div>'
        . '<div style="background: #ffffff; border: 1px solid #e0d6c7; border-radius: 6px; padding: 20px; margin: 20px 0;">'
        . '<h3 style="color: #1a1a1a; margin-top: 0;">What makes Intrachange special?</h3>'
        . '<ul style="color: #5a5a5a; margin: 0;">'
        . '<li><strong>Esoteric Moves:</strong> Pieces can "bounce" to unexpected squares based on I Ching hexagrams</li>'
        . '<li><strong>Strategic Depth:</strong> Every square corresponds to one of the 64 hexagrams from the I Ching</li>'
        . '<li><strong>Meaningful Play:</strong> Experience how ancient wisdom guides modern strategy</li>'
        . '<li><strong>Contemplative Gaming:</strong> More than just winning — it\'s about understanding patterns of change</li>'
        . '</ul>'
        . '</div>'
        . '<p style="margin-bottom: 0; color: #5a5a5a; text-align: center;">Click the link above to join the game and begin your strategic journey.</p>'
        . '</div>'
        . '<div style="background: #f4f1e8; padding: 20px; text-align: center; border-top: 1px solid #e0d6c7;">'
        . '<p style="margin: 0; color: #666; font-size: 0.9em;">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>'
        . '</div>'
        . '</div>';
    
    return sendIntrachangeMail($to, $subject, $body, true, $recipientName);
}

/**
 * Send welcome email to new users
 *
 * @param string $to Recipient email address
 * @param string $username New user's username
 * @return bool True on success, false on failure
 */
function sendWelcomeEmail($to, $username) {
    $login_url = 'https://intrachange.net/login.php';
    
    $subject = 'Welcome to Intrachange';
    
    $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;">'
        . '<div style="background: #f8f8f8; padding: 20px; text-align: center;">'
        . '<h1 style="color: #333;">Welcome to Intrachange!</h1>'
        . '</div>'
        . '<div style="padding: 20px;">'
        . '<p>Hello ' . htmlspecialchars($username) . ',</p>'
        . '<p>Welcome to Intrachange! Your account has been successfully created.</p>'
        . '<p>You can now log in and start playing this unique fusion of Chess and I Ching wisdom.</p>'
        . '<div style="text-align: center; margin: 20px 0;">'
        . '<a href="' . $login_url . '" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Log In Now</a>'
        . '</div>'
        . '<p>Thank you for joining our community!</p>'
        . '<p>Best regards,<br>The Intrachange Team</p>'
        . '</div>'
        . '<div style="background: #f8f8f8; padding: 10px; text-align: center; font-size: 12px; color: #666;">'
        . '© 2025 Intrachange. All rights reserved.'
        . '</div>'
        . '</div>';
    
    return sendIntrachangeMail($to, $subject, $body, true, $username);
}

/**
 * Send game notification email (turn notifications, game ready, etc.)
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $gameUrl URL to the game
 * @param string $gameId Game ID
 * @param string $recipientName Recipient's username
 * @return bool True on success, false on failure
 */
function sendGameNotificationEmail($to, $subject, $gameUrl, $gameId, $recipientName = '') {
    $body = '<div style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; padding: 30px;">'
        . '<h2 style="color: #1a1a1a;">' . htmlspecialchars($subject) . '</h2>'
        . '<p>The game is waiting for your move. Click below to resume:</p>'
        . '<div style="margin: 20px 0;"><a href="' . htmlspecialchars($gameUrl) . '" style="background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: Georgia, serif;">Resume Game</a></div>'
        . '<p style="color: #5a5a5a;">Game ID: #' . htmlspecialchars($gameId) . '</p>'
        . '<p style="margin: 0; color: #666; font-size: 0.9em;">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>'
        . '</div>';
    
    return sendIntrachangeMail($to, $subject, $body, true, $recipientName);
}

/**
 * Send password reset email
 *
 * @param string $to Recipient email address
 * @param string $resetToken Password reset token
 * @return bool True on success, false on failure
 */
function sendPasswordResetEmail($to, $resetToken) {
    $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $resetToken;
    
    $subject = 'Reset Your Intrachange Password';
    
    $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;">'
        . '<div style="background: #f8f8f8; padding: 20px; text-align: center;">'
        . '<h1 style="color: #333;">Password Reset Request</h1>'
        . '</div>'
        . '<div style="padding: 20px;">'
        . '<p>You have requested to reset your Intrachange password.</p>'
        . '<p>Click the link below to create a new password:</p>'
        . '<div style="text-align: center; margin: 20px 0;">'
        . '<a href="' . $reset_link . '" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a>'
        . '</div>'
        . '<p><strong>This link will expire in 1 hour for security reasons.</strong></p>'
        . '<p>If you did not request this password reset, please ignore this email.</p>'
        . '<p>Best regards,<br>The Intrachange Team</p>'
        . '</div>'
        . '<div style="background: #f8f8f8; padding: 10px; text-align: center; font-size: 12px; color: #666;">'
        . '© 2025 Intrachange. All rights reserved.'
        . '</div>'
        . '</div>';
    
    return sendIntrachangeMail($to, $subject, $body);
}