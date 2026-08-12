<?php
// Unified game page with chessboard and comment system
session_start();

// Require login; preserve intended return to this game if provided
if (!isset($_SESSION['user_id'])) {
    $next = 'game.php';
    if (!empty($_GET['game_id'])) {
        $next .= '?game_id=' . urlencode($_GET['game_id']);
    }
    header('Location: login.php?next=' . urlencode($next));
    exit();
}

// Determine game id: prefer explicit GET param; fall back to session if available
$selected_game_id = isset($_GET['game_id']) ? $_GET['game_id'] : (
    isset($_SESSION['current_game_id']) ? $_SESSION['current_game_id'] : null
);

if (empty($selected_game_id)) {
    // No game specified; send user to dashboard/selection
    header('Location: dashboard.php');
    exit();
}

// Use session user id only (do not take user id from GET)
$user_id = (int)$_SESSION['user_id'];

// Store in session for consistency
$_SESSION['current_game_id'] = (int)$selected_game_id;

// Fetch game data
$moves = [];
$current_game = [];

try {
    require_once 'backend/db_connect.php';
    
    // Get current game state using mysqli
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $selected_game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_game = $result->fetch_assoc() ?: [];
        $stmt->close();
    }
    
    // Get move history using mysqli
    $stmt = $conn->prepare("SELECT * FROM moves WHERE game_id = ? ORDER BY move_number ASC");
    if ($stmt) {
        $stmt->bind_param("i", $selected_game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $moves = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON position data
            if ($row['from_square']) {
                $row['from_square'] = json_decode($row['from_square'], true);
            }
            if ($row['to_square']) {
                $row['to_square'] = json_decode($row['to_square'], true);
            }
            $moves[] = $row;
        }
        $stmt->close();
        
        // Debug logging
        error_log("DEBUG: User $user_id fetched " . count($moves) . " moves at " . date('Y-m-d H:i:s'));
        if (count($moves) > 0) {
            $last_move = end($moves);
            error_log("DEBUG: Last move is #" . $last_move['move_number'] . " (" . $last_move['chess_coordinates'] . ")");
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    // Fallback if database connection fails
    error_log("Database error in game.php: " . $e->getMessage());
    $current_game = ['next_player_id' => 1, 'game_mode' => 'multiplayer'];
    $moves = [];
}

// Determine whose turn it is based on move count and game players
// Get white and black player IDs from the current game with robust normalization
// Treat empty string or 0 as null; otherwise cast to int
$white_player_id = (isset($current_game['white_player_id']) && $current_game['white_player_id'] !== '' && (int)$current_game['white_player_id'] !== 0)
    ? (int)$current_game['white_player_id']
    : null;
$black_player_id = (isset($current_game['black_player_id']) && $current_game['black_player_id'] !== '' && (int)$current_game['black_player_id'] !== 0)
    ? (int)$current_game['black_player_id']
    : null;
$current_turn_db = isset($current_game['current_turn']) ? $current_game['current_turn'] : 'white'; // Step 3: Get from database

// Check if this is a singleplayer game (use database value)
$is_singleplayer = isset($current_game['is_singleplayer']) ? $current_game['is_singleplayer'] : false;

$move_count = count($moves);

// Debug logging
error_log("DEBUG: Game $selected_game_id - Move count: $move_count, Is singleplayer: " . ($is_singleplayer ? 'true' : 'false'));
error_log("DEBUG: White player: $white_player_id, Black player: $black_player_id, Current user: $user_id");

if ($is_singleplayer) {
    // In singleplayer mode, the user can always move and alternates colors
    $current_turn_color = ($move_count % 2 === 0) ? 'White' : 'Black';
    $current_turn_user_id = $user_id; // Always the same user
    $is_my_turn = true; // Always your turn in singleplayer
    error_log("DEBUG: Singleplayer - Current turn color: $current_turn_color");
} else {
    // Step 3: Use database current_turn for multiplayer
    $current_turn_user_id = ($current_turn_db == 'white') ? $white_player_id : $black_player_id;
    $current_turn_color = ($current_turn_db == 'white') ? 'White' : 'Black';
    $is_my_turn = ($user_id == $current_turn_user_id);
    error_log("DEBUG: Multiplayer - Current turn: $current_turn_db, Current turn user: $current_turn_user_id, Current turn color: $current_turn_color");
}

// Determine user's color based on game assignment
    // Initialize to empty to avoid undefined variable notices
    $my_color = '';
if ($is_singleplayer) {
    // In singleplayer, show current turn color (the color you're about to play)
    $my_color = $current_turn_color;
} else {
    // Deterministic assignment only; do not guess based on turn
    error_log("DEBUG: Comparing user_id=$user_id (int " . (int)$user_id . ") with white=$white_player_id, black=$black_player_id");
    if ($white_player_id !== null && (int)$user_id === (int)$white_player_id) {
        $my_color = 'White';
    } elseif ($black_player_id !== null && (int)$user_id === (int)$black_player_id) {
        $my_color = 'Black';
    } else {
        // Fallback only if one slot is empty
        if ($white_player_id === null) {
            $my_color = 'White';
        } elseif ($black_player_id === null) {
            $my_color = 'Black';
        } else {
            // $my_color = 'Spectator';
            error_log("DEBUG: No color match for user_id=$user_id; both white and black are assigned. Possible session mismatch.");
        }
    }
}

// Prepare game state for JavaScript
$game_state = [
    'game_id' => (int)$selected_game_id,
    'moves' => $moves,
    'current_player' => $current_turn_user_id,
    'user_id' => (int)$user_id,
    'is_my_turn' => $is_my_turn,
    'current_turn_color' => $current_turn_color,
    'current_turn_db' => $current_turn_db, // Step 3: Add database turn state
    'my_color' => $my_color,
    'is_singleplayer' => $is_singleplayer,
    'white_player_id' => $white_player_id, // Step 3: Add for turn validation
    'black_player_id' => $black_player_id, // Step 3: Add for turn validation
    'board' => [] // Board state will be managed by JavaScript
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Intrachange - Chess with I Ching Commentary</title>
    <style>
        /* Reset and base styles */
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif !important;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif !important;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            color: #2c2c2c;
            height: 100vh;
            overflow: auto;
        }

        /* Main layout container */
        #main-container {
            display: flex;
            min-height: 100vh;
            gap: 20px;
            padding: 20px;
            align-items: flex-start;
        }

        /* Game board area */
        #game-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Header */
        .game-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 25px;
            border-bottom: 2px solid #e0d6c7;
            margin-bottom: 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .header-left h1 {
            margin: 0;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            color: #666;
            font-size: 14px;
        }
        
        .nav-link {
            color: #1a1a1a;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .nav-link:hover {
            background-color: #f0f0f0;
            text-decoration: none;
        }
        
        .profile-nav-link {
            color: #8a9d8a;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .profile-nav-link:hover {
            color: #6d7a6d;
            text-decoration: underline;
        }

        .game-header h1 {
            margin: 0 0 10px 0;
            color: #1a1a1a;
            font-size: 2.5em;
            font-weight: normal;
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

        .game-controls {
            margin-bottom: 15px;
        }

        .turn-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .player-info {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .turn-indicator {
            font-size: 16px;
            font-weight: bold;
        }

        .toggle-hexagrams {
            font-size: 16px;
            padding: 8px 12px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-hexagrams:hover {
            background-color: #2c3e50;
        }

        #toggleHexagrams {
            margin-right: 8px;
            transform: scale(1.2);
        }

        /* Chessboard container */
        #chessboard-container {
            position: relative;
            display: inline-block;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        /* Chessboard styles */
        table#chessboard {
            border-collapse: collapse;
            width: 480px;
            height: 480px;
            table-layout: fixed;
            background: #fff;
        }

        #chessboard td {
            width: 60px;
            height: 60px;
            position: relative;
            border: 1px solid #333;
            text-align: center;
            vertical-align: middle;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 11px;
            line-height: 1.2;
            padding: 4px;
            cursor: pointer;
            user-select: none;
            background-color: #f0d9b5;
            color: #000;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        #chessboard td.dark {
            background-color: #b58863;
            color: #fff;
        }

        #chessboard td:hover {
            transform: scale(1.02);
            z-index: 10;
        }

        /* Coordinate labels */
        .coord-label {
            position: absolute;
            top: 2px;
            left: 2px;
            font-size: 9px;
            color: rgba(0,0,0,0.6);
            user-select: none;
            z-index: 12;
            font-weight: bold;
        }

        /* Hexagram elements */
        .hexNum {
            position: absolute;
            top: 2px;
            right: 2px;
            font-weight: bold;
            font-size: 12px;
            color: rgba(0,0,0,0.8);
            z-index: 12;
        }

        .hex-keyword {
            position: absolute;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 9px;
            font-style: italic;
            line-height: 1.1;
            user-select: none;
            pointer-events: none;
            color: rgba(0,0,0,0.7);
            z-index: 11;
            text-align: center;
            width: 100%;
        }

        .hex-img {
            position: static;
            height: 40px;
            width: auto;
            pointer-events: none;
            opacity: 0.8;
        }

        /* Chess pieces */
        .piece {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2.5em;
            line-height: 1;
            user-select: none;
            z-index: 15;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .white-piece {
            color: #ffffff !important;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        }

        .black-piece {
            color: #000000 !important;
            text-shadow: 1px 1px 3px rgba(255,255,255,0.3);
        }

        /* Highlight styles */
        .highlight {
            background-color: transparent !important;
            box-shadow: none !important;
            z-index: 21;
        }

        .highlight-legal {
            background-color: rgba(144, 238, 144, 0.7) !important;
            border: 2px solid #32cd32 !important;
            box-shadow: 0 0 8px rgba(144, 238, 144, 0.8);
            z-index: 20;
        }

        .highlight-capture {
            background-color: rgba(255, 99, 71, 0.7) !important;
            border: 3px solid #ff0000 !important;
            box-shadow: 0 0 8px rgba(255, 99, 71, 0.8);
            z-index: 20;
        }

        .highlight-esoteric {
            background-color: rgba(255, 179, 102, 0.8) !important;
            border: 2px solid #e67c30 !important;
            box-shadow: 0 0 8px rgba(255, 179, 102, 0.8);
            z-index: 22;
        }

        /* Comment Sidebar */
        #commentSidebar {
            width: 350px;
            max-width: 350px;
            min-width: 50px;
            border: 2px solid #34495e;
            border-radius: 12px;
            padding: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            transition: width 0.3s ease, padding 0.3s ease;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            height: fit-content;
            max-height: calc(100vh - 40px);
            position: sticky;
            top: 20px;
            flex-shrink: 0;
        }

        #commentSidebar.collapsed {
            width: 50px;
            padding: 10px 5px;
        }

        #commentSidebar.collapsed > *:not(#toggleSidebar) {
            display: none;
        }

        /* Toggle button */
        #toggleSidebar {
            display: block;
            margin-bottom: 15px;
            cursor: pointer;
            font-weight: bold;
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: #3498db;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        #toggleSidebar:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        /* Current move info */
        #currentMoveInfo {
            margin-bottom: 20px;
            padding: 12px;
            background-color: #ecf0f1;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        #currentMoveInfo .move-coords {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        #currentMoveInfo .move-hexagrams {
            font-size: 14px;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Comment form */
        #commentForm {
            margin-bottom: 20px;
        }

        #commentForm textarea {
            width: 100%;
            height: 80px;
            resize: vertical;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        #commentForm textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        #commentForm button {
            margin-right: 8px;
            padding: 8px 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #saveComment {
            background-color: #27ae60;
            color: white;
        }

        #saveComment:hover {
            background-color: #229954;
            transform: translateY(-1px);
        }

        #clearComment {
            background-color: #95a5a6;
            color: white;
        }

        #clearComment:hover {
            background-color: #7f8c8d;
        }

        /* Move history */
        #moveHistory {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #bdc3c7 #ecf0f1;
        }

        #moveHistory::-webkit-scrollbar {
            width: 6px;
        }

        #moveHistory::-webkit-scrollbar-track {
            background: #ecf0f1;
            border-radius: 3px;
        }

        #moveHistory::-webkit-scrollbar-thumb {
            background: #bdc3c7;
            border-radius: 3px;
        }

        .move-card {
            border: 1px solid #d5dbdb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            background-color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .move-card:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .move-card.selected {
            background-color: #d5e8ff;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .move-card.latest-move {
            border-color: #27ae60;
            background: linear-gradient(135deg, #f0fff4 0%, #e8f8f5 100%);
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.2);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .move-readout {
            font-family: 'Courier New', monospace;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            padding: 8px;
        }

        .move-piece-hex {
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .move-coordinates {
            font-size: 12px;
            color: #7f8c8d;
            font-style: italic;
        }

        .move-card-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
            color: #7f8c8d;
        }

        .move-notation {
            font-weight: bold;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 3px;
        }

        .move-hexagram-info {
            font-size: 12px;
            color: #8e44ad;
            margin-bottom: 3px;
        }

        .move-comments {
            font-size: 13px;
            color: #34495e;
            margin-top: 5px;
            line-height: 1.4;
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            #main-container {
                flex-direction: column;
                align-items: center;
            }
            
            #commentSidebar {
                width: 100%;
                max-width: 480px;
                position: static;
            }
        }

        @media (max-width: 600px) {
            #main-container {
                padding: 10px;
                gap: 15px;
            }
            
            table#chessboard {
                width: 90vw;
                height: 90vw;
                max-width: 400px;
                max-height: 400px;
            }
            
            #chessboard td {
                width: 11.25vw;
                height: 11.25vw;
                max-width: 50px;
                max-height: 50px;
                font-size: 9px;
                padding: 2px;
            }
            
            .piece {
                font-size: 2em;
            }
            
            .hex-keyword {
                font-size: 7px;
            }
            
            .coord-label, .hexNum {
                font-size: 7px;
            }
            
            .hex-img {
                height: 30px;
            }
        }

        /* History Timeline (Right Side) */
        #historyTimeline {
            width: 350px;
            background: #faf8f3;
            border-left: 2px solid #d4c4a8;
            padding: 20px;
            max-height: 100vh;
            overflow-y: auto;
            font-family: Arial, Helvetica, sans-serif !important;
        }
        
        #historyTimeline h3 {
            margin: 0 0 15px 0;
            color: #1a1a1a;
            font-size: 18px;
            border-bottom: 2px solid #d4c4a8;
            padding-bottom: 10px;
            font-weight: normal;
        }
        
        .history-entry {
            margin-bottom: 15px;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #007cba;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .history-entry.white-move {
            border-left-color: #007cba;
        }
        
        .history-entry.black-move {
            border-left-color: #6c757d;
        }
        
        .move-header {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .move-details {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .move-comment {
            font-style: italic;
            background: #f1f3f4;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .move-url {
            margin-top: 5px;
        }
        
        .move-url a {
            color: #007cba;
            font-size: 12px;
        }
        
        /* Comment input area at bottom */
        #commentInput {
            position: sticky;
            bottom: 0;
            background: #f8f9fa;
            padding: 15px 0;
            border-top: 2px solid #dee2e6;
            margin-top: 20px;
        }
        
        #commentInput textarea {
            width: 100%;
            height: 60px;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
        }
        
        #commentInput input[type="url"] {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        #commentInput button {
            background: #007cba;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 8px;
        }
        
        #commentInput button:hover {
            background: #0056b3;
        }

        /* Move Comment Popup Modal */
        #moveCommentModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 25px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .modal-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .modal-header h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.4em;
        }
        
        .move-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .modal-form textarea {
            width: 100%;
            height: 80px;
        }
        
        /* Reference Portal Modal */
        #referencePortalModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001; /* Above move comment modal */
            backdrop-filter: blur(2px);
        }
        
        .reference-modal-content {
            width: 90%;
            max-width: 1000px;
            height: 80%;
            max-height: 600px;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #000;
        }
        
        .reference-iframe-container {
            width: 100%;
            height: calc(100% - 60px); /* Account for header */
            margin-top: 15px;
        }
        
        #referenceIframe {
            width: 100%;
            height: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            resize: vertical;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 15px;
            transition: border-color 0.3s;
        }
        
        .modal-form textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
        }
        
        .modal-form input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        
        .modal-form input[type="url"]:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
        }
        
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: 10px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-skip {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #dee2e6;
        }
        
        .btn-skip:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
    </style>
