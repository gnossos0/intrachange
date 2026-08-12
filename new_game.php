<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to start a game. <a href='login.php'>Login here</a>");
}
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'multiplayer'; // Always defined
?>

<!DOCTYPE html>
<html>
<head>
    <title>Start New Game - <?php echo ucfirst($mode); ?></title>
    <link rel="stylesheet" href="assets/global.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .form-container {
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 90%;
        }
        h2 {
            margin-bottom: 30px;
            font-size: 2em;
            color: #1a1a1a;
            font-weight: normal;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 4px;
        }
        .mode-info {
            background: #ffffff;
            border: 1px solid #e0d6c7;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            color: #5a5a5a;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #1a1a1a;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0d6c7;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 16px;
            background: #ffffff;
        }
        input[type="text"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: #1a1a1a;
        }
        input[type="submit"] {
            background: #1a1a1a;
            color: #faf8f3;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover {
            background: #333;
        }
        .back-link {
            color: #5a5a5a;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #1a1a1a;
        }
        .extra-links {
            margin-top: 20px;
            font-size: 0.95em;
        }
        .extra-links a {
            color: #1a1a1a;
            text-decoration: underline;
        }
        
        .navigation-links {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0d6c7;
            text-align: center;
        }
        
        .navigation-links .nav-link {
            color: #8a9d8a;
            text-decoration: none;
            margin: 0 8px;
            font-size: 0.9em;
            transition: color 0.3s ease;
        }
        
        .navigation-links .nav-link:hover {
            color: #6d7a6d;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>
        <img src="img/icon.jpg" alt="Intrachange" class="logo-icon">
        Start New <?php echo ucfirst(str_replace('twoplayer', 'Two-Player', $mode)); ?> Game
    </h2>
    
    <?php if ($mode === 'singleplayer'): ?>
        <div class="mode-info">
            <strong>Single Player Mode</strong><br>
            You'll control both White and Black pieces.<br>
            Perfect for studying positions and exploring strategies.
        </div>
        <form action="new_game_submit.php" method="post">
            <input type="hidden" name="mode" value="singleplayer">
            <input type="submit" value="Begin Single Player Game">
        </form>
    
    <?php elseif ($mode === 'twoplayer'): ?>
        <div class="mode-info">
            Challenge a friend by email invitation.<br>
            They'll get an email to join your game.
        </div>
        <form action="new_game_submit.php" method="post">
            <input type="hidden" name="mode" value="twoplayer">
            <label>Opponent (Email Address):</label>
            <input type="email" name="invite_email" required placeholder="Enter opponent's email address">
            <input type="submit" value="Send Game Invitation">
        </form>
        <div class="extra-links">
            <p>Don't have friends on Intrachange yet? <a href="invite_friends.php">Send them an invitation to join!</a></p>
        </div>
    
    <?php else: ?>
        <div class="mode-info">
            Challenge a friend who is online now <br>and has an Intrachange account.
        </div>
        <form action="new_game_submit.php" method="post">
            <input type="hidden" name="mode" value="multiplayer">
            <label>Opponent (Username):</label>
            <input type="text" name="opponent" required placeholder="Enter opponent's username">
            <input type="submit" value="Start Multiplayer Game">
        </form>
        <div class="extra-links">
            <p>Don’t have friends on Intrachange yet? <a href="send_invitation.php">Send them an invitation!</a></p>
        </div>
    <?php endif; ?>
    
    <div class="navigation-links">
        <a href="player_profile.php" class="nav-link">My Profile</a> | 
        <a href="dashboard.php" class="nav-link">Dashboard</a> | 
        <a href="logout.php" class="nav-link">Logout</a>
    </div>
</div>
</body>
</html>
