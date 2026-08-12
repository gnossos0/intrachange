<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    require_once __DIR__ . '/backend/db_connect.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Determine which profile to show
$viewing_user_id = $_SESSION['user_id']; // Default to current user
$viewing_username = $_SESSION['username'];
$is_own_profile = true;

// If no specific user is requested, redirect to own profile with ID
if (!isset($_GET['user']) && !isset($_GET['id'])) {
    header("Location: player_profile.php?id=" . $_SESSION['user_id']);
    exit();
}

// Check if viewing another user's profile
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $requested_username = trim($_GET['user']);
    
    // Look up the requested user
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ?");
    $stmt->bind_param("s", $requested_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $viewing_user_id = $row['id'];
        $viewing_username = $row['username'];
        $is_own_profile = ($viewing_user_id == $_SESSION['user_id']);
    } else {
        // User not found, show current user's profile with error message
        $profile_error = "Player '$requested_username' not found. Showing your profile instead.";
    }
    $stmt->close();
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $requested_id = (int)$_GET['id'];
    
    // Look up the requested user by ID
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $requested_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $viewing_user_id = $row['id'];
        $viewing_username = $row['username'];
        $is_own_profile = ($viewing_user_id == $_SESSION['user_id']);
    } else {
        // User not found, show current user's profile with error message
        $profile_error = "Player with ID $requested_id not found. Showing your profile instead.";
    }
    $stmt->close();
}

// Get player statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_games,
        SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) as wins,
        SUM(CASE WHEN status = 'completed' AND winner_id IS NULL THEN 1 ELSE 0 END) as draws,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_games,
        MIN(created_at) as first_game_date,
        MAX(updated_at) as last_activity
    FROM games 
    WHERE (white_player_id = ? OR black_player_id = ?) AND status != 'abandoned'