</head>
<body>
    <div id="main-container">
        <!-- Game Area (Left Side) -->
        <div id="game-area">
            <div class="game-header">
                <div class="header-top">
                    <div class="header-left">
                        <h1>
                            <img src="img/icon.jpg" alt="Intrachange" class="logo-icon">
                            Intrachange
                        </h1>
                    </div>
                    <div class="header-right">
                        <span class="user-info">Welcome, <a href="player_profile.php" class="profile-nav-link"><?php echo htmlspecialchars($_SESSION['username']); ?></a>!</span>
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                        <a href="logout.php" class="nav-link">Logout</a>
                    </div>
                </div>
                <div class="turn-info">
                    <div class="player-info">
                        <?php if ($is_singleplayer): ?>
                            <strong>🧘 Single Player Mode</strong>
                        <?php else: ?>
                            You are: <strong><?php echo $game_state['my_color']; ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="turn-indicator" id="turnIndicator">
                        <?php if ($is_singleplayer): ?>
                            <span style="color: #28a745; font-weight: bold;">
                                🧘 <?php echo $current_turn_color; ?>'s Turn
                            </span>
                        <?php elseif ($is_my_turn): ?>
                            <span style="color: #28a745; font-weight: bold;">🎯 Your Turn (<?php echo $current_turn_color; ?>)</span>
                        <?php else: ?>
                            <span style="color: #6c757d;">⏳ <?php echo $current_turn_color; ?>'s Turn</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="game-controls">
                    <label class="toggle-hexagrams">
                        <input type="checkbox" id="toggleHexagrams">
                        Show Hexagrams
                    </label>
                </div>
            </div>
            
            <div id="chessboard-container">
                <table id="chessboard">
                    <tbody>
                        <!-- Table structure will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

        </div>



        <!-- History Timeline (Right Side) -->
        <div id="historyTimeline">
            <h3>📜 Game Timeline</h3>
            <div id="historyEntries">
                <!-- Move history entries will be populated here -->
            </div>
        </div>
    </div>

    <!-- Move Comment Modal -->
    <div id="moveCommentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Symbolism of the Move</h3>
                <div class="move-info" id="modalMoveInfo">
                    <!-- Move details will be populated here -->
                </div>
            </div>
            <div class="modal-form">
                <textarea id="modalCommentText" placeholder="Share your thoughts on this move, strategy, or hexagram transition..."></textarea>
                <input type="url" id="modalCommentUrl" placeholder="Optional: Link to an image or webpage related to this move.">
                <div id="reference-placeholder"></div>
                <div class="modal-buttons">
                    <button type="button" id="openReference" class="btn-secondary">Reference</button>
                    <button type="button" class="btn-primary" onclick="saveModalComment()">Submit Comment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reference Portal Modal -->
    <div id="referencePortalModal">
        <div class="modal-content reference-modal-content">
            <div class="modal-header">
                <h3>Reference Portal</h3>
                <button type="button" class="modal-close" onclick="hideReferenceModal()">&times;</button>
            </div>
            <div class="reference-iframe-container">
                <iframe id="referenceIframe" src="" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <!-- JavaScript Setup -->
    <script>
        window.API_BASE = window.location.origin + '/';
        console.log('API_BASE defined as:', window.API_BASE);
        
        // Standardized fetch function with validation
        window.safeFetch = async function(endpoint, options = {}) {
            const url = window.API_BASE + endpoint;
            console.log('Fetching:', url);
            
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        };
        
        // Expose game state to JavaScript modules
        window.INTRACHANGE_STATE = <?php echo json_encode($game_state, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
        
        // Create the basic 8x8 table structure
        function createChessboardTable() {
            const table = document.getElementById('chessboard');
            if (!table) {
                console.error('Chessboard table element not found!');
                return;
            }
            
            const tbody = table.querySelector('tbody');
            if (!tbody) {
                console.error('Chessboard tbody element not found!');
                return;
            }
            
            tbody.innerHTML = ''; // Clear any existing content
            
            for (let row = 0; row < 8; row++) {
                const tr = document.createElement('tr');
                tr.dataset.row = 8 - row; // Chess notation: rank 8 at top, rank 1 at bottom
                
                for (let col = 0; col < 8; col++) {
                    const td = document.createElement('td');
                    // Add alternating light/dark square pattern
                    if ((row + col) % 2 === 1) {
                        td.classList.add('dark');
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            }
            
            console.log('✅ Chessboard table structure created: 8x8 grid');
            console.log('✅ Game state loaded:', window.INTRACHANGE_STATE);
        }
        
        // Create table structure with multiple fallbacks
        function initializeChessboard() {
            createChessboardTable();
            
            // Verify table was created properly
            setTimeout(() => {
                const table = document.getElementById('chessboard');
                const rows = table?.querySelectorAll('tr') || [];
                if (rows.length !== 8) {
                    console.warn('⚠️ Chessboard table creation failed, retrying...');
                    createChessboardTable();
                }
            }, 100);
        }
        
        // Multiple initialization attempts
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeChessboard);
        } else {
            initializeChessboard();
        }
        
        // Additional safety check
        window.addEventListener('load', () => {
            const table = document.getElementById('chessboard');
            const rows = table?.querySelectorAll('tr') || [];
            if (rows.length === 0) {
                console.warn('⚠️ Table still empty after load, creating structure...');
                createChessboardTable();
            }
        });
        
        // Enhanced debug helper
        
        // Auto-debug on console access
        console.log('🎯 Intrachange Game Page Loaded');
        console.log('💡 Type debugChessboard() in console to verify board structure');
        
        // History Timeline Functions
        function populateHistoryTimeline() {
            // console.log('🕐 Populating history timeline...');
            const historyContainer = document.getElementById('historyEntries');
            if (!historyContainer) {
                console.error('❌ History container not found');
                return;
            }
            if (!window.INTRACHANGE_STATE?.moves) {
                console.log('📭 No moves in INTRACHANGE_STATE yet');
                return;
            }
            
            // console.log(`📚 Found ${window.INTRACHANGE_STATE.moves.length} moves to display`);
            historyContainer.innerHTML = '';
            
            window.INTRACHANGE_STATE.moves.forEach((move, index) => {
                const entry = createHistoryEntry(move, index + 1);
                historyContainer.appendChild(entry);
            });
            
            // Auto-scroll to bottom
            historyContainer.scrollTop = historyContainer.scrollHeight;
        }
        
        function createHistoryEntry(move, moveNumber) {
            const div = document.createElement('div');
            const isWhiteMove = moveNumber % 2 === 1;
            div.className = `history-entry ${isWhiteMove ? 'white-move' : 'black-move'}`;
            div.style.cursor = 'pointer';

            // Extract piece from coordinates or use fallback
            const piece = extractPieceFromMove(move);


            // Refined: top line = piece + joined coords; details = joined hexagrams and keywords only
            let coordsStr = '', hexagramsStr = '', keywordsStr = '';
            let moveData = move.move_data;
            if (typeof moveData === 'string') {
                try { moveData = JSON.parse(moveData); } catch (e) { moveData = null; }
            }
            if (moveData && Array.isArray(moveData.coords) && Array.isArray(moveData.hexagrams) && Array.isArray(moveData.keywords)) {
                coordsStr = moveData.coords.filter(Boolean).join(' → ');
                hexagramsStr = moveData.hexagrams.filter(Boolean).join(' → ');
                keywordsStr = moveData.keywords.filter(Boolean).join(' → ');
            } else if (move.hex_from && move.hex_to) {
                coordsStr = move.chess_coordinates || '';
                hexagramsStr = `${move.hex_from} → ${move.hex_to}`;
                keywordsStr = `${move.keyword_from || ''} → ${move.keyword_to || ''}`;
            } else {
                coordsStr = move.chess_coordinates || 'N/A';
            }

            let html = `
                <div class=\"move-header\">Move ${moveNumber}: ${piece} ${coordsStr}</div>
                <div class=\"move-details\">Hexagrams: ${hexagramsStr}<br>Keywords: ${keywordsStr}</div>
            `;

            if (move.user_comment) {
                html += `<div class="move-comment">"${move.user_comment}"</div>`;
            }

            if (move.comment_url) {
                html += `<div class="move-url"><a href="${move.comment_url}" target="_blank">🔗 View Resource</a></div>`;
            }

            div.innerHTML = html;
            // Add click handler to open the comment modal with move data
            div.onclick = function() {
                // Always call the original comment modal from game.php, not the debug/test modal
                if (typeof window.showMoveCommentModalOriginal === 'function') {
                    window.showMoveCommentModalOriginal(move);
                } else if (typeof showMoveCommentModal === 'function') {
                    showMoveCommentModal(move);
                } else {
                    alert('Comment modal function not found.');
                }
            };
        // Ensure the original modal function is always available as window.showMoveCommentModalOriginal
        if (typeof showMoveCommentModal === 'function') {
            window.showMoveCommentModalOriginal = showMoveCommentModal;
        }
            return div;
        }
        
        function extractPieceFromMove(move) {
            // Use the stored piece_type if available
            if (move.piece_type) {
                return move.piece_type;
            }
            
            // Fallback to generic indicator if piece_type is not stored
            if (move.chess_coordinates) {
                return move.player_id === 1 ? 'W●' : 'B●';
            }
            return '●';
        }
        
        function clearCommentInput() {
            // Legacy function - now handled by modal
            console.log('Legacy clearCommentInput called');
        }
        
        function showCommentInput() {
            // Legacy function - now handled by modal  
            console.log('Legacy showCommentInput called');
        }
        
        function hideCommentInput() {
            // Legacy function - now handled by modal
            console.log('Legacy hideCommentInput called');
        }
        
        // Modal Comment Functions
        let currentMoveData = null;
        
        function openReferencePortal() {
            console.log("🚀 Opening Reference Portal Modal");

            // Use currentMoveData if available (modal open), otherwise latest move from history
            let hexList = [];
            if (currentMoveData?.hexagrams) {
                hexList = currentMoveData.hexagrams;
            } else if (window.INTRACHANGE_STATE?.moves?.length > 0) {
                const latestMove = window.INTRACHANGE_STATE.moves[window.INTRACHANGE_STATE.moves.length - 1];
                // Extract from move_data or legacy fields
                if (latestMove.move_data?.hexagrams) {
                    hexList = latestMove.move_data.hexagrams;
                } else {
                    // Build from legacy fields
                    if (latestMove.hex_from) hexList.push(latestMove.hex_from);
                    if (latestMove.hex_to) hexList.push(latestMove.hex_to);
                }
            }
            let url = 'ref/index.html';

            if (hexList.length >= 2) {
                const origin = hexList[0];
                const target = hexList[1];   // ALWAYS second element

                const params = new URLSearchParams();
                params.set('origin', origin);
                params.set('target', target);

                // If esoteric exists, it is the third element
                if (hexList.length === 3) {
                    const esoteric = hexList[2];
                    params.set('esoteric', esoteric);
                }

                url += '?' + params.toString();
            }

            // Load the reference page in the iframe and show modal
            const iframe = document.getElementById('referenceIframe');
            iframe.src = url;
            
            // Show the reference modal
            const modal = document.getElementById('referencePortalModal');
            modal.style.display = 'block';
            modal.style.visibility = 'visible';
            modal.style.opacity = 1;
            
            console.log('✅ Reference Portal modal opened with URL:', url);
        }
        
        function hideReferenceModal() {
            const modal = document.getElementById('referencePortalModal');
            modal.style.display = 'none';
            
            // Clear iframe src to stop any loading
            const iframe = document.getElementById('referenceIframe');
            iframe.src = '';
            
            console.log('✅ Reference Portal modal closed');
        }
        
        async function populateReferenceFromMove() {
            const container = document.getElementById('reference-placeholder');
            if (!container) return;
            
            container.innerHTML = '';
            
            const hexList = currentMoveData?.hexagrams || [];
            
            try {
                const { getKeywordFromHex } = await import('./frontend/hexagrams.mjs');
                
                hexList.forEach(hexNum => {
                    const keyword = getKeywordFromHex(hexNum);
                    
                    const item = document.createElement('div');
                    item.textContent = keyword;
                    
                    item.addEventListener('click', () => {
                        if (portalWindow && !portalWindow.closed) {
                            portalWindow.loadHexIntoContent(hexNum);
                        } else {
                            console.warn('Portal window not available');
                        }
                    });
                    
                    container.appendChild(item);
                });
            } catch (error) {
                console.error('Failed to load hexagram keywords:', error);
            }
        }        
        function showMoveCommentModal(moveData) {
            console.log('showMoveCommentModal called with:', moveData);
            currentMoveData = moveData;

            // Populate move info with all steps (dynamic join)
            const moveInfo = document.getElementById('modalMoveInfo');
            const piece = moveData.piece_type || 'Piece';

            // Use move_data arrays if present, else fallback to legacy fields
            const coords = (moveData.move_data && moveData.move_data.coords) ? moveData.move_data.coords : (moveData.coords || []);
            const hexagrams = (moveData.move_data && moveData.move_data.hexagrams) ? moveData.move_data.hexagrams : (moveData.hexagrams || []);
            const keywords = (moveData.move_data && moveData.move_data.keywords) ? moveData.move_data.keywords : (moveData.keywords || []);

            // Join arrays for display
            const coordsText = coords.length ? coords.join(' → ') : (moveData.chess_coordinates || '');
            const hexText = hexagrams.length ? hexagrams.join(' → ') : 'Hexagram data pending';
            const keywordsText = keywords.length ? keywords.map(k => `"${k}"`).join(' → ') : 'Keywords pending';

            moveInfo.innerHTML = `
                <strong>${piece}</strong> moved ${coordsText}<br>
                <em>${hexText}</em><br>
                <small style="color: #666;">${keywordsText}</small>
            `;

            // Clear previous inputs
            document.getElementById('modalCommentText').value = '';
            document.getElementById('modalCommentUrl').value = '';

            // Show modal
            document.getElementById('moveCommentModal').style.display = 'block';
            document.getElementById('moveCommentModal').style.visibility = 'visible';
            document.getElementById('moveCommentModal').style.opacity = 1;
            document.getElementById('modalCommentText').focus();
            
            // Populate reference section
            populateReferenceFromMove();
        }
        
        // Make the function globally accessible for boardController.mjs
        window.showMoveCommentModal = showMoveCommentModal;
        
        function hideCommentModal() {
            document.getElementById('moveCommentModal').style.display = 'none';
            currentMoveData = null;
        }
        
        function skipComment() {
            // Reset selectedSquare to null
            if (window.boardController && 'selectedSquare' in window.boardController) {
                window.boardController.selectedSquare = null;
            }
            // Clear highlights
            if (window.ui && typeof window.ui.clearHighlights === 'function') {
                window.ui.clearHighlights();
            }
            if (window.boardController && typeof window.boardController.clearSelectedPiece === 'function') {
                window.boardController.clearSelectedPiece();
            }
            // Close the modal
            hideCommentModal();
            // Do NOT call any move-finalization or API functions
        }
        
        function clearModalComment() {
            document.getElementById('modalCommentText').value = '';
            document.getElementById('modalCommentUrl').value = '';
        }
        
        async function saveModalComment() {
            const commentText = document.getElementById('modalCommentText').value.trim();
            const commentUrl = document.getElementById('modalCommentUrl').value.trim();
            
            if (!commentText && !commentUrl) {
                skipComment();
                return;
            }
            
            // Update the move in database with comment
            try {
                const response = await window.safeFetch('backend/update_move_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        move_id: currentMoveData.id,
                        user_comment: commentText,
                        comment_url: commentUrl
                    })
                });
                
                const result = response; // safeFetch handles JSON parsing
                if (result.success) {
                    // Update the move in INTRACHANGE_STATE with the new comment
                    if (window.INTRACHANGE_STATE?.moves) {
                        const moveIndex = window.INTRACHANGE_STATE.moves.findIndex(m => m.id == currentMoveData.id);
                        if (moveIndex !== -1) {
                            window.INTRACHANGE_STATE.moves[moveIndex].user_comment = commentText;
                            window.INTRACHANGE_STATE.moves[moveIndex].comment_url = commentUrl;
                        }
                    }
                    
                    // Refresh the timeline to show the new comment
                    populateHistoryTimeline();
                    hideCommentModal();
                } else {
                    alert('Failed to save comment: ' + result.error);
                }
            } catch (error) {
                console.error('Error saving comment:', error);
                alert('Error saving comment. Please try again.');
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('moveCommentModal');
            if (event.target === modal) {
                hideCommentModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideCommentModal();
            }
        });
        
        // Initialize CSS animations once on page load
        function initializeNotificationStyles() {
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes flash {
                    0%, 100% { background-color: rgba(76, 175, 80, 0.3); }
                    50% { background-color: rgba(76, 175, 80, 0.8); }
                }
                .move-flash {
                    animation: flash 0.6s ease-in-out 3;
                }
            `;
            document.head.appendChild(style);
        }

        // Unified polling for moves and timeline updates
        let lastKnownMoveId = <?php echo $move_count; ?>;
        
        function showMoveNotification() {
            // Suppress notification in single player mode
            if (window.INTRACHANGE_STATE && window.INTRACHANGE_STATE.is_singleplayer) {
                return;
            }
            // Create notification banner
            const banner = document.createElement('div');
            banner.innerHTML = '🎯 Your opponent just moved!';
            banner.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4CAF50;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                font-weight: bold;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideIn 0.5s ease-out;
            `;
            document.body.appendChild(banner);
            // Flash the game board
            const gameBoard = document.querySelector('.game-board, #chessboard, .board');
            if (gameBoard) {
                gameBoard.classList.add('move-flash');
                setTimeout(() => {
                    gameBoard.classList.remove('move-flash');
                }, 2000);
            }
            // Remove banner after 3 seconds
            setTimeout(() => {
                banner.remove();
            }, 3000);
        }
        
        function unifiedPolling() {
            const endpoint = `_dev/utilities/check_game_status.php?game_id=<?php echo $selected_game_id; ?>`;
            
            window.safeFetch(endpoint)
                .then(data => {
                    if (!data) return;
                    if (data.last_move_id > lastKnownMoveId) {
                        console.log('New move detected!');
                        lastKnownMoveId = data.last_move_id;
                        showMoveNotification();
                        // Update timeline after detecting new move
                        populateHistoryTimeline();
                    } else {
                        // Regular timeline refresh even when no new moves
                        if (window.INTRACHANGE_STATE?.moves) {
                            populateHistoryTimeline();
                        }
                    }
                })
                .catch(error => {
                    console.log('Polling error (ignored):', error);
                    // Still try to update timeline on errors
                    if (window.INTRACHANGE_STATE?.moves) {
                        populateHistoryTimeline();
                    }
                });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeNotificationStyles();
            setTimeout(populateHistoryTimeline, 500); // Initial timeline load
            
            // Reference button portal launcher - Phase 1: Navigation test only
            const referenceButton = document.getElementById('openReference');
            if (referenceButton) {
                console.log('✅ Reference button found, attaching click handler');
                referenceButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔍 Reference button clicked! Opening ref portal...');
                    openReferencePortal();
                });
            } else {
                console.error('❌ Reference button not found!');
            }
        });
        
        // Unified polling every 10 seconds
        setInterval(unifiedPolling, 10000);
        console.log('Unified polling started - checking every 10 seconds');
    </script>
    
    <!-- Load ES6 Modules -->
    <script type="module" src="frontend/script.mjs"></script>
    <script type="module" src="frontend/commentSystem.mjs"></script>
</body>
</html>
