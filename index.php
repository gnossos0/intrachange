<?php
// Simple landing page for the Intrachange project
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intrachange Chess/I Ching Game</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .link-button { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 10px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .link-button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🏛️ Intrachange Chess/I Ching Game</h1>
    <p>Welcome to the Intrachange chess game with I Ching hexagram overlays and move commentary system.</p>
    
    <h2>Available Pages:</h2>
    <a href="dashboard.php" class="link-button">🏠 Player Dashboard</a>
    <a href="resume_game.php?game_id=1" class="link-button">🎮 Resume Game #1</a>
    <a href="test_reconstruction.html" class="link-button">🧪 Test Reconstruction</a>
    <a href="frontend/" class="link-button">🎯 Main Game (Frontend)</a>
    <a href="chessboard.php" class="link-button">🧪 PHP Test Page</a>
    
    <h2>Development Info:</h2>
    <ul>
        <li><strong>Server:</strong> PHP <?php echo phpversion(); ?></li>
        <li><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
        <li><strong>Project:</strong> Chess with I Ching hexagrams and sidebar commentary</li>
    </ul>
    
    <h2>Features:</h2>
    <ul>
        <li>✅ Interactive chessboard with piece movement</li>
        <li>✅ I Ching hexagram overlays (toggle-able)</li>
        <li>✅ Right sidebar for move commentary</li>
        <li>✅ Move history with hexagram information</li>
        <li>✅ Single-player practice mode</li>
        <li>🔧 Database integration (in progress)</li>
    </ul>
</body>
</html>