");
$stmt->bind_param("iii", $viewing_user_id, $viewing_user_id, $viewing_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user profile information
$stmt = $conn->prepare("
    SELECT username, email, display_name, bio, signature, avatar, created_at 
    FROM users 
    WHERE id = ?
");
$stmt->bind_param("i", $viewing_user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate losses
$losses = $stats['total_games'] - $stats['wins'] - $stats['draws'] - $stats['active_games'];

// Get recent games
$stmt = $conn->prepare("
    SELECT g.id, g.white_player_id, g.black_player_id, g.status, g.created_at, g.updated_at,
           u1.username as white_username, u2.username as black_username,
           g.winner_id
    FROM games g
    LEFT JOIN users u1 ON g.white_player_id = u1.id
    LEFT JOIN users u2 ON g.black_player_id = u2.id
    WHERE (g.white_player_id = ? OR g.black_player_id = ?) 
    AND g.status != 'abandoned'
    ORDER BY g.updated_at DESC
    LIMIT 10
");
$stmt->bind_param("ii", $viewing_user_id, $viewing_user_id);
$stmt->execute();
$recent_games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Determine display name and avatar
$display_name = !empty($user_profile['display_name']) ? $user_profile['display_name'] : $viewing_username;
$has_avatar = !empty($user_profile['avatar']) && file_exists($user_profile['avatar']);
$avatar_initial = strtoupper(substr($display_name, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_own_profile ? 'My Profile' : htmlspecialchars($viewing_username) . "'s Profile"; ?> - Intrachange</title>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            color: #4a554a;
            line-height: 1.5;
        }

        <?php if (isset($profile_error)): ?>
        .error-message {
            background: #ffeaa7;
            border: 1px solid #fdcb6e;
            color: #e17055;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        <?php endif; ?>

        .profile-header {
            background: #fafcfa;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(90,100,90,0.1);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 30px;
            align-items: start;
        }

        .avatar-section {
            text-align: center;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8a9d8a 0%, #7a8d7a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(90,100,90,0.2);
        }

        .profile-info h1 {
            color: #5a645a;
            margin: 0 0 10px 0;
            font-size: 2.2rem;
            font-weight: bold;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .profile-info .member-since {
            color: #6a746a;
            font-style: italic;
            margin-bottom: 20px;
        }

        .stats-panel {
            background: #f5f8f5;
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #8a9d8a;
            height: fit-content;
        }

        .stats-panel h2 {
            color: #5a645a;
            margin: 0 0 20px 0;
            font-size: 1.5rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e8ece8;
        }

        .stat-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .stat-label {
            color: #6a746a;
            font-weight: 500;
        }

        .stat-value {
            color: #4a554a;
            font-weight: 600;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .recent-games-panel {
            background: #fafcfa;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(90,100,90,0.1);
        }

        .recent-games-panel h2 {
            color: #5a645a;
            margin: 0 0 20px 0;
            font-size: 1.5rem;
        }

        .game-list {
            display: grid;
            gap: 15px;
        }

        .game-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f5f8f5;
            border-radius: 8px;
            border-left: 4px solid #8a9d8a;
        }

        .game-info {
            flex: 1;
        }

        .game-opponents {
            font-weight: 600;
            color: #4a554a;
            margin-bottom: 5px;
        }

        .game-meta {
            color: #6a746a;
            font-size: 0.9rem;
        }

        .game-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .back-to-dashboard {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            background-color: #8a9d8a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 5px;
        }

        .btn:hover {
            background-color: #7a8d7a;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(90,100,90,0.2);
        }

        .btn-secondary {
            background-color: #6a746a;
        }

        .btn-secondary:hover {
            background-color: #5a645a;
        }

        .profile-actions {
            margin-top: 15px;
        }

        .username-link {
            color: #8a9d8a;
            text-decoration: none;
            font-weight: 600;
        }

        .username-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php if (isset($profile_error)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($profile_error); ?>
    </div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="avatar-section">
            <div class="avatar">
                <?php if ($has_avatar): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['avatar']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo $avatar_initial; ?>
                <?php endif; ?>
            </div>
            <?php if ($is_own_profile): ?>
            <div class="profile-actions">
                <a href="edit_profile.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 16px; text-decoration: none;">Edit Profile</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <h1><?php echo htmlspecialchars($display_name); ?></h1>
            <?php if ($display_name !== $viewing_username): ?>
            <p style="color: #666; font-size: 0.9rem; margin: -0.5rem 0 1rem 0;">@<?php echo htmlspecialchars($viewing_username); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($user_profile['signature'])): ?>
            <p class="signature" style="font-style: italic; color: #555; margin-bottom: 1rem; font-size: 0.95rem;">
                "<?php echo htmlspecialchars($user_profile['signature']); ?>"
            </p>
            <?php endif; ?>
            
            <p class="member-since">
                <?php if ($is_own_profile): ?>
                    Member since <?php echo date('F Y', strtotime($user_profile['created_at'])); ?>
                <?php else: ?>
                    Playing since <?php echo $stats['first_game_date'] ? date('F Y', strtotime($stats['first_game_date'])) : date('F Y', strtotime($user_profile['created_at'])); ?>
                <?php endif; ?>
            </p>
            <?php if ($stats['last_activity']): ?>
            <p style="color: #6a746a; font-size: 0.9rem;">
                Last activity: <?php echo date('M j, Y', strtotime($stats['last_activity'])); ?>
            </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($user_profile['bio'])): ?>
        <div class="bio-section" style="grid-column: 1 / -1; margin: 1rem 0; padding: 1.5rem; background: rgba(255, 255, 255, 0.9); border-radius: 10px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);">
            <h3 style="margin-bottom: 1rem; color: #2c3e50; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: bold;">About <?php echo $is_own_profile ? 'Me' : htmlspecialchars($display_name); ?></h3>
            <div style="line-height: 1.6; color: #444; white-space: pre-wrap;"><?php echo htmlspecialchars($user_profile['bio']); ?></div>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="recent-games-panel">
                <h2>Recent Games</h2>
                <?php if (!empty($recent_games)): ?>
                <div class="game-list">
                    <?php foreach ($recent_games as $game): ?>
                    <div class="game-item">
                        <div class="game-info">
                    <div class="game-opponents">
                        <?php 
                        if ($game['white_player_id'] == $viewing_user_id) {
                            echo htmlspecialchars($viewing_username) . ' vs ';
                            echo '<a href="player_profile.php?user=' . urlencode($game['black_username']) . '" class="username-link">' . htmlspecialchars($game['black_username']) . '</a>';
                        } else {
                            echo '<a href="player_profile.php?user=' . urlencode($game['white_username']) . '" class="username-link">' . htmlspecialchars($game['white_username']) . '</a>';
                            echo ' vs ' . htmlspecialchars($viewing_username);
                        }
                        ?>
                    </div>
                    <div class="game-meta">
                        Game #<?php echo $game['id']; ?> • 
                        <?php echo date('M j, Y', strtotime($game['created_at'])); ?>
                        <?php if ($game['status'] == 'completed'): ?>
                            • 
                            <?php 
                            if ($game['winner_id'] == $viewing_user_id) {
                                echo 'Won';
                            } elseif ($game['winner_id'] && $game['winner_id'] != $viewing_user_id) {
                                echo 'Lost';
                            } else {
                                echo 'Draw';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="game-status <?php echo $game['status'] == 'active' ? 'status-active' : 'status-completed'; ?>">
                    <?php echo ucfirst($game['status']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color: #666; font-style: italic;">No games played yet.</p>
        <?php endif; ?>
    </div>

    <div class="stats-panel">
        <h2>Game Statistics</h2>
        <div class="stat-item">
            <span class="stat-label">Total Games</span>
            <span class="stat-value"><?php echo $stats['total_games']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Wins</span>
            <span class="stat-value"><?php echo $stats['wins']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Losses</span>
            <span class="stat-value"><?php echo $losses; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Draws</span>
            <span class="stat-value"><?php echo $stats['draws']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Active Games</span>
            <span class="stat-value"><?php echo $stats['active_games']; ?></span>
        </div>
        <?php if ($stats['total_games'] > 0): ?>
        <div class="stat-item">
            <span class="stat-label">Win Rate</span>
            <span class="stat-value"><?php echo round(($stats['wins'] / $stats['total_games']) * 100, 1); ?>%</span>
        </div>
        <?php endif; ?>
    </div>
</div>

    <div class="back-to-dashboard" style="text-align: center; margin-top: 3rem; padding: 2rem 0;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
        <?php if (!$is_own_profile): ?>
        <a href="player_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-secondary" style="margin-left: 1rem;">View My Profile</a>
        <?php endif; ?>
    </div>
</body>
</html>