<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Show registration success message
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    echo '<div style="background:#d1fae5;border:1px solid #10b981;color:#065f46;padding:15px;border-radius:4px;margin-bottom:20px;text-align:center;">
            Registration successful! Please check your email for your welcome message.
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Intrachange</title>
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            max-width: 400px;
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            font-family: 'Georgia', serif;
            font-size: 1rem;
            background: #faf8f3;
            transition: border-color 0.3s ease;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #1a1a1a;
        }
        
        .submit-btn {
            width: 100%;
            background: #1a1a1a;
            color: #f4f1e8;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Georgia', serif;
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
    </style>
</head>
<body>
    <div class="header">
        <img src="img/icon.jpg" alt="Intrachange Icon">
        <h1>Login</h1>
    </div>
    
    <div class="login-container">
        <?php $next = isset($_GET['next']) ? $_GET['next'] : ''; ?>
        <form action="login_submit.php" method="post">
            <?php if (!empty($next)): ?>
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES); ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" autocomplete="off" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            
            <input type="submit" value="Login" class="submit-btn">
        </form>
        
        <div style="text-align: center; margin: 15px 0;">
            <a href="forgot_password.php" style="color: #1a1a1a; text-decoration: none; font-size: 0.9rem;">Forgot your password?</a>
        </div>
        
        <div class="back-link">
            <a href="index.html">← Back to Welcome</a>
        </div>
    </div>
</body>
</html>

