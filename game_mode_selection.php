<?php
session_start();

// Optional: show errors while testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intrachange - Select Game Mode</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c2c2c;
        }

        .selection-container {
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            width: 90%;
        }

        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            color: #1a1a1a;
            font-weight: normal;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .logo-icon { width: 60px; height: 60px; border-radius: 4px; }
        .subtitle { font-size: 1.2em; margin-bottom: 40px; color: #5a5a5a; font-style: italic; }

        .mode-options {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .mode-card {
            background: #ffffff;
            border: 2px solid #e0d6c7;
            border-radius: 6px;
            padding: 40px 30px;
            width: 250px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c2c2c;
        }

        .mode-card:hover {
            transform: translateY(-3px);
            border-color: #1a1a1a;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .mode-title { font-size: 1.5em; font-weight: bold; margin-bottom: 15px; color: #1a1a1a; }
        .mode-description { font-size: 1em; color: #666; line-height: 1.6; }

        .back-link {
            position: absolute;
            top: 30px;
            left: 30px;
            color: #5a5a5a;
            text-decoration: none;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.3s ease;
        }

        .back-link:hover { color: #1a1a1a; }

        @media (max-width: 768px) {
            .selection-container { padding: 30px; }
            h1 { font-size: 2.5em; }
            .mode-options { flex-direction: column; align-items: center; }
            .mode-card { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>

    <div class="header-bar" style="position: absolute; top: 20px; right: 20px; color: #1a1a1a;">
        Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Player'; ?>! 
        <a href="logout.php" style="color: #1a1a1a; text-decoration: underline; margin-left: 10px;">Logout</a>
    </div>

    <div class="selection-container">
        <h1>
            <img src="img/icon.jpg" alt="Intrachange" class="logo-icon">
            Intrachange
        </h1>
        <p class="subtitle">Choose Your Path</p>
        
        <div class="mode-options">
            <!-- Single Player -->
            <a href="new_game.php?mode=singleplayer" class="mode-card">
                <div class="mode-title">Single Player</div>
                <div class="mode-description">
                    Control both White and Black pieces.<br>
                    Perfect for exploring strategies,<br>
                    studying positions, and contemplation.
                </div>
            </a>
            
            <!-- Two Players -->
            <a href="new_game.php?mode=twoplayer" class="mode-card">
                <div class="mode-title">Two Players</div>
                <div class="mode-description">
                    Begin a new game with another Intrachange user.<br>
                    Play against a friend or invite them via email.
                </div>
            </a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0d6c7;">
            <a href="invite_friends.php" style="color: #5a5a5a; text-decoration: none; font-size: 0.9em;">
                Invite Your Friends to Join the Game
            </a>
        </div>
    </div>

</body>
</html>
