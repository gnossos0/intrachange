<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get active games for this user with last move time
require_once __DIR__ . '/backend/db_connect.php';

$active_games = [];
$stmt = $conn->prepare("
    SELECT g.id, g.white_player_id, g.black_player_id, g.current_turn, g.created_at, g.updated_at,
           u1.username as white_username, u2.username as black_username,
           COALESCE(MAX(m.created_at), g.created_at) as last_move_time,
           COUNT(m.id) as move_count
    FROM games g
    LEFT JOIN users u1 ON g.white_player_id = u1.id
    LEFT JOIN users u2 ON g.black_player_id = u2.id
    LEFT JOIN moves m ON g.id = m.game_id
    WHERE (g.white_player_id = ? OR g.black_player_id = ?) 
    AND g.status = 'active' 
    AND g.archived = 0 
    AND g.deleted = 0
    GROUP BY g.id
    ORDER BY last_move_time DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Determine opponent username
    if ($row['white_player_id'] == $user_id) {
        $row['opponent_username'] = $row['black_username'];
    } else {
        $row['opponent_username'] = $row['white_username'];
    }
    $active_games[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Intrachange Dashboard</title>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            margin: 0;
            padding: 40px;
            color: #2c2c2c;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #faf8f3;
            border: 2px solid #d4c4a8;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-layout {
            display: grid;
            grid-template-columns: 70% 30%;
            gap: 40px;
            margin-top: 30px;
        }
        
        .left-column, .right-column {
            background: #ffffff;
            border: 1px solid #e0d6c7;
            border-radius: 8px;
            padding: 30px;
        }
        
        .section-title {
            color: #1a1a1a;
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 1px solid #e0d6c7;
            padding-bottom: 10px;
        }
        
        .new-game-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .mode-card {
            background: #f9f9f9;
            border: 2px solid #e0d6c7;
            border-radius: 6px;
            padding: 20px;
            text-decoration: none;
            color: #2c2c2c;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .mode-card:hover {
            background: #f0f0f0;
            border-color: #d4c4a8;
            transform: translateY(-2px);
            text-decoration: none;
            color: #1a1a1a;
        }
        
        .mode-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        
        .mode-description {
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
        }
        
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .dashboard-container {
                padding: 20px;
            }
        }
        
        h2 {
            color: #5a645a;
            font-size: 2.5em;
            margin-bottom: 10px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            border-radius: 4px;
        }
        
        .welcome-text {
            text-align: center;
            color: #5a5a5a;
            margin-bottom: 40px;
            font-style: italic;
        }
        
        .profile-link {
            color: #8a9d8a;
            text-decoration: none;
            font-weight: bold;
            border-bottom: 1px solid transparent;
            transition: all 0.3s ease;
        }
        
        .profile-link:hover {
            color: #6d7a6d;
            border-bottom-color: #8a9d8a;
            text-decoration: none;
        }
        
        .username-link {
            color: #8a9d8a;
            text-decoration: none;
            font-weight: normal;
            transition: color 0.3s ease;
        }
        
        .username-link:hover {
            color: #6d7a6d;
            text-decoration: underline;
        }
        
        .nav-footer-link {
            color: #7a8d7a;
            text-decoration: none;
            font-size: 0.9em;
            margin-right: 18px;
            font-weight: normal;
            transition: font-weight 0.3s ease;
        }
        
        .nav-footer-link:hover {
            font-weight: bold;
            text-decoration: none;
        }
        
        .nav-footer-link:last-child {
            margin-right: 0;
        }
        
        h3 {
            color: #1a1a1a;
            font-size: 1.3em;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: bold;
            margin: 30px 0 15px 0;
            border-bottom: 1px solid #e0d6c7;
            padding-bottom: 8px;
        }
        
        .button-group {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: normal;
            transition: all 0.3s ease;
            font-family: 'Georgia', 'Times New Roman', serif;
            border: 2px solid transparent;
        }
        
        .btn-primary {
            background: #1a1a1a;
            color: #faf8f3;
        }
        
        .btn-primary:hover {
            background: #333;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #ffffff;
            color: #1a1a1a;
            border: 2px solid #e0d6c7;
        }
        
        .btn-secondary:hover {
            border-color: #1a1a1a;
            transform: translateY(-1px);
        }
        
        .btn-tertiary {
            background: #666;
            color: #faf8f3;
        }
        
        .btn-tertiary:hover {
            background: #888;
        }
        
        .dev-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0d6c7;
        }
        
        .dev-info {
            font-size: 0.9em;
            color: #888;
            margin: 10px 0;
        }
        
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .resume-game-section {
            background: #f0f8ff;
            border: 2px solid #4a90e2;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .game-card {
            background: #ffffff;
            border: 1px solid #e0d6c7;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
            text-align: left;
            transition: box-shadow 0.3s ease;
        }
        
        .game-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .game-meta {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }
        
        .manual-entry {
            margin-top: 15px;
            padding: 15px;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .form-group {
            margin: 10px 0;
        }
        
        .form-input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            width: 150px;
        }
        
        .form-button {
            padding: 8px 16px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .form-button:hover {
            background: #357abd;
        }
    </style>
</head>
<body>
        <div class="dashboard-container">
        <h2>
            <img src="img/icon.jpg" alt="Intrachange" class="logo-icon">
            Intrachange Dashboard
        </h2>
        <div class="welcome-text">
            Welcome back, 
            <a href="player_profile.php?id=<?php echo $user_id; ?>" class="profile-link"><?php echo htmlspecialchars($username); ?></a>!
        </div>
        
        <div class="dashboard-layout">
            <!-- Left Column: New Games & Tutorial -->
            <div class="left-column">
                <h3 class="section-title">Start Playing</h3>
                
                <div class="new-game-options">
                    <!-- Single Player -->
                    <a href="new_game.php?mode=singleplayer" class="mode-card">
                        <div class="mode-title">Single Player</div>
                        <div class="mode-description">
                            Control both White and Black pieces.<br>
                            Perfect for exploring strategies and contemplation.
                        </div>
                    </a>
                    
                    <!-- Two Players -->
                    <a href="new_game.php?mode=twoplayer" class="mode-card">
                        <div class="mode-title">Two Players</div>
                        <div class="mode-description">
                            Start a new game and send a registered<br>
                            player an email invitation.
                        </div>
                    </a>
                    
                    <!-- Tell Your Friends -->
                    <a href="invite_friends.php" class="mode-card">
                        <div class="mode-title">Tell Your Friends</div>
                        <div class="mode-description">
                            Send an email invitation to join<br>
                            Intrachange.
                        </div>
                    </a>
                    
                    <!-- Learn to Play -->
                    <a href="tutorial/index.html" class="mode-card">
                        <div class="mode-title">Learn to Play</div>
                        <div class="mode-description">
                            Delve into ancient logic behind Intrachange.<br>
                            Lessons from Initiate to Master levels.
                        </div>
                    </a>
                </div>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0d6c7; text-align: center;">
                    <a href="player_profile.php?id=<?php echo $user_id; ?>" class="nav-footer-link">
                        MY PROFILE
                    </a>
                    <a href="about.php" class="nav-footer-link">
                        ABOUT
                    </a>
                    <a href="archive_display.html" class="nav-footer-link">
                        GAME ARCHIVE
                    </a>
                    <a href="logout.php" class="nav-footer-link">
                        LOG OUT
                    </a>
                    <a href="dashboard.php" class="nav-footer-link">
                        REFRESH DASHBOARD
                    </a>
                </div>
            </div>
            
            <!-- Right Column: Active Games -->
            <div class="right-column">
                <h3 class="section-title">Your Active Games</h3>
                
                <?php if (empty($active_games)): ?>
                <p style="text-align: center; color: #666; font-style: italic; margin: 40px 0;">
                    No active games yet.<br>
                    Start a new game to begin playing!
                </p>
                <?php else: ?>
                <div class="games-list">
                    <?php foreach ($active_games as $game): ?>
                    <div class="game-card">
                        <strong>Game #<?php echo $game['id']; ?></strong>
                        <div class="game-meta">
                            Opponent: <strong><a href="player_profile.php?user=<?php echo urlencode($game['opponent_username']); ?>" class="username-link"><?php echo htmlspecialchars($game['opponent_username']); ?></a></strong>
                        </div>
                        <div class="game-meta">
                            White: <a href="player_profile.php?user=<?php echo urlencode($game['white_username']); ?>" class="username-link"><?php echo htmlspecialchars($game['white_username']); ?></a> | 
                            Black: <a href="player_profile.php?user=<?php echo urlencode($game['black_username']); ?>" class="username-link"><?php echo htmlspecialchars($game['black_username']); ?></a>
                        </div>
                        <div class="game-meta">
                            Moves: <?php echo $game['move_count']; ?> | 
                            Last activity: <?php echo date('M j, Y g:i A', strtotime($game['last_move_time'])); ?>
                        </div>
                        <div class="game-meta">
                            <?php 
                            $your_role = ($game['white_player_id'] == $user_id) ? 'White' : 'Black';
                            $current_turn = $game['current_turn'];
                            $is_your_turn = (($current_turn === 'white' && $game['white_player_id'] == $user_id) || 
                                            ($current_turn === 'black' && $game['black_player_id'] == $user_id));
                            echo "You are playing as: <strong>$your_role</strong> | ";
                            echo $is_your_turn ? "<strong style='color: #d32f2f;'>Your Turn!</strong>" : "Waiting for opponent";
                            ?>
                        </div>
                        <a href="game.php?game_id=<?php echo $game['id']; ?>" class="button-link" style="margin-top: 10px; display: inline-block;">
                            Resume Game
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Manual Game Entry -->
                <div class="resume-game-section">
                    <h4 style="margin-top: 0; color: #2c5aa0;">Join Game by ID</h4>
                    <form action="game.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" name="game_id" placeholder="Enter Game ID" required 
                               style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" class="button-link">Join</button>
                    </form>
                    <div class="dev-info">
                        Enter any game ID to join or resume a specific game.
                    </div>
                </div>
            </div>
        </div>
</body>
</html>
