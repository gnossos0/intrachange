<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$game_id = $_GET['game_id'] ?? null;

if (!$game_id) {
    die('Game ID is required');
}

require_once __DIR__ . '/backend/db_connect.php';

// Get game details
$game_stmt = $conn->prepare("
    SELECT g.*, 
           u1.username as white_username, u2.username as black_username
    FROM games g
    LEFT JOIN users u1 ON g.white_player_id = u1.id
    LEFT JOIN users u2 ON g.black_player_id = u2.id
    WHERE g.id = ?
");
$game_stmt->bind_param("i", $game_id);
$game_stmt->execute();
$game_result = $game_stmt->get_result();
$game = $game_result->fetch_assoc();

if (!$game) {
    die('Game not found');
}

// Get all moves for this game
$moves_stmt = $conn->prepare("
    SELECT * 
    FROM moves 
    WHERE game_id = ? 
    ORDER BY move_number ASC
");
$moves_stmt->bind_param("i", $game_id);
$moves_stmt->execute();
$moves_result = $moves_stmt->get_result();

$moves = [];
while ($move = $moves_result->fetch_assoc()) {
    $moves[] = $move;
}

$moves_stmt->close();
$game_stmt->close();
$conn->close();

// Determine if current user can make moves
$is_player = ($game['white_player_id'] == $user_id || $game['black_player_id'] == $user_id);
$can_move = $is_player && $game['status'] == 'active' && !$game['archived'] && !$game['deleted'];
$your_role = '';
if ($game['white_player_id'] == $user_id) {
    $your_role = 'white';
} elseif ($game['black_player_id'] == $user_id) {
    $your_role = 'black';
}

// Check whose turn it is
$is_your_turn = false;
if ($can_move) {
    $is_your_turn = (($game['current_turn'] == 'white' && $your_role == 'white') || 
                     ($game['current_turn'] == 'black' && $your_role == 'black'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Game #<?php echo $game_id; ?> - Intrachange</title>
    <link rel="stylesheet" href="frontend/style.css">
    <style>
        .game-header {
            background: #f0f8ff;
            border: 2px solid #4a90e2;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            text-align: center;
            font-family: 'Georgia', serif;
        }
        
        .game-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .player-info {
            flex: 1;
            min-width: 200px;
        }
        
        .status-info {
            color: #666;
            font-size: 0.9em;
        }
        
        .turn-indicator {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            display: inline-block;
        }
        
        .your-turn {
            background: #28a745;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .waiting {
            background: #ffc107;
            color: #333;
        }
        
        .spectator {
            background: #6c757d;
            color: white;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .loading-message {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px;
        }
        
        /* Chessboard Styles */
        #gameContainer {
            display: flex;
            gap: 20px;
            margin: 20px;
            max-width: 1200px;
        }
        
        #chessboard {
            border-collapse: collapse;
            border: 2px solid #333;
            position: relative;
        }
        
        #chessboard td.square {
            width: 60px;
            height: 60px;
            position: relative;
            text-align: center;
            vertical-align: middle;
            font-size: 40px;
            cursor: pointer;
            border: none;
        }
        
        #chessboard td.light {
            background-color: #f0d9b5;
        }
        
        #chessboard td.dark {
            background-color: #b58863;
        }
        
        #chessboard td.highlight {
            background-color: #ffff99 !important;
        }
        
        #chessboard td.highlight-legal {
            background-color: #90EE90 !important;
        }
        
        #chessboard td.highlight-capture {
            background-color: #FFB6C1 !important;
        }
        
        .coord-label {
            position: absolute;
            top: 2px;
            left: 2px;
            font-size: 10px;
            color: #666;
            font-weight: bold;
        }
        
        .hexNum {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 9px;
            color: #333;
            background: rgba(255,255,255,0.7);
            border-radius: 2px;
            padding: 1px 2px;
        }
        
        .sidebar {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="game-header">
        <h1>🎮 Resume Game #<?php echo $game_id; ?></h1>
        
        <div class="game-info">
            <div class="player-info">
                <strong><?php echo htmlspecialchars($game['white_username']); ?></strong> (White)
                <?php if ($your_role == 'white'): ?>
                    <span style="color: #28a745;">← You</span>
                <?php endif; ?>
            </div>
            
            <div>VS</div>
            
            <div class="player-info">
                <strong><?php echo htmlspecialchars($game['black_username']); ?></strong> (Black)
                <?php if ($your_role == 'black'): ?>
                    <span style="color: #28a745;">← You</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="status-info">
            Game Status: <?php echo ucfirst($game['status']); ?> • 
            Moves: <?php echo count($moves); ?> • 
            
            <?php if ($can_move): ?>
                <?php if ($is_your_turn): ?>
                    <span class="turn-indicator your-turn">🎯 Your Turn!</span>
                <?php else: ?>
                    <span class="turn-indicator waiting">⏳ Waiting for opponent</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="turn-indicator spectator">👀 Spectating</span>
            <?php endif; ?>
        </div>
        
        <?php if ($game['archived']): ?>
            <div style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;">
                📚 This game is archived
            </div>
        <?php endif; ?>
    </div>

    <div class="loading-message">
        🔄 Reconstructing game from move history...
    </div>

    <!-- Game board container (will be populated by frontend) -->
    <div id="gameContainer">
        <!-- Include the full frontend structure with proper chessboard table -->
        <table id="chessboard">
            <?php for ($row = 0; $row < 8; $row++): ?>
                <tr>
                    <?php for ($col = 0; $col < 8; $col++): ?>
                        <?php 
                        $isLight = ($row + $col) % 2 == 0;
                        $squareClass = $isLight ? 'light' : 'dark';
                        ?>
                        <td class="square <?php echo $squareClass; ?>" id="square-<?php echo $row; ?>-<?php echo $col; ?>">
                            <!-- Pieces will be populated by JavaScript -->
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
        
        <div id="sidebar" class="sidebar">
            <div id="moveHistory"></div>
            <div id="commentSection"></div>
        </div>
    </div>

    <!-- Include the frontend modules -->
    <script type="module">
        // Pass game data to frontend
        window.INTRACHANGE_RESUME_DATA = {
            game_id: <?php echo $game_id; ?>,
            game: <?php echo json_encode($game); ?>,
            moves: <?php echo json_encode($moves); ?>,
            user: {
                id: <?php echo $user_id; ?>,
                role: '<?php echo $your_role; ?>',
                can_move: <?php echo $can_move ? 'true' : 'false'; ?>,
                is_your_turn: <?php echo $is_your_turn ? 'true' : 'false'; ?>
            }
        };
        
        console.log('🎮 Resume data loaded:', window.INTRACHANGE_RESUME_DATA);
        
        // Set global state for compatibility
        window.INTRACHANGE_STATE = {
            game_id: <?php echo $game_id; ?>,
            mode: 'resume',
            moves: <?php echo json_encode($moves); ?>
        };
        
        // Import and initialize the board controller with resume data
        import('./frontend/boardController.mjs').then(module => {
            console.log('📋 Board controller loaded, initializing resume mode...');
            
            // Hide loading message
            document.querySelector('.loading-message').style.display = 'none';
            
            // Call resume function
            if (module.resumeGame) {
                module.resumeGame(window.INTRACHANGE_RESUME_DATA);
            } else {
                // Fallback: direct reconstruction
                module.reconstructBoardFromMoves(<?php echo $game_id; ?>)
                    .then(result => {
                        console.log('� Board reconstructed via fallback:', result);
                    })
                    .catch(error => {
                        console.error('❌ Fallback reconstruction failed:', error);
                    });
            }
        }).catch(error => {
            console.error('❌ Error loading board controller:', error);
            document.querySelector('.loading-message').innerHTML = 
                '<div class="error-message">❌ Error loading game: ' + error.message + '</div>';
        });
    </script>
    
    <!-- Load the main frontend -->
    <script type="module" src="frontend/script.mjs"></script>
</body>
</html>
