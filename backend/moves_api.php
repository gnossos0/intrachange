<?php
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
// Helper: build_move_data_from_flat_fields
function build_move_data_from_flat_fields($row) {
    return [
        'coords' => [
            isset($row['coord_from']) ? $row['coord_from'] : (isset($row['from_square']['coord']) ? $row['from_square']['coord'] : ''),
            isset($row['coord_mid']) ? $row['coord_mid'] : (isset($row['mid_square']['coord']) ? $row['mid_square']['coord'] : ''),
            isset($row['coord_to']) ? $row['coord_to'] : (isset($row['to_square']['coord']) ? $row['to_square']['coord'] : ''),
        ],
        'hexagrams' => [
            isset($row['hex_from']) ? $row['hex_from'] : '',
            isset($row['hex_mid']) ? $row['hex_mid'] : '',
            isset($row['hex_to']) ? $row['hex_to'] : '',
        ],
        'keywords' => [
            isset($row['keyword_from']) ? $row['keyword_from'] : '',
            isset($row['keyword_mid']) ? $row['keyword_mid'] : '',
            isset($row['keyword_to']) ? $row['keyword_to'] : '',
        ],
        'hex_from' => isset($row['hex_from']) ? $row['hex_from'] : '',
        'hex_mid' => isset($row['hex_mid']) ? $row['hex_mid'] : '',
        'hex_to' => isset($row['hex_to']) ? $row['hex_to'] : '',
        'keyword_from' => isset($row['keyword_from']) ? $row['keyword_from'] : '',
        'keyword_mid' => isset($row['keyword_mid']) ? $row['keyword_mid'] : '',
        'keyword_to' => isset($row['keyword_to']) ? $row['keyword_to'] : ''
    ];
}
// Helper to build move_data arrays and legacy fields in sync
function build_move_data($fromCoord, $chosenCoord, $toCoord, $fromHex, $chosenHex, $toHex, $fromKeyword, $chosenKeyword, $toKeyword) {
    // Normalize all values to non-null strings
    $coords = [
        isset($fromCoord) ? $fromCoord : '',
        isset($chosenCoord) ? $chosenCoord : '',
        isset($toCoord) ? $toCoord : ''
    ];

    $hexagrams = [
        isset($fromHex) ? $fromHex : '',
        isset($chosenHex) ? $chosenHex : '',
        isset($toHex) ? $toHex : ''
    ];

    $keywords = [
        isset($fromKeyword) ? $fromKeyword : '',
        isset($chosenKeyword) ? $chosenKeyword : '',
        isset($toKeyword) ? $toKeyword : ''
    ];

    // Also return the legacy flat fields fully in sync
    return [
        'coords' => $coords,
        'hexagrams' => $hexagrams,
        'keywords' => $keywords,

        'hex_from' => $hexagrams[0],
        'hex_mid' => $hexagrams[1],
        'hex_to' => $hexagrams[2],

        'keyword_from' => $keywords[0],
        'keyword_mid' => $keywords[1],
        'keyword_to' => $keywords[2]
    ];
}
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// Temporary toggle: disable strict turn enforcement in multiplayer (keep code but don't apply)
$STRICT_TURN_ENFORCEMENT = true;

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use session user ID or mock for testing
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Handle new move submission
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        // Extract move data
        $game_id          = isset($data['game_id']) ? $data['game_id'] : null;
        $chess_coordinates = isset($data['chess_coordinates']) ? $data['chess_coordinates'] : '';
        $piece_type       = isset($data['piece_type']) ? $data['piece_type'] : '';
        $from_square      = isset($data['from_square']) ? $data['from_square'] : [];
        $to_square        = isset($data['to_square']) ? $data['to_square'] : [];

        if (!$game_id) {
            throw new Exception('Game ID is required');
        }

        // Step 3: Check turn-taking for multiplayer games
        $game_sql = "SELECT white_player_id, black_player_id, current_turn, is_singleplayer 
                     FROM games WHERE id = ?";
        $game_stmt = $conn->prepare($game_sql);
        $game_stmt->bind_param("i", $game_id);
        $game_stmt->execute();
        $game_result = $game_stmt->get_result();
        
        if ($game_result->num_rows !== 1) {
            throw new Exception('Game not found');
        }
        
        $game = $game_result->fetch_assoc();
        $game_stmt->close();
        
        // Only enforce turn-taking for multiplayer games (temporarily disabled)
        if ($game['is_singleplayer'] == 0 && $STRICT_TURN_ENFORCEMENT) {
            if ($game['current_turn'] == 'white' && $user_id != $game['white_player_id']) {
                throw new Exception("It's not your turn! Waiting for white player.");
            } elseif ($game['current_turn'] == 'black' && $user_id != $game['black_player_id']) {
                throw new Exception("It's not your turn! Waiting for black player.");
            }
        }




        // --- Use build_move_data helper for robust, always-3-element move_data ---
        // --- Strict move_data array alignment: exoteric = 2, esoteric = 3 ---

        $isEsoteric = (isset($data['esoteric']) && isset($data['esoteric']['coord']) && $data['esoteric']['coord'] !== null && $data['esoteric']['coord'] !== '');


        // Always use from_square['coord'] and to_square['coord'] for move endpoints
        $coord_from = isset($from_square['coord']) ? $from_square['coord'] : '';
        $coord_mid = $isEsoteric ? (isset($data['esoteric']['coord']) ? $data['esoteric']['coord'] : null) : null;
        $coord_to = isset($to_square['coord']) ? $to_square['coord'] : '';



        // Build move_data arrays with strict alignment and no padding
        if ($isEsoteric) {
            // Esoteric: 3 elements, all must be present, no padding
            $coords = array_values(array_filter([
                $coord_from,
                $coord_mid,
                $coord_to
            ], function($v) { return $v !== null && $v !== ''; }));
            $hexagrams = isset($data['hexagrams']) ? $data['hexagrams'] : [
                isset($data['hexagrams']['from']) ? $data['hexagrams']['from'] : '',
                isset($data['esoteric']['hexagram']) ? $data['esoteric']['hexagram'] : '',
                isset($data['hexagrams']['to']) ? $data['hexagrams']['to'] : ''
            ];
            $keywords = isset($data['keywords']) ? $data['keywords'] : [
                isset($data['keywords']['from']) ? $data['keywords']['from'] : '',
                isset($data['esoteric']['keyword']) ? $data['esoteric']['keyword'] : '',
                isset($data['keywords']['to']) ? $data['keywords']['to'] : ''
            ];
        } else {
            // Exoteric: 2 elements, no padding
            $coords = array_values(array_filter([
                $coord_from,
                $coord_to
            ], function($v) { return $v !== null && $v !== ''; }));
            $hexagrams = isset($data['hexagrams']) ? $data['hexagrams'] : [
                isset($data['hexagrams']['from']) ? $data['hexagrams']['from'] : '',
                isset($data['hexagrams']['to']) ? $data['hexagrams']['to'] : ''
            ];
            $keywords = isset($data['keywords']) ? $data['keywords'] : [
                isset($data['keywords']['from']) ? $data['keywords']['from'] : '',
                isset($data['keywords']['to']) ? $data['keywords']['to'] : ''
            ];
        }

        // Ensure all arrays are strictly index-aligned and only as long as coords
        $hexagrams = array_slice($hexagrams, 0, count($coords));
        $keywords = array_slice($keywords, 0, count($coords));

        $move_data = [
            'coords' => $coords,
            'hexagrams' => $hexagrams,
            'keywords' => $keywords
        ];
        $move_data_json = json_encode($move_data);

        // Store chess_coordinates, hexagram, and keyword as human-readable strings for display
        // Build 3-step strings only if mid values are present and non-empty
        $chess_coordinates = $coord_from;
        $hexagram_str = (string)(isset($data['hexagrams']['from']) ? $data['hexagrams']['from'] : '');
        $keyword_str = (string)(isset($data['keywords']['from']) ? $data['keywords']['from'] : '');
        $has_mid = $isEsoteric && $coord_mid && $coord_mid !== '' &&
            isset($data['esoteric']['hexagram']) && $data['esoteric']['hexagram'] !== null && $data['esoteric']['hexagram'] !== '' &&
            isset($data['esoteric']['keyword']) && $data['esoteric']['keyword'] !== null && $data['esoteric']['keyword'] !== '';

        if ($has_mid) {
            $chess_coordinates .= " → " . $coord_mid . " → " . $coord_to;
            $hexagram_str .= " → " . $data['esoteric']['hexagram'] . " → " . (isset($data['hexagrams']['to']) ? $data['hexagrams']['to'] : '');
            $keyword_str .= " → " . $data['esoteric']['keyword'] . " → " . (isset($data['keywords']['to']) ? $data['keywords']['to'] : '');
        } else {
            $chess_coordinates .= " → " . $coord_to;
            $hexagram_str .= " → " . (isset($data['hexagrams']['to']) ? $data['hexagrams']['to'] : '');
            $keyword_str .= " → " . (isset($data['keywords']['to']) ? $data['keywords']['to'] : '');
        }

        $hex_from      = isset($data['hex_from']) ? $data['hex_from'] : null;
        $hex_to        = isset($data['hex_to']) ? $data['hex_to'] : null;
        $hex_mid       = $isEsoteric ? (isset($data['esoteric']['hexagram']) ? $data['esoteric']['hexagram'] : null) : null;
        $keyword_from  = isset($data['keyword_from']) ? $data['keyword_from'] : '';
        $keyword_to    = isset($data['keyword_to']) ? $data['keyword_to'] : '';
        $keyword_mid   = $isEsoteric ? (isset($data['esoteric']['keyword']) ? $data['esoteric']['keyword'] : null) : null;
        $user_comment  = isset($data['user_comment']) ? $data['user_comment'] : '';
        $comment_url   = isset($data['comment_url']) ? $data['comment_url'] : '';
        $esoteric_json = isset($data['esoteric']) ? json_encode($data['esoteric']) : null;

        $from_position = isset($from_square['coord']) ? $from_square['coord'] : '';
        $to_position   = isset($to_square['coord']) ? $to_square['coord'] : '';

        // Get current move number for this game
        $stmt = $conn->prepare("SELECT COALESCE(MAX(move_number), 0) + 1 as next_move FROM moves WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $next_move = $result->fetch_assoc()['next_move'];
        $stmt->close();

        // Encode from/to squares as JSON
        $from_square_json = json_encode($from_square);
        $to_square_json   = json_encode($to_square);



        // Insert the move with coord_from, coord_mid, coord_to columns
        $stmt = $conn->prepare("
            INSERT INTO moves
            (game_id, player_id, from_position, to_position, piece_type, move_number,
             chess_coordinates, from_square, to_square, coord_from, coord_mid, coord_to,
             hex_from, hex_mid, hex_to, keyword_from, keyword_mid, keyword_to,
             user_comment, comment_url, esoteric, move_data, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        // Types: i, i, s, s, s, i, s, s, s, s, s, s, i, i, i, s, s, s, s, s, s, s, s
        $stmt->bind_param(
            "iisssissssssiiisssssss",
            $game_id, $user_id, $from_position, $to_position, $piece_type, $next_move,
            $chess_coordinates, $from_square_json, $to_square_json, $coord_from, $coord_mid, $coord_to,
            $hex_from, $hex_mid, $hex_to, $keyword_from, $keyword_mid, $keyword_to,
            $user_comment, $comment_url, $esoteric_json, $move_data_json
        );

        // (No fallback or legacy padding: move_data arrays are strictly aligned)

        // --- Diagnostic logging ---
        error_log("DEBUG move_data before DB insert: " . json_encode($move_data));
        error_log("DEBUG POST data received: " . json_encode($data));

        if ($stmt->execute()) {

            $move_id = $conn->insert_id;
            $stmt->close();

            // Step 3: Switch turn for multiplayer games and update last_move_at
            if ($game['is_singleplayer'] == 0) {
                                // Use current_turn from DB before the move
                                $currentTurn = $game['current_turn'];
                                $nextTurn = ($currentTurn === 'white') ? 'black' : 'white';
                                error_log("[moves_api.php] currentTurn from DB: $currentTurn, nextTurn: $nextTurn, game_id: $game_id");

                                $updateTurn = $conn->prepare("
                                    UPDATE games 
                                    SET current_turn = ?, last_move_at = NOW()
                                    WHERE id = ?
                                ");
                                if (!$updateTurn) {
                                        error_log('[moves_api.php] Failed to prepare updateTurn statement: ' . $conn->error);
                                }
                                $updateTurn->bind_param("si", $nextTurn, $game_id);
                                $success = $updateTurn->execute();
                                if (!$success) {
                                        error_log('[moves_api.php] Failed to execute updateTurn: ' . $updateTurn->error);
                                } else {
                                        error_log('[moves_api.php] updateTurn executed successfully.');
                                }
                                $updateTurn->close();
            }

            // Add email notification to the opponent when a move is made, with a link to resume the game using the game_id.
            // PHPMailer imports are already at the top of the file.

            // Only send for multiplayer games
            if ($game['is_singleplayer'] == 0) {
                // Determine whose turn it is now
                $opponent_id = ($nextTurn === 'white') ? $game['white_player_id'] : $game['black_player_id'];
                // Get opponent email and username
                $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
                $stmt->bind_param("i", $opponent_id);
                $stmt->execute();
                $opponent = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($opponent && !empty($opponent['email'])) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'];
                    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                    $game_url = $protocol . $host . $basePath . "/../../game.php?game_id=" . urlencode($game_id);
                    $subject = "It's your turn in Intrachange!";
                    $body = "<div style=\"font-family: Georgia, serif; max-width: 600px; margin: 0 auto; background: #faf8f3; border: 2px solid #d4c4a8; border-radius: 8px; padding: 30px;\">" .
                        "<h2 style=\"color: #1a1a1a;\">It's your turn!</h2>" .
                        "<p>The game is waiting for your move. Click below to resume:</p>" .
                        "<div style=\"margin: 20px 0;\"><a href=\"$game_url\" style=\"background: #1a1a1a; color: #faf8f3; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 16px; display: inline-block; font-family: Georgia, serif;\">Resume Game</a></div>" .
                        "<p style=\"color: #5a5a5a;\">Game ID: #$game_id</p>" .
                        "<p style=\"margin: 0; color: #666; font-size: 0.9em;\">Intrachange • Where Chess meets the I Ching • The Game of Changes</p>" .
                        "</div>";
                    $mailer = new PHPMailer(true);
                    try {
                        $mailer->isSMTP();
                        $mailer->Host       = 'intrachange.net';
                        $mailer->SMTPAuth   = true;
                        $mailer->Username   = 'info@intrachange.net';
                        $mailer->Password   = 'Intr4ch4ng3!';
                        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mailer->Port       = 465;
                        $mailer->setFrom('info@intrachange.net', 'Intrachange');
                        $mailer->addAddress($opponent['email'], $opponent['username']);
                        $mailer->Subject = $subject;
                        $mailer->Body    = $body;
                        $mailer->isHTML(true);
                        $mailer->send();
                    } catch (PHPMailerException $e) {
                        error_log("Failed to send turn notification: " . $e->getMessage());
                    }
                }
            }

            // Return the created move data
            $response = [
                'success' => true,
                'move' => [
                    'id'               => $move_id,
                    'game_id'          => $game_id,
                    'move_number'      => $next_move,
                    'chess_coordinates'=> $chess_coordinates,
                    'piece_type'       => $piece_type,
                    'from_square'      => $from_square,
                    'to_square'        => $to_square,
                    'hex_from'         => $hex_from,
                    'hex_to'           => $hex_to,
                    'keyword_from'     => $keyword_from,
                    'keyword_to'       => $keyword_to,
                    'user_comment'     => $user_comment,
                    'comment_url'      => $comment_url,
                    'esoteric'         => isset($data['esoteric']) ? $data['esoteric'] : null,
                    'move_data'        => $move_data,
                    'timestamp'        => date('Y-m-d H:i:s')
                ],
                'next_turn'        => isset($next_turn) ? $next_turn : null
            ];

            echo json_encode($response);

        } else {
            throw new Exception('Failed to save move: ' . $stmt->error);
        }

    } elseif ($method === 'GET') {
        // Handle move history retrieval
        $game_id = isset($_GET['game_id']) ? $_GET['game_id'] : null;

        if (!$game_id) {
            throw new Exception('Game ID is required');
        }

        $stmt = $conn->prepare("SELECT * FROM moves WHERE game_id = ? ORDER BY move_number ASC");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $moves = [];
        while ($row = $result->fetch_assoc()) {
            $row['from_square'] = json_decode($row['from_square'], true);
            $row['to_square']   = json_decode($row['to_square'], true);
            $row['esoteric']    = $row['esoteric'] ? json_decode($row['esoteric'], true) : null;

            // Always use coord_from, coord_mid, coord_to for move_data['coords']
            $coords = [];
            if (isset($row['coord_from'])) {
                $coords[] = $row['coord_from'];
            } else {
                $coords[] = isset($row['from_square']['coord']) ? $row['from_square']['coord'] : '';
            }
            if (isset($row['coord_mid']) && $row['coord_mid'] !== null && $row['coord_mid'] !== '') {
                $coords[] = $row['coord_mid'];
            }
            if (isset($row['coord_to'])) {
                $coords[] = $row['coord_to'];
            } else {
                $coords[] = isset($row['to_square']['coord']) ? $row['to_square']['coord'] : '';
            }

            // If exoteric, remove the middle element
            if (count($coords) === 3 && ($coords[1] === null || $coords[1] === '')) {
                $coords = [$coords[0], $coords[2]];
            }

            $row['move_data'] = [
                'coords' => $coords,
                'hexagrams' => [
                    isset($row['hex_from']) ? $row['hex_from'] : '',
                    isset($row['hex_mid']) ? $row['hex_mid'] : '',
                    isset($row['hex_to']) ? $row['hex_to'] : ''
                ],
                'keywords' => [
                    isset($row['keyword_from']) ? $row['keyword_from'] : '',
                    isset($row['keyword_mid']) ? $row['keyword_mid'] : '',
                    isset($row['keyword_to']) ? $row['keyword_to'] : ''
                ]
            ];
            $moves[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'moves'   => $moves
        ]);

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

$conn->close();
