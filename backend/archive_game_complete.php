<?php
// archive_game_complete.php - Complete archive workflow implementation
// Follows the exact workflow you specified

// Start output buffering to prevent any stray output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['game_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing game_id']);
    exit;
}

$game_id = (int)$input['game_id'];
$game_name = $input['game_name'] ?? null;
    // Determine game result from winner_id or provided data
    $game_result = $input['game_result'] ?? null;
    
    // If no game_result provided, determine from winner_id
    if (!$game_result) {
        if (!$game['winner_id']) {
            $game_result = 'DRAW';
        } elseif ($game['winner_id'] == $game['white_player_id']) {
            $game_result = 'WHITE_WINS';
        } elseif ($game['winner_id'] == $game['black_player_id']) {
            $game_result = 'BLACK_WINS';
        } else {
            $game_result = 'DRAW'; // fallback
        }
    }
    
    $end_reason = $input['end_reason'] ?? ($game['status'] == 'abandoned' ? 'abandoned' : 'manual_archive');
    $board_final_state = $input['board_final_state'] ?? null;
$test_mode = isset($input['test_mode']) ? (bool)$input['test_mode'] : false;

try {
    // Start transaction
    $conn->autocommit(false);
    
    // STEP 1: Gather Game Data
    // Fetch the finished game info from games table
    $game_stmt = $conn->prepare("
        SELECT id, white_player_id, black_player_id, created_at, status, winner_id, 
               current_turn, game_mode, archived, updated_at
        FROM games 
        WHERE id = ?
    ");
    $game_stmt->bind_param("i", $game_id);
    $game_stmt->execute();
    $game_result_obj = $game_stmt->get_result();
    $game = $game_result_obj->fetch_assoc();
    
    if (!$game) {
        throw new Exception("Game not found");
    }
    
    // Check if already archived
    if ($game['archived']) {
        echo json_encode(['status' => 'ok', 'message' => 'Game already archived']);
        $conn->commit();
        exit;
    }
    
    // Fetch all moves for the game from moves table
    $moves_stmt = $conn->prepare("
        SELECT id, player_id, move_number, piece_type, from_position, to_position, 
               chess_coordinates, from_square, to_square, coord_from, coord_to,
               hexagram_from, hexagram_to, keyword_from, keyword_to,
               user_comment, comment_url, captured_piece, is_check, is_checkmate,
               created_at as timestamp
        FROM moves 
        WHERE game_id = ? 
        ORDER BY move_number ASC
    ");
    $moves_stmt->bind_param("i", $game_id);
    $moves_stmt->execute();
    $moves_result = $moves_stmt->get_result();
    
    // Build moves_json array from moves
    $moves_json = [];
    while ($move = $moves_result->fetch_assoc()) {
        $moves_json[] = [
            'id' => (int)$move['id'],
            'player_id' => (int)$move['player_id'],
            'move_number' => (int)$move['move_number'],
            'piece_type' => $move['piece_type'],
            'from_position' => $move['from_position'],
            'to_position' => $move['to_position'],
            'from_square' => $move['from_square'],
            'to_square' => $move['to_square'],
            'chess_coordinates' => $move['chess_coordinates'],
            'coord_from' => $move['coord_from'],
            'coord_to' => $move['coord_to'],
            'hexagram_from' => $move['hexagram_from'],
            'hexagram_to' => $move['hexagram_to'],
            'keyword_from' => $move['keyword_from'],
            'keyword_to' => $move['keyword_to'],
            'user_comment' => $move['user_comment'],
            'comment_url' => $move['comment_url'],
            'captured_piece' => $move['captured_piece'],
            'is_check' => (bool)$move['is_check'],
            'is_checkmate' => (bool)$move['is_checkmate'],
            'timestamp' => $move['timestamp']
        ];
    }
    
    // Count moves
    $move_count = count($moves_json);
    
    // Build a human-readable game_summary
    $game_summary = generateGameSummary($game, $moves_json, $move_count);
    
    // STEP 2: Insert Into Archive Table
    $archive_stmt = $conn->prepare("
        INSERT INTO game_archives (
            game_id, white_player_id, black_player_id, game_result, end_reason,
            move_count, game_summary, moves_json, board_final_state,
            start_timestamp, end_timestamp, test_mode, game_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $moves_json_str = json_encode($moves_json);
    $board_final_state_str = $board_final_state ? json_encode($board_final_state) : null;
    
    $archive_stmt->bind_param(
        "iiissississis", 
        $game_id,
        $game['white_player_id'],
        $game['black_player_id'],
        $game_result,
        $end_reason,
        $move_count,
        $game_summary,
        $moves_json_str,
        $board_final_state_str,
        $game['created_at'],
        $game['updated_at'], // Use updated_at as end_timestamp
        $test_mode,
        $game_name
    );
    
    if (!$archive_stmt->execute()) {
        throw new Exception("Failed to insert into archive table: " . $archive_stmt->error);
    }
    
    $archive_id = $conn->insert_id;
    
    // STEP 3: Update Live Game Table
    $update_stmt = $conn->prepare("UPDATE games SET archived = TRUE WHERE id = ?");
    $update_stmt->bind_param("i", $game_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to mark game as archived: " . $update_stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    // STEP 4: Return Response to Frontend
    echo json_encode([
        'status' => 'ok',
        'message' => 'Game archived successfully',
        'archive_id' => $archive_id,
        'game_summary' => $game_summary,
        'move_count' => $move_count
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Archive game error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to archive game: ' . $e->getMessage()
    ]);
} finally {
    $conn->autocommit(true);
}

function generateGameSummary($game, $moves_json, $move_count) {
    if ($move_count === 0) {
        return "Game with no moves - Status: {$game['status']}";
    }
    
    $white_player_id = $game['white_player_id'];
    $black_player_id = $game['black_player_id'];
    $game_mode = $game['game_mode'] ?? 'multiplayer';
    
    // Determine result description
    $result_desc = 'Completed';
    if ($game['winner_id']) {
        if ($game['winner_id'] == $white_player_id) {
            $result_desc = 'White wins';
        } elseif ($game['winner_id'] == $black_player_id) {
            $result_desc = 'Black wins';
        }
    } else if ($game['status'] == 'abandoned') {
        $result_desc = 'Abandoned';
    } else {
        $result_desc = 'Draw';
    }
    
    // Get final move details if available
    if (count($moves_json) > 0) {
        $final_move = end($moves_json);
        $piece_name = getPieceName($final_move['piece_type'] ?? '');
        
        // Check if final move was esoteric (contains special coordinates)
        $is_esoteric = strpos($final_move['chess_coordinates'] ?? '', '→') !== false;
        $move_type = $is_esoteric ? 'Esoteric' : 'Classical';
        
        // Extract keywords for context
        $keywords = [];
        if (!empty($final_move['keyword_from']) || !empty($final_move['keyword_to'])) {
            if ($final_move['keyword_from']) $keywords[] = $final_move['keyword_from'];
            if ($final_move['keyword_to']) $keywords[] = $final_move['keyword_to'];
        }
        
        $keyword_text = !empty($keywords) ? ' (' . implode(' → ', $keywords) . ')' : '';
        
        return "{$result_desc} after {$move_count} moves ({$game_mode}). Final move: {$move_type} {$piece_name} from {$final_move['from_position']} to {$final_move['to_position']}{$keyword_text}";
    } else {
        return "{$result_desc} - {$game_mode} game with {$move_count} moves";
    }
}

function getPieceName($piece_code) {
    if (empty($piece_code) || strlen($piece_code) < 2) {
        return 'Piece';
    }
    
    $pieces = [
        'P' => 'Pawn', 'R' => 'Rook', 'N' => 'Knight', 
        'B' => 'Bishop', 'Q' => 'Queen', 'K' => 'King'
    ];
    
    // Extract piece type (assuming format like "WP", "BR", etc.)
    $piece_type = substr($piece_code, 1, 1);
    return $pieces[$piece_type] ?? 'Piece';
}

// Clean output buffer and send
ob_end_flush();