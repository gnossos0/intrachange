<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
try {
    include 'backend/db_connect.php';
    
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Create a new game for the invitation using proper multiplayer structure
    // Game creator becomes black player (waits for white to start)
    $stmt = $conn->prepare("INSERT INTO games (black_player_id, game_mode, status, is_singleplayer, created_at) VALUES (?, 'twoplayer', 'pending', FALSE, NOW())");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    
    $game_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    if (!$game_id) {
        die("Failed to create game - no ID returned");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Game Invitation - Intrachange</title>
    <style>
        body {
            font-family: Georgia, serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 10px;
            font-size: 2.2em;
            font-weight: normal;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #1a1a1a;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #d4c4a8;
            border-radius: 4px;
            font-size: 16px;
            font-family: Georgia, serif;
            background-color: #faf8f3;
            color: #1a1a1a;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 8px rgba(26, 26, 26, 0.1);
        }

        input[type="submit"] {
            display: inline-block;
            background: #1a1a1a;
            color: #faf8f3;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-family: Georgia, serif;
            font-size: 16px;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        input[type="submit"]:hover {
            background: #333;
        }

        .info {
            background: #f0f8ff;
            border: 1px solid #87ceeb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #2c5aa0;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #1a1a1a;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Send Game Invitation</h1>
        
        <div class="info">
            📧 Send an invitation to play Intrachange with a friend! They'll receive an email with a link to join your game.
        </div>

        <form action="game_invitation_send.php" method="post">
            <input type="hidden" name="mode" value="twoplayer">
            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">

            <label for="username">Friend's Username (optional)</label>
            <input type="text" name="username" id="username" placeholder="Enter username">

            <label for="email">Friend's Email</label>
            <input type="email" name="email" id="email" required placeholder="Enter their email address">

            <input type="submit" value="Send Invitation">
        </form>

        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
