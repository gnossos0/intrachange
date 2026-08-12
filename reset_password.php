<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Include database connection
include __DIR__ . '/backend/db_connect.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    header("Location: login.php?error=invalid_reset");
    exit;
}

$user = null;
$error = '';

try {
    // Validate token using mysqli
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    if ($stmt->error) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $error = 'This reset link is invalid or has expired.';
    }
    
} catch(Exception $e) {
    error_log("Database error in reset password: " . $e->getMessage());
    $error = 'A system error occurred. Please try again later.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Update password and clear reset token using mysqli
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            $stmt->bind_param('si', $hashed_password, $user['id']);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("Database execute failed: " . $stmt->error);
            }
            
            // Redirect to login with success message
            header("Location: login.php?reset=success");
            exit;
            
        } catch(Exception $e) {
            error_log("Error updating password: " . $e->getMessage());
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Intrachange</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #1a1a1a;
            font-size: 2.2rem;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1a1a1a;
            font-weight: bold;
        }
        
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #1a1a1a;
        }
        
        .submit-btn {
            width: 100%;
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #1a1a1a;
            text-decoration: none;
            font-style: italic;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        .info {
            background: #e0f2fe;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
            line-height: 1.5;
        }

        .user-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #d1d5db;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="img/icon.jpg" alt="Intrachange Icon">
        <h1>Reset Password</h1>
    </div>
    
    <div class="login-container">
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php elseif ($user): ?>
            <div class="user-info">
                <strong>Resetting password for:</strong><br>
                <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
            </div>
            
            <div class="message info">
                <p>Please enter your new password below.</p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-requirements">
                        Must be at least 6 characters long
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <input type="submit" value="Update Password" class="submit-btn">
            </form>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
