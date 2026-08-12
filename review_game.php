<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Game - Intrachange</title>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f8f5 0%, #e8f1e8 100%);
            min-height: 100vh;
            color: #2d3d2d;
        }

        .header {
            background: linear-gradient(135deg, #8a9d8a 0%, #7a8d7a 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .game-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 0.75rem;
            font-size: 1rem;
        }

        .player-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .player-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
        }

        .white { background-color: white; }
        .black { background-color: #333; }

        .back-nav {
            padding: 1rem 1.5rem;
            background: white;
            border-bottom: 1px solid #e8f1e8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-button {
            padding: 0.5rem 1rem;
            background: #8a9d8a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .back-button:hover {
            background: #7a8d7a;
            transform: translateY(-1px);
        }

        .game-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: calc(100vh - 200px);
            gap: 0;
        }

        /* Left Column - Static Board */
        .board-column {
            background: white;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border-right: 1px solid #e8f1e8;
        }

        .board-container {
            position: relative;
            display: inline-block;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            border: 2px solid #34495e;
            padding: 0.5rem;
        }

        #reviewChessboard {
            border-collapse: collapse;
            width: 464px !important;
            height: 464px !important;
            table-layout: fixed;
            background: #fff;
        }

        #reviewChessboard td {
            width: 58px !important;
            height: 58px !important;
            box-sizing: border-box;
            position: relative;
            border: 1px solid #333;
            text-align: center;
            vertical-align: middle;
            font-family: sans-serif;
            font-size: 9px;
            line-height: 1.1;
            padding: 0;
            cursor: pointer;
            user-select: none;
            background-color: #f0d9b5 !important;
            color: #000;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        #reviewChessboard td.white {
            background-color: #f0d9b5 !important;
            color: #000;
        }

        #reviewChessboard td.black {
            background-color: #b58863 !important;
            color: #fff;
        }

        #reviewChessboard td:hover {
            transform: scale(1.02);
            z-index: 10;
        }

        #reviewChessboard td.highlight-from {
            background-color: rgba(255, 235, 59, 0.8) !important;
            box-shadow: 0 0 8px rgba(255, 193, 7, 0.8), inset 0 0 0 2px #ff6b35;
        }

        #reviewChessboard td.highlight-to {
            background-color: rgba(76, 175, 80, 0.7) !important;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.8), inset 0 0 0 2px #2d8f2d;
        }

        #reviewChessboard td.highlight-path {
            background-color: #b3d9ff !important;
            box-shadow: inset 0 0 0 1px #4a90e2;
        }

        /* Coordinate labels - matching main game board */
        .coord-label {
            position: absolute;
            top: 1px;
            left: 1px;
            font-size: 7px;
            color: rgba(0,0,0,0.6);
            user-select: none;
            z-index: 12;
            font-weight: bold;
        }

        #reviewChessboard td.black .coord-label {
            color: rgba(255,255,255,0.8);
        }

        /* Hexagram elements - matching main game board */
        .hexNum {
            position: absolute;
            top: 1px;
            right: 1px;
            font-weight: bold;
            font-size: 8px;
            color: rgba(0,0,0,0.8);
            z-index: 12;
        }

        #reviewChessboard td.black .hexNum {
            color: rgba(255,255,255,0.8);
        }

        .hex-keyword {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: bold;
            line-height: 1.1;
            user-select: none;
            pointer-events: none;
            color: rgba(0,0,0,0.9);
            z-index: 11;
            text-align: center;
            width: 100%;
        }

        #reviewChessboard td.black .hex-keyword {
            color: rgba(0,0,0,0.9);
        }

        .hex-img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: 32px;
            width: auto;
            pointer-events: none;
            opacity: 0.6;
            display: none;
            z-index: 5;
        }

        .hex-img.show {
            display: block;
        }

        /* Chess pieces - matching main game board */
        .piece {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.8em;
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

        .piece-slot {
            font-size: 1.8em;
            line-height: 1;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            position: relative;
            z-index: 15;
        }

        .board-controls {
            margin-top: 1rem;
            text-align: center;
        }

        /* Toggle hexagrams button - matching main game board */
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

        #reviewChessboard {
            width: 100%;
            height: auto;
            border-collapse: collapse;
            aspect-ratio: 1;
        }



        .board-title {
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            color: #4a554a;
            font-weight: 600;
        }

        /* Right Column - Move Details */
        .move-column {
            background: #fafcfa;
            padding: 2rem;
            overflow-y: auto;
        }

        .move-navigation {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
        }

        .nav-button {
            padding: 0.75rem 1.25rem;
            background: #8a9d8a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .nav-button:hover:not(:disabled) {
            background: #7a8d7a;
            transform: translateY(-1px);
        }

        .nav-button:disabled {
            background: #d0d8d0;
            cursor: not-allowed;
            transform: none;
        }

        .move-counter {
            font-size: 1.1rem;
            font-weight: 600;
            color: #4a554a;
        }

        .move-details-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .move-header {
            background: linear-gradient(135deg, #f0f8f0 0%, #e8f1e8 100%);
            padding: 1.5rem;
            border-bottom: 1px solid #e8f1e8;
        }

        .move-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #4a554a;
            margin-bottom: 0.5rem;
        }

        .move-meta {
            color: #6a746a;
            font-size: 0.95rem;
        }

        .move-content {
            padding: 2rem;
            line-height: 1.8;
            color: #4a554a;
        }

        .section-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: #4a554a;
            margin: 1.5rem 0 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header:first-child {
            margin-top: 0;
        }

        .move-line {
            margin: 0.5rem 0;
            font-size: 1rem;
            color: #4a554a;
        }

        .move-line.piece-info {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d3d2d;
        }

        .move-line:empty {
            display: none;
        }

        .chess-move {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #2d3d2d;
            font-size: 1.04rem;
            background-color: rgba(138, 157, 138, 0.1);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border-left: 3px solid #8a9d8a;
        }

        .chess-move .arrow-icon {
            font-weight: bold !important;
            color: #2d3d2d !important;
            font-size: 2.25em !important;
            margin: 0 0.5rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }

        .hexagram-line {
            font-size: 1rem;
            color: #4a554a;
        }

        .esoteric-text {
            font-style: italic;
            color: #5a645a;
            background: #f8f9f8;
            padding: 1rem;
            border-radius: 6px;
            border-left: 3px solid #8a9d8a;
            margin: 0.75rem 0;
        }

        .move-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 0.75rem 0;
        }

        .user-comment-block {
            background: #f8f9f8;
            border-left: 4px solid #8a9d8a;
            border-radius: 0 8px 8px 0;
            padding: 1.25rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #5a645a;
            line-height: 1.7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid rgba(138, 157, 138, 0.2);
        }

        /* Image/URL Viewer - floated right in L-shaped layout */
        .image-viewer {
            float: right;
            max-width: 280px;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .move-symbolism-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            background: white;
        }

        .reference-link-box {
            background: white;
            border: 1px solid #e0e8e0;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            text-align: center;
        }

        .reference-link-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a554a;
            margin-bottom: 0.5rem;
        }

        .reference-link {
            color: #8a9d8a;
            text-decoration: none;
            font-size: 0.85rem;
            word-break: break-all;
            line-height: 1.4;
        }

        .reference-link:hover {
            color: #7a8d7a;
            text-decoration: underline;
        }

        .arrow-icon {
            font-size: 0.9rem;
            color: #8a9d8a;
            margin: 0 0.5rem;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #8a9a8a;
        }

        .no-moves {
            text-align: center;
            padding: 3rem;
            color: #8a9a8a;
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .game-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .board-column {
                padding: 1rem;
                border-right: none;
                border-bottom: 1px solid #e8f1e8;
            }
            
            .move-column {
                padding: 1rem;
            }
            
            .field-grid {
                grid-template-columns: 1fr;
            }
            
            .game-info {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <script>
    // Temporary safeFetch polyfill
    if (!window.safeFetch) {
        window.safeFetch = function(url, options) {
            return fetch(url, options);
        };
    }
    </script>
    <div class="header">
        <h1 id="game-title">Game Review</h1>
        <div class="game-info" id="game-info">
            <div class="loading">Loading game information...</div>
        </div>
    </div>

    <div class="back-nav">
        <a href="archive_display.html" class="back-button">← Back to Archive</a>
        <div class="move-navigation">
            <button class="nav-button" id="prev-btn" onclick="previousMove()" disabled>← Previous</button>
            <div class="move-counter" id="move-counter">Loading...</div>
            <button class="nav-button" id="next-btn" onclick="nextMove()" disabled>Next →</button>
        </div>
    </div>

    <div class="game-container">
        <!-- Left Column: Static Board -->
        <div class="board-column">
            <div class="board-container">
                <table id="reviewChessboard">
                    <tbody>
                        <!-- Board will be populated by JavaScript -->
                    </tbody>
                </table>
                <div class="board-controls">
                    <label class="toggle-hexagrams">
                        <input type="checkbox" id="toggleHexagrams"> Show Hexagrams
                    </label>
                </div>
            </div>
        </div>

        <!-- Right Column: Move Details -->
        <div class="move-column">
            <div id="move-details-container">
                <div class="loading">Loading game data...</div>
            </div>
        </div>
    </div>

    <script>
        let gameData = null;
        let moves = [];
        let currentMoveIndex = 0;

        // Board reconstruction variables
        const gameStates = [];
        const moveData = [
            { piece: 'WN', from: 10, to: 36, esoteric: null },
            { piece: 'BP', from: 44, to: 4, esoteric: 36 },
            { piece: 'WP', from: 16, to: 32, esoteric: 0 },
            { piece: 'BP', from: 46, to: 6, esoteric: 38 },
            { piece: 'WN', from: 13, to: 16, esoteric: null },
            { piece: 'BQ', from: 55, to: 44, esoteric: 46 },
            { piece: 'WN', from: 16, to: 36, esoteric: null },
            { piece: 'BP', from: 42, to: 26, esoteric: 58 },
            { piece: 'WN', from: 36, to: 7, esoteric: null },
            { piece: 'BQ', from: 46, to: 62, esoteric: null },
            { piece: 'WQ', from: 15, to: 6, esoteric: 4 },
            { piece: 'BB', from: 52, to: 42, esoteric: 46 },
            { piece: 'WQ', from: 4, to: 44, esoteric: null },
            { piece: 'BK', from: 48, to: 55, esoteric: null },
            { piece: 'WQ', from: 44, to: 50, esoteric: 48 },
            { piece: 'BP', from: 58, to: 34, esoteric: null },
            { piece: 'WP', from: 17, to: 33, esoteric: 1 },
            { piece: 'BP', from: 34, to: 20, esoteric: 52 },
            { piece: 'WQ', from: 48, to: 43, esoteric: 41 },
            { piece: 'BN', from: 50, to: 28, esoteric: null },
            { piece: 'WQ', from: 41, to: 4, esoteric: null },
            { piece: 'BN', from: 28, to: 0, esoteric: 8 }
        ];
        
        // Starting position
        const startingPosition = {
            0: 'BR', 1: 'BN', 2: 'BB', 3: 'BQ', 4: 'BK', 5: 'BB', 6: 'BN', 7: 'BR',
            8: 'BP', 9: 'BP', 10: 'BP', 11: 'BP', 12: 'BP', 13: 'BP', 14: 'BP', 15: 'BP',
            48: 'WP', 49: 'WP', 50: 'WP', 51: 'WP', 52: 'WP', 53: 'WP', 54: 'WP', 55: 'WP',
            56: 'WR', 57: 'WN', 58: 'WB', 59: 'WQ', 60: 'WK', 61: 'WB', 62: 'WN', 63: 'WR'
        };
        
        // Piece symbols
        const pieceSymbols = {
            WP: '♙', WR: '♖', WN: '♘', WB: '♗', WQ: '♕', WK: '♔',
            BP: '♟', BR: '♜', BN: '♞', BB: '♝', BQ: '♛', BK: '♚'
        };
        
        // Hexagram to chess coordinate mapping (reverse of the main mapping)
        const hexToChess = {
            49: 'a8', 50: 'b8', 52: 'c8', 55: 'd8', 48: 'e8', 51: 'f8', 53: 'g8', 54: 'h8',
            41: 'a7', 42: 'b7', 44: 'c7', 47: 'd7', 40: 'e7', 43: 'f7', 45: 'g7', 46: 'h7',
            25: 'a6', 26: 'b6', 28: 'c6', 31: 'd6', 24: 'e6', 27: 'f6', 29: 'g6', 30: 'h6',
            1: 'a5', 2: 'b5', 4: 'c5', 7: 'd5', 0: 'e5', 3: 'f5', 5: 'g5', 6: 'h5',
            57: 'a4', 58: 'b4', 60: 'c4', 63: 'd4', 56: 'e4', 59: 'f4', 61: 'g4', 62: 'h4',
            33: 'a3', 34: 'b3', 36: 'c3', 39: 'd3', 32: 'e3', 35: 'f3', 37: 'g3', 38: 'h3',
            17: 'a2', 18: 'b2', 20: 'c2', 23: 'd2', 16: 'e2', 19: 'f2', 21: 'g2', 22: 'h2',
            9: 'a1', 10: 'b1', 12: 'c1', 15: 'd1', 8: 'e1', 11: 'f1', 13: 'g1', 14: 'h1'
        };
        
        // Convert hexagram number to array index (0-63)
        function hexToIndex(hexNum) {
            const coord = hexToChess[hexNum];
            if (!coord) return null;
            
            const file = coord[0];
            const rank = coord[1];
            const col = file.charCodeAt(0) - 97; // a=0, b=1, etc.
            const row = 8 - parseInt(rank);      // rank 8=0, rank 1=7
            return row * 8 + col;
        }
        
        // Convert array index to chess coordinate
        function indexToChess(index) {
            const row = Math.floor(index / 8);
            const col = index % 8;
            const file = String.fromCharCode(97 + col);
            const rank = 8 - row;
            return `${file}${rank}`;
        }
        
        // Helper function to get hexagram keywords  
        function getHexagramKeyword(hexNum) {
            const keywords = {
                0: 'Essence', 1: 'Revelation', 2: 'Intuition', 3: 'Emanation', 4: 'Identification', 5: 'Interpretation', 6: 'Cultivation', 7: 'Orientation',
                8: 'Devotion', 9: 'Sensitization', 10: 'Incongruity', 11: 'Projection', 12: 'Authenticity', 13: 'Induction', 14: 'Inhibition', 15: 'Emancipation',
                16: 'Conceptualization', 17: 'Congruity', 18: 'Certainty', 19: 'Instruction', 20: 'Tradition', 21: 'Rationality', 22: 'Preconception', 23: 'Noninterference',
                24: 'Participation', 25: 'Persuasion', 26: 'Ambition', 27: 'Provocation', 28: 'Regeneration', 29: 'Eradication', 30: 'Evolution', 31: 'Transcendence',
                32: 'Experience', 33: 'Compulsion', 34: 'Reflection', 35: 'Propagation', 36: 'Resilience', 37: 'Potentiality', 38: 'Utilization', 39: 'Individuation',
                40: 'Confrontation', 41: 'Gratification', 42: 'Irrationality', 43: 'Nonsublimation', 44: 'Improvisation', 45: 'Uncertainty', 46: 'Specialization', 47: 'Synchronization',
                48: 'Internalization', 49: 'Communication', 50: 'Recollection', 51: 'Concentration', 52: 'Self-Sufficiency', 53: 'Disorientation', 54: 'Extinction', 55: 'Nonspecialization',
                56: 'Recapitulation', 57: 'Celebration', 58: 'Compliance', 59: 'Inspiration', 60: 'Reintegration', 61: 'Habituation', 62: 'Disintegration', 63: 'Existence'
            };
            return keywords[hexNum] || `Hex ${hexNum}`;
        }

        // Complete hexagram data for all 64 squares using positionMap
        const hexagramData = {};
        const positionMap = [
            { index: 0, hexNum: 49 }, { index: 1, hexNum: 50 }, { index: 2, hexNum: 52 }, { index: 3, hexNum: 55 },
            { index: 4, hexNum: 48 }, { index: 5, hexNum: 51 }, { index: 6, hexNum: 53 }, { index: 7, hexNum: 54 },
            { index: 8, hexNum: 41 }, { index: 9, hexNum: 42 }, { index: 10, hexNum: 44 }, { index: 11, hexNum: 47 },
            { index: 12, hexNum: 40 }, { index: 13, hexNum: 43 }, { index: 14, hexNum: 45 }, { index: 15, hexNum: 46 },
            { index: 16, hexNum: 25 }, { index: 17, hexNum: 26 }, { index: 18, hexNum: 28 }, { index: 19, hexNum: 31 },
            { index: 20, hexNum: 24 }, { index: 21, hexNum: 27 }, { index: 22, hexNum: 29 }, { index: 23, hexNum: 30 },
            { index: 24, hexNum: 1 }, { index: 25, hexNum: 2 }, { index: 26, hexNum: 4 }, { index: 27, hexNum: 7 },
            { index: 28, hexNum: 0 }, { index: 29, hexNum: 3 }, { index: 30, hexNum: 5 }, { index: 31, hexNum: 6 },
            { index: 32, hexNum: 57 }, { index: 33, hexNum: 58 }, { index: 34, hexNum: 60 }, { index: 35, hexNum: 63 },
            { index: 36, hexNum: 56 }, { index: 37, hexNum: 59 }, { index: 38, hexNum: 61 }, { index: 39, hexNum: 62 },
            { index: 40, hexNum: 33 }, { index: 41, hexNum: 34 }, { index: 42, hexNum: 36 }, { index: 43, hexNum: 39 },
            { index: 44, hexNum: 32 }, { index: 45, hexNum: 35 }, { index: 46, hexNum: 37 }, { index: 47, hexNum: 38 },
            { index: 48, hexNum: 17 }, { index: 49, hexNum: 18 }, { index: 50, hexNum: 20 }, { index: 51, hexNum: 23 },
            { index: 52, hexNum: 16 }, { index: 53, hexNum: 19 }, { index: 54, hexNum: 21 }, { index: 55, hexNum: 22 },
            { index: 56, hexNum: 9 }, { index: 57, hexNum: 10 }, { index: 58, hexNum: 12 }, { index: 59, hexNum: 15 },
            { index: 60, hexNum: 8 }, { index: 61, hexNum: 11 }, { index: 62, hexNum: 13 }, { index: 63, hexNum: 14 }
        ];
        
        // Populate hexagramData with keywords
        positionMap.forEach(pos => {
            hexagramData[pos.index] = {
                hexNum: pos.hexNum,
                keyword: getHexagramKeyword(pos.hexNum)
            };
        });

        // Get game ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const gameId = urlParams.get('game_id');
        
        if (!gameId) {
            document.getElementById('move-details-container').innerHTML = '<div class="no-moves"><p>No game ID provided.</p></div>';
        } else {
            loadGameData(gameId);
        }

        function loadGameData(gameId) {
            window.safeFetch(`backend/get_game_moves.php?game_id=${gameId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        gameData = data.game;
                        moves = data.moves;
                        displayGameInfo(data.game, gameId);
                        if (moves.length > 0) {
                            showMove(0);
                        } else {
                            document.getElementById('move-details-container').innerHTML = '<div class="no-moves"><p>No moves recorded for this game.</p></div>';
                        }
                    } else {
                        useMockData(gameId);
                    }
                })
                .catch(error => {
                    console.error('Database not available, using mock data:', error);
                    useMockData(gameId);
                });
        }

        function useMockData(gameId) {
            // Convert our moveData to the format expected by the UI
            gameData = {
                white_player: 'Player 1',
                black_player: 'Player 2',
                game_status: 'completed',
                created_at: '2024-11-15 14:30:00'
            };
            
            // Convert moveData array to moves format with proper chess coordinates
            moves = moveData.map((move, index) => ({
                id: index + 1,
                game_id: gameId,
                move_number: index + 1,
                piece: move.piece,
                from_position: hexToChess[move.from] || '',
                to_position: hexToChess[move.to] || '',
                esoteric_position: move.esoteric !== null ? hexToChess[move.esoteric] : null,
                coord_from: hexToChess[move.from] || '',
                coord_to: hexToChess[move.to] || '',
                coord_mid: move.esoteric !== null ? hexToChess[move.to] : null,
                player: move.piece_type.charAt(0) === 'W' ? 'white' : 'black',
                player_name: move.piece_type.charAt(0) === 'W' ? 'Player 1' : 'Player 2',
                created_at: '2024-11-15 14:30:00',
                // Database field that will be populated when connected to real data
                user_comment: null,  // This will contain actual user_comment from moves table
                hex_from: move.from,
                hex_to: move.to,
                hex_esoteric: move.esoteric,
                keyword_from: getHexagramKeyword(move.from),
                keyword_to: getHexagramKeyword(move.to),
                keyword_esoteric: move.esoteric !== null ? getHexagramKeyword(move.esoteric) : null
            }));
            
            displayGameInfo(gameData, gameId);
            if (moves.length > 0) {
                showMove(0);
            }
        }
        
        // Helper function to get piece names
        function getPieceName(piece) {
            const names = {
                'WP': 'Pawn', 'WR': 'Rook', 'WN': 'Knight', 'WB': 'Bishop', 'WQ': 'Queen', 'WK': 'King',
                'BP': 'Pawn', 'BR': 'Rook', 'BN': 'Knight', 'BB': 'Bishop', 'BQ': 'Queen', 'BK': 'King'
            };
            return names[piece] || piece;
        }
        
        function displayGameInfo(game, gameId) {
            document.getElementById('game-title').textContent = `Game Review #${gameId}`;
            document.getElementById('game-info').innerHTML = `
                <div class="player-info">
                    <div class="player-color white"></div>
                    <span>White: ${game.white_player || 'Unknown'}</span>
                </div>
                <div class="player-info">
                    <div class="player-color black"></div>
                    <span>Black: ${game.black_player || 'Unknown'}</span>
                </div>
            `;
        }

        function showMove(index) {
            if (index < 0 || index >= moves.length) return;
            
            currentMoveIndex = index;
            const move = moves[index];
            
            // Update navigation
            document.getElementById('move-counter').textContent = `Move ${index + 1} of ${moves.length}`;
            document.getElementById('prev-btn').disabled = index === 0;
            document.getElementById('next-btn').disabled = index === moves.length - 1;
            
            // Display move details
            displayMoveDetails(move, index + 1);
            
            // Update board state
            renderBoardState(index + 1);
        }
        
        function reconstructGameStates() {
            gameStates[0] = { ...startingPosition };
            
            for (let i = 0; i < moveData.length; i++) {
                const currentState = { ...gameStates[i] };
                const move = moveData[i];
                
                // Convert hexagram numbers to board indices
                const fromIndex = hexToIndex(move.from);
                const toIndex = hexToIndex(move.to);
                const esotericIndex = move.esoteric !== null ? hexToIndex(move.esoteric) : null;
                
                if (fromIndex === null || toIndex === null) {
                    console.error(`Invalid hexagram numbers in move ${i + 1}:`, move);
                    continue;
                }
                
                // Remove piece from starting position
                delete currentState[fromIndex];
                
                // Place piece at destination (esoteric if available, otherwise normal)
                const destination = esotericIndex !== null ? esotericIndex : toIndex;
                currentState[destination] = move.piece;
                
                gameStates[i + 1] = currentState;
            }
        }
        
        function createChessboard() {
            const chessboard = document.getElementById('reviewChessboard');
            if (!chessboard) return;
            
            chessboard.innerHTML = '';
            
            for (let row = 0; row < 8; row++) {
                const tr = document.createElement('tr');
                for (let col = 0; col < 8; col++) {
                    const td = document.createElement('td');
                    td.className = (row + col) % 2 === 0 ? 'white' : 'black';
                    td.dataset.row = row;
                    td.dataset.col = col;
                    td.dataset.index = row * 8 + col;
                    
                    // Add coordinate and hexagram info immediately
                    const index = row * 8 + col;
                    const square = indexToChess(index);
                    
                    // Add coordinate label
                    const coordLabel = document.createElement('div');
                    coordLabel.className = 'coord-label';
                    coordLabel.textContent = square;
                    td.appendChild(coordLabel);
                    
                    // Add hexagram info
                    if (hexagramData[index]) {
                        const hexNum = hexagramData[index].hexNum;
                        
                        const hexNumDiv = document.createElement('div');
                        hexNumDiv.className = 'hexNum';
                        hexNumDiv.textContent = hexNum;
                        td.appendChild(hexNumDiv);
                        
                        // Add hexagram image (hidden by default)
                        const hexImg = document.createElement('img');
                        hexImg.className = 'hex-img';
                        hexImg.src = `img/hex/hexagram${hexNum}.svg`;
                        hexImg.alt = `Hexagram ${hexNum}`;
                        td.appendChild(hexImg);
                    }
                    
                    // Add piece slot
                    const pieceSlot = document.createElement('div');
                    pieceSlot.className = 'piece-slot';
                    td.appendChild(pieceSlot);
                    
                    tr.appendChild(td);
                }
                chessboard.appendChild(tr);
            }
            
            // Set up hexagram toggle functionality
            const toggleHexagrams = document.getElementById('toggleHexagrams');
            if (toggleHexagrams) {
                toggleHexagrams.addEventListener('change', function() {
                    const hexImages = document.querySelectorAll('.hex-img');
                    hexImages.forEach(img => {
                        img.style.display = this.checked ? 'block' : 'none';
                    });
                });
            }
        }
        
        function renderBoardState(moveIndex) {
            const chessboard = document.getElementById('reviewChessboard');
            if (!chessboard || !gameStates[moveIndex]) return;
            
            const boardState = gameStates[moveIndex];
            
            // Clear previous highlights, keywords, and update pieces
            chessboard.querySelectorAll('td').forEach(td => {
                td.classList.remove('highlight-from', 'highlight-to', 'highlight-path');
                
                // Remove any existing keywords
                const existingKeyword = td.querySelector('.hex-keyword');
                if (existingKeyword) {
                    existingKeyword.remove();
                }
                
                const index = parseInt(td.dataset.index);
                const pieceSlot = td.querySelector('.piece-slot');
                
                if (pieceSlot) {
                    // Update piece content
                    const piece = boardState[index];
                    if (piece && pieceSymbols[piece]) {
                        pieceSlot.innerHTML = `<span class="piece ${piece.charAt(0) === 'W' ? 'white-piece' : 'black-piece'}">${pieceSymbols[piece]}</span>`;
                    } else {
                        pieceSlot.innerHTML = '';
                    }
                }
            });
            
            // Add keywords only to squares involved in current move
            if (moveIndex > 0) {
                const move = moveData[moveIndex - 1];
                if (move) {
                    // Get the actual move data from the moves array for hexagram info
                    const currentMove = moves[moveIndex - 1];
                    const moveSquares = [];
                    
                    // Add from square
                    if (currentMove && currentMove.hex_from !== null && currentMove.hex_from !== undefined) {
                        const fromIndex = hexToIndex(move.from);
                        if (fromIndex !== null) {
                            moveSquares.push({
                                index: fromIndex,
                                hexNum: currentMove.hex_from,
                                keyword: currentMove.keyword_from
                            });
                        }
                    }
                    
                // Add to square  
                if (currentMove && currentMove.hex_to !== null && currentMove.hex_to !== undefined) {
                    const toIndex = hexToIndex(currentMove.hex_to);
                    if (toIndex !== null) {
                        moveSquares.push({
                            index: toIndex,
                            hexNum: currentMove.hex_to,
                            keyword: currentMove.keyword_to
                        });
                    }
                }
                
                // Add esoteric square if present (this is the middle square in 3-square moves)
                if (move.esoteric !== null && currentMove && currentMove.hex_mid !== null && currentMove.hex_mid !== undefined) {
                    const esotericIndex = hexToIndex(currentMove.hex_mid);
                    if (esotericIndex !== null) {
                        moveSquares.push({
                            index: esotericIndex,
                            hexNum: currentMove.hex_mid,
                            keyword: currentMove.keyword_mid
                        });
                    }
                }                    // Add keywords to the relevant squares
                    moveSquares.forEach(square => {
                        const cell = chessboard.querySelector(`[data-index="${square.index}"]`);
                        if (cell && square.keyword) {
                            const keywordDiv = document.createElement('div');
                            keywordDiv.className = 'hex-keyword';
                            keywordDiv.textContent = square.keyword;
                            cell.appendChild(keywordDiv);
                        }
                    });
                }
            }
            
            // Highlight current move if not at starting position
            if (moveIndex > 0) {
                const move = moveData[moveIndex - 1];
                if (move) {
                    // Convert hexagram numbers to indices for highlighting
                    const fromIndex = hexToIndex(move.from);
                    const toIndex = hexToIndex(move.to);
                    const esotericIndex = move.esoteric !== null ? hexToIndex(move.esoteric) : null;
                    
                    const fromCell = fromIndex !== null ? chessboard.querySelector(`[data-index="${fromIndex}"]`) : null;
                    const toCell = toIndex !== null ? chessboard.querySelector(`[data-index="${toIndex}"]`) : null;
                    const esotericCell = esotericIndex !== null ? chessboard.querySelector(`[data-index="${esotericIndex}"]`) : null;
                    
                    if (fromCell) fromCell.classList.add('highlight-from');
                    if (toCell && move.esoteric === null) toCell.classList.add('highlight-to');
                    if (esotericCell) esotericCell.classList.add('highlight-to');
                    if (toCell && move.esoteric !== null) toCell.classList.add('highlight-path');
                }
            }
        }

        function displayMoveDetails(move, moveNumber) {
            // Check for esoteric move data
            const hasEsoteric = move.esoteric_position_from || move.esoteric_position_to || 
                               move.esoteric_hex_from || move.esoteric_hex_to || 
                               move.esoteric_keyword_from || move.esoteric_keyword_to;

            // Build coordinate array like Game History window (coord_from, coord_mid, coord_to)
            let coords = [];
            
            // Add from coordinate
            const fromCoord = move.coord_from || move.position_from || move.from_position || move.from_coord || 
                             (move.from_square ? `${move.from_square.file}${move.from_square.rank}` : null);
            if (fromCoord) coords.push(fromCoord);
            
            // Add middle coordinate for esoteric moves (this is the key for three-square moves)
            const midCoord = move.coord_mid || move.position_mid || move.mid_position || move.mid_coord;
            if (midCoord && midCoord !== '' && midCoord !== null) coords.push(midCoord);
            
            // Add to coordinate  
            const toCoord = move.coord_to || move.position_to || move.to_position || move.to_coord || 
                           (move.to_square ? `${move.to_square.file}${move.to_square.rank}` : null);
            if (toCoord) coords.push(toCoord);

            let chessSection = '';
            if (coords.length > 0) {
                // Join coordinates with arrows like Game History window
                const coordsStr = coords.filter(Boolean).join(' <span class="arrow-icon">→</span> ');
                const pieceName = getPieceName(move.piece_type);
                const pieceColor = move.piece_type.charAt(0) === 'W' ? 'White' : 'Black';
                chessSection = `
                    <div class="section-header">Chess Move</div>
                    <div class="move-line piece-info">${pieceColor} ${pieceName}</div>
                    <div class="move-line chess-move">${coordsStr}</div>
                `;
            }

            let hexagramSection = '';
            if (move.hex_from !== null && move.hex_from !== undefined || 
                move.hex_to !== null && move.hex_to !== undefined || 
                move.keyword_from || move.keyword_to) {
                hexagramSection = `
                    <div class="section-header">☰ Hexagrams</div>
                    ${(move.hex_from !== null && move.hex_from !== undefined) || move.keyword_from ? 
                        `<div class="move-line hexagram-line">From: ${move.hex_from !== null && move.hex_from !== undefined ? move.hex_from : ''} ${(move.hex_from !== null && move.hex_from !== undefined) && move.keyword_from ? '—' : ''} ${move.keyword_from || ''}</div>` : ''}
                    ${(move.hex_mid !== null && move.hex_mid !== undefined) || move.keyword_mid ? 
                        `<div class="move-line hexagram-line">Mid: ${move.hex_mid !== null && move.hex_mid !== undefined ? move.hex_mid : ''} ${(move.hex_mid !== null && move.hex_mid !== undefined) && move.keyword_mid ? '—' : ''} ${move.keyword_mid || ''}</div>` : ''}
                    ${(move.hex_to !== null && move.hex_to !== undefined) || move.keyword_to ? 
                        `<div class="move-line hexagram-line">To: ${move.hex_to !== null && move.hex_to !== undefined ? move.hex_to : ''} ${(move.hex_to !== null && move.hex_to !== undefined) && move.keyword_to ? '—' : ''} ${move.keyword_to || ''}</div>` : ''}
                `;
            }

            let esotericSection = '';
            if (hasEsoteric) {
                let esotericLines = [];
                
                if (move.esoteric_position_from || move.esoteric_position_to) {
                    if (move.esoteric_position_from && move.esoteric_position_to) {
                        esotericLines.push(`${move.esoteric_position_from} <span class="arrow-icon">→</span> ${move.esoteric_position_to}`);
                    } else {
                        if (move.esoteric_position_from) esotericLines.push(`From: ${move.esoteric_position_from}`);
                        if (move.esoteric_position_to) esotericLines.push(`To: ${move.esoteric_position_to}`);
                    }
                }
                
                if ((move.esoteric_hex_from !== null && move.esoteric_hex_from !== undefined) || move.esoteric_keyword_from) {
                    esotericLines.push(`From: ${(move.esoteric_hex_from !== null && move.esoteric_hex_from !== undefined) ? move.esoteric_hex_from : ''} ${(move.esoteric_hex_from !== null && move.esoteric_hex_from !== undefined) && move.esoteric_keyword_from ? '—' : ''} ${move.esoteric_keyword_from || ''}`);
                }
                if ((move.esoteric_hex_to !== null && move.esoteric_hex_to !== undefined) || move.esoteric_keyword_to) {
                    esotericLines.push(`To: ${(move.esoteric_hex_to !== null && move.esoteric_hex_to !== undefined) ? move.esoteric_hex_to : ''} ${(move.esoteric_hex_to !== null && move.esoteric_hex_to !== undefined) && move.esoteric_keyword_to ? '—' : ''} ${move.esoteric_keyword_to || ''}`);
                }

                if (esotericLines.length > 0) {
                    esotericSection = `
                        <div class="section-header">🜁 Esoteric Move</div>
                        ${esotericLines.map(line => `<div class="move-line">${line}</div>`).join('')}
                    `;
                }
            }

            // Create floating image/URL viewer for L-shaped layout
            let imageViewer = '';
            
            // First check if there's a custom comment_url image
            const localImagePath = `backend/images/${moveNumber}.png`;
            
            if (move.comment_url && isImageUrl(move.comment_url)) {
                // Keep full URLs instead of converting to relative paths
                let imagePath = move.comment_url;
                
                console.log("Move", moveNumber, "comment_url:", move.comment_url);
                console.log("Final image path used:", imagePath);
                
                imageViewer = `
                    <div class="image-viewer">
                        <img id="ref-img-${move.id}" src="${imagePath}" alt="Reference Image" 
                             style="max-width:230px; height:auto; border:1px solid #ccc; border-radius:8px;" 
                             onload="console.log('Loaded OK:', '${imagePath}')"
                             onerror="console.error('Image failed:', '${imagePath}'); console.log('Move ${moveNumber} img element:', this); console.log('Actual src attr:', this.getAttribute('src'));" />
                    </div>
                `;
            } else {
                // Use local image as primary
                imageViewer = `
                    <div class="image-viewer">
                        <img id="ref-img-${move.id}" src="${localImagePath}" alt="Reference Image" 
                             style="max-width:230px; height:auto; border:1px solid #ccc; border-radius:8px;" 
                             onerror="this.style.display='none';" />
                    </div>
                `;
                
                // If there's a non-image comment_url, show it as a link
                if (move.comment_url) {
                    const displayUrl = move.comment_url.length > 40 
                        ? move.comment_url.substring(0, 37) + '...'
                        : move.comment_url;
                    imageViewer += `
                        <div class="reference-link-box">
                            <div class="reference-link-title">Reference Link</div>
                            <a href="${move.comment_url}" target="_blank" class="reference-link">${displayUrl}</a>
                        </div>
                    `;
                }
            }

            let commentSection = '';
            if (move.user_comment) {
                commentSection = `
                    <div class="section-header">Symbolism</div>
                    <div class="user-comment-block">${move.user_comment.replace(/\n/g, '<br>')}</div>
                `;
            }

            const html = `
                <div class="move-details-card">
                    <div class="move-header">
                        <div class="move-title">Move #${moveNumber}</div>
                        <div class="move-meta">by ${move.player_name || 'Unknown'} • ${formatDate(move.created_at)}</div>
                    </div>
                    <div class="move-content">
                        ${imageViewer}
                        ${chessSection}
                        ${hexagramSection}
                        ${esotericSection}
                        ${commentSection}
                        <div style="clear: both;"></div>
                    </div>
                </div>
            `;

            document.getElementById('move-details-container').innerHTML = html;
        }

        function previousMove() {
            if (currentMoveIndex > 0) {
                showMove(currentMoveIndex - 1);
            }
        }

        function nextMove() {
            if (currentMoveIndex < moves.length - 1) {
                showMove(currentMoveIndex + 1);
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function isImageUrl(url) {
            // Check for standard image extensions only
            return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url);
        }
        
        // Initialize the chessboard when page loads
        document.addEventListener('DOMContentLoaded', function() {
            reconstructGameStates();
            createChessboard();
            renderBoardState(0);
            
            // Hexagram toggle
            const hexToggle = document.getElementById('toggleHexagrams');
            if (hexToggle) {
                hexToggle.addEventListener('change', function() {
                    renderBoardState(currentMoveIndex + 1);
                });
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentMoveIndex > 0) {
                previousMove();
            } else if (e.key === 'ArrowRight' && currentMoveIndex < moves.length - 1) {
                nextMove();
            }
        });
    </script>
</body>
</html>
    </script>
</body>
</html>