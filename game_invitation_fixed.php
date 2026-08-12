<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to send a game invitation. <a href='login.php'>Login here</a>");
}

// Read mode safely
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'twoplayer';
if ($mode !== 'twoplayer') {
    die("Invalid mode. <a href='dashboard.php'>Go to dashboard</a>");
}

// Get or create a game_id for this invitation
include 'backend/db_connect.php';

// Create a new game for the invitation
$stmt = $conn->prepare("INSERT INTO games (white_player_id, black_player_id, current_turn, created_at) VALUES (?, NULL, 'white', NOW())");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$game_id = $stmt->insert_id;
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Send Game Invitation - Intrachange</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #2c2c2c;
        }
        .container {
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        h2 {
            font-size: 2em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            color: #1a1a1a;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 4px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #1a1a1a;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0d6c7;
            border-radius: 4px;
            margin-bottom: 20px;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 16px;
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
            font-family: 'Georgia', 'Times New Roman', serif;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover { background: #333; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #5a5a5a;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link:hover { color: #1a1a1a; }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            <img src="img/icon.jpg" alt="Intrachange" class="logo-icon">
            Invite a Friend to Play
        </h2>

        <p>Send a two-player game invitation to someone with an Intrachange account.</p>

        <form action="game_invitation_send.php" method="post">
            <input type="hidden" name="mode" value="twoplayer">
            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">

            <label for="username">Friend's Username (optional)</label>
            <input type="text" name="username" id="username" placeholder="Enter username">

            <label for="email">Friend's Email</label>
            <input type="email" name="email" id="email" required placeholder="Enter their email address">

            <input type="submit" value="Send Invitation">
        </form>

        <a href="new_game.php?mode=twoplayer" class="back-link">← Back to Two-Player Game</a>
    </div>
</body>
</html>
