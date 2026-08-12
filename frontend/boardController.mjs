// Debug function to inspect each move object and check array alignment
function debugMoveRendering(move) {
  console.group(`Debug Move #${move.move_number} - ${move.piece_type}`);
  console.log("Full move object:", move);
  console.log("move_data.coords:", move.move_data?.coords);
  console.log("move_data.hexagrams:", move.move_data?.hexagrams);
  console.log("move_data.keywords:", move.move_data?.keywords);

  if (move.move_data?.coords?.length !== move.move_data?.hexagrams?.length || 
    move.move_data?.coords?.length !== move.move_data?.keywords?.length) {
    console.warn("⚠️ Length mismatch detected!");
  }
  console.groupEnd();
}
// boardController.js

import { updateBoardPieces, highlightMoves, clearHighlights } from './ui.mjs';
import { getLegalMoves } from './movement.mjs';
import { handleEsotericMove, getEsotericTarget } from './esotericMoves.mjs';
import { positionMap } from './positionMap.mjs';
import { hexagrams, getKeywordFromHex } from './hexagrams.mjs';

// Starting chess board
const startingBoardState = [
  ['BR','BN','BB','BQ','BK','BB','BN','BR'],
  ['BP','BP','BP','BP','BP','BP','BP','BP'],
  [null, null, null, null, null, null, null, null],
  [null, null, null, null, null, null, null, null],
  [null, null, null, null, null, null, null, null],
  [null, null, null, null, null, null, null, null],
  ['WP','WP','WP','WP','WP','WP','WP','WP'],
  ['WR','WN','WB','WQ','WK','WB','WN','WR']
];

let boardState = [];
let currentTurn = 'W';
let selectedSquare = null;
let esotericMoves = true; // toggle for esoteric moves
let gameFinished = false; // track game state
// No-op async function to prevent ReferenceError for refreshGameState
async function refreshGameState() {}

// Initialize board
export function initializeBoard() {
  boardState = JSON.parse(JSON.stringify(startingBoardState));  // Deep copy
  currentTurn = 'W';
  selectedSquare = null;
  gameFinished = false; // Reset game state

  updateBoardPieces(boardState, handleSquareClick, hexagrams);
  clearHighlights();

  return boardState;
}

// Emergency function to force render pieces
function emergencyRenderBoard() {
  console.log('🆘 EMERGENCY BOARD RENDER');
  
  const chessboard = document.getElementById('chessboard');
  if (!chessboard) {
    console.error('❌ No chessboard element for emergency render');
    return;
  }
  
  // Clear everything first
  chessboard.innerHTML = '';
  
  // Recreate table structure if needed
  if (!chessboard.querySelector('tr')) {
    console.log('🔨 Recreating table structure');
    for (let row = 0; row < 8; row++) {
      const tr = document.createElement('tr');
      for (let col = 0; col < 8; col++) {
        const td = document.createElement('td');
        td.className = `square ${(row + col) % 2 === 0 ? 'light' : 'dark'}`;
        td.id = `square-${row}-${col}`;
        td.style.cssText = `
          width: 60px; height: 60px; 
          position: relative; 
          text-align: center; 
          vertical-align: middle;
          background-color: ${(row + col) % 2 === 0 ? '#f0d9b5' : '#b58863'};
          border: 1px solid #999;
        `;
        tr.appendChild(td);
      }
      chessboard.appendChild(tr);
    }
  }
  
  // Add pieces using the current board state
  const pieceSymbols = {
    WP: '♙', WR: '♖', WN: '♘', WB: '♗', WQ: '♕', WK: '♔',
    BP: '♟', BR: '♜', BN: '♞', BB: '♝', BQ: '♛', BK: '♚',
  };
  
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = boardState[row][col];
      if (piece) {
        const square = document.getElementById(`square-${row}-${col}`);
        if (square) {
          square.innerHTML = `<span style="font-size: 40px; line-height: 1;">${pieceSymbols[piece] || piece}</span>`;
          console.log(`🆘 Emergency placed ${piece} at [${row}][${col}]`);
        }
      }
    }
  }
  
  console.log('🆘 Emergency render complete');
}

// Add diagnostic function for debugging
export function debugBoardState() {
  console.log('🔍 BOARD DIAGNOSTIC:');
  console.log('📋 Current boardState:', boardState);
  console.log('🎯 Current turn:', currentTurn);
  console.log('🎮 Game finished:', gameFinished);
  
  // Count pieces
  let whitePieces = 0, blackPieces = 0;
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = boardState[row][col];
      if (piece) {
        if (piece.startsWith('W')) whitePieces++;
        if (piece.startsWith('B')) blackPieces++;
      }
    }
  }
  console.log(`🧮 Pieces: White=${whitePieces}, Black=${blackPieces}`);
  
  // Check DOM elements
  const chessboard = document.getElementById('chessboard');
  const gameBoard = document.getElementById('gameBoard');
  console.log('🎯 DOM elements:');
  console.log('  - #chessboard:', chessboard ? 'Found' : 'Not found');
  console.log('  - #gameBoard:', gameBoard ? 'Found' : 'Not found');
  
  if (chessboard) {
    const rows = chessboard.querySelectorAll('tr');
    console.log(`  - Chessboard rows: ${rows.length}`);
  }
  
  return {
    boardState,
    currentTurn,
    gameFinished,
    whitePieces,
    blackPieces,
    hasChessboard: !!chessboard,
    hasGameBoard: !!gameBoard
  };
}

// Initialize empty board for reconstruction
export function initializeEmptyBoard() {
  // Start with standard chess setup
  boardState = JSON.parse(JSON.stringify(startingBoardState));
  currentTurn = 'W';
  selectedSquare = null;
  gameFinished = false;
  
  console.log('🏁 Empty board initialized for reconstruction');
  console.log('📋 Starting position verified:', boardState[0]); // Show first row
  return boardState;
}

// Apply a single move to the board state
export function applyMoveToBoard(board, move) {
  console.log(`🔄 Applying move ${move.move_number}: ${move.piece_type} ${move.from_position} → ${move.to_position}`);
  
  try {
    // Parse move positions - handle both string and object formats
    let fromSquare, toSquare;
    
    if (move.from_square) {
      fromSquare = typeof move.from_square === 'string' ? JSON.parse(move.from_square) : move.from_square;
    }
    if (move.to_square) {
      toSquare = typeof move.to_square === 'string' ? JSON.parse(move.to_square) : move.to_square;
    }
    
    if (!fromSquare || !toSquare) {
      console.warn(`⚠️ Missing square data for move ${move.move_number}:`, { fromSquare, toSquare });
      console.warn(`⚠️ Raw move data:`, move);
      return board;
    }
    
    const { row: fromRow, col: fromCol } = fromSquare;
    const { row: toRow, col: toCol } = toSquare;
    
    // Validate coordinates
    if (fromRow < 0 || fromRow > 7 || fromCol < 0 || fromCol > 7 ||
        toRow < 0 || toRow > 7 || toCol < 0 || toCol > 7) {
      console.warn(`⚠️ Invalid coordinates for move ${move.move_number}: (${fromRow},${fromCol}) → (${toRow},${toCol})`);
      return board;
    }
    
    // Get the piece from source square
    const piece = board[fromRow][fromCol];
    
    if (!piece) {
      console.warn(`⚠️ No piece found at source square [${fromRow}][${fromCol}] for move ${move.move_number}`);
      console.warn(`⚠️ Board state at source:`, board[fromRow]);
      return board;
    }
    
    console.log(`✨ Moving piece "${piece}" from [${fromRow}][${fromCol}] to [${toRow}][${toCol}]`);
    
    // Handle esoteric moves (bounces)
    const isEsoteric = move.esoteric && (move.esoteric === true || move.esoteric === 'true' || 
                       (typeof move.esoteric === 'string' && move.esoteric !== 'null' && move.esoteric !== ''));
    
    if (isEsoteric) {
      console.log(`✨ Esoteric move detected for move ${move.move_number}`);
      
      // For esoteric moves, we need to handle the bounce
      // The piece moves to an intermediate square, then bounces to final position
      let moveData = null;
      
      if (move.move_data) {
        moveData = typeof move.move_data === 'string' ? JSON.parse(move.move_data) : move.move_data;
      }
      
      if (moveData && moveData.hexagrams && moveData.hexagrams.length >= 3) {
        // Three-part esoteric move: from → intermediate → final
        console.log(`🎯 Processing bounce: ${moveData.hexagrams[0]} → ${moveData.hexagrams[1]} → ${moveData.hexagrams[2]}`);
        
        // Move piece to final destination (the bounce is already calculated)
        board[toRow][toCol] = piece;
        board[fromRow][fromCol] = null;
        
        // Add visual indicator for esoteric moves if in real-time mode
        if (typeof addMoveToHistory === 'function') {
          addMoveToHistory({
            ...move,
            isEsoteric: true,
            bounceInfo: `Bounced via ${moveData.hexagrams[1]}`
          });
        }
      } else {
        // Simple esoteric move
        board[toRow][toCol] = piece;
        board[fromRow][fromCol] = null;
      }
    } else {
      // Regular (exoteric) move
      console.log(`👑 Exoteric move for move ${move.move_number}`);
      board[toRow][toCol] = piece;
      board[fromRow][fromCol] = null;
    }
    
    console.log(`✅ Move ${move.move_number} applied successfully. Piece at destination: ${board[toRow][toCol]}`);
    
    // Add hexagram state tracking for the move
    if (move.hex_from && move.hex_to) {
      console.log(`🔮 Hexagram transition: ${move.hex_from} (${move.keyword_from}) → ${move.hex_to} (${move.keyword_to})`);
    }
    
  } catch (error) {
    console.error(`❌ Error applying move ${move.move_number}:`, error);
    console.error(`❌ Move data:`, move);
  }
  
  return board;
}

// Reconstruct board from move history
export async function reconstructBoardFromMoves(gameId) {
  console.log(`🔄 Starting board reconstruction for game ${gameId}`);
  
  try {
    // Show reconstruction progress
    const loadingMsg = document.querySelector('.loading-message');
    if (loadingMsg) {
      loadingMsg.textContent = '🔄 Loading game data...';
    }
    
    // Get moves from API
    const response = await fetch(`backend/get_game_moves.php?game_id=${gameId}`);
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Failed to load game data');
    }
    
    const { game, moves } = data;
    console.log(`📚 Loaded ${moves.length} moves for reconstruction`);
    console.log('🎮 Game data:', game);
    console.log('📝 First few moves:', moves.slice(0, 3));
    
    if (loadingMsg) {
      loadingMsg.textContent = `🎯 Reconstructing board... (${moves.length} moves)`;
    }
    
    // Initialize board with starting position
    const board = initializeEmptyBoard();
    console.log('🏁 Starting board state:', board);
    
    // Apply each move in sequence - CRITICAL: Apply ALL moves regardless of player
    moves.forEach((move, index) => {
      debugMoveRendering(move);
      console.log(`🎯 Applying move ${index + 1}/${moves.length}: ${move.piece_type} ${move.from_position}→${move.to_position}`);
      
      // Debug: show board state before move
      if (index < 3) {
        console.log(`📋 Board before move ${index + 1}:`, JSON.parse(JSON.stringify(board)));
      }
      
      applyMoveToBoard(board, move);
      
      // Debug: show board state after move
      if (index < 3) {
        console.log(`📋 Board after move ${index + 1}:`, JSON.parse(JSON.stringify(board)));
      }
    });
    
    // Set the correct current turn based on move count and game state
    if (moves.length > 0) {
      // Calculate turn based on move count (moves are 1-indexed)
      const lastMoveNumber = moves[moves.length - 1].move_number;
      currentTurn = (lastMoveNumber % 2 === 1) ? 'B' : 'W'; // After white move, it's black's turn
    } else {
      currentTurn = 'W'; // White starts
    }
    
    // Override with game state if available
    if (game.current_turn) {
      currentTurn = game.current_turn === 'white' ? 'W' : 'B';
    }
    
    console.log(`✅ Board reconstruction complete! Current turn: ${currentTurn}`);
    console.log('📋 Final board state:', board);
    
    // Force render the reconstructed board
    renderBoard(board);
    
    return {
      board,
      game,
      moves,
      moveCount: moves.length
    };
    
  } catch (error) {
    console.error('❌ Board reconstruction failed:', error);
    throw error;
  }
}

// Render the board with current state
export function renderBoard(board) {
  console.log('🎨 Rendering reconstructed board');
  console.log('📋 Board to render:', board);
  
  // Update the global board state
  boardState = JSON.parse(JSON.stringify(board)); // Deep copy to avoid reference issues
  
  // Check what DOM elements we have available
  const chessboard = document.getElementById('chessboard');
  const gameBoard = document.getElementById('gameBoard');
  
  console.log('🎯 Available DOM elements:');
  console.log(`  - #chessboard: ${chessboard ? 'Found' : 'Not found'}`);
  console.log(`  - #gameBoard: ${gameBoard ? 'Found' : 'Not found'}`);
  
  let renderSuccess = false;
  
  // Force update the visual board - try different approaches
  try {
    // Method 1: Use the standard updateBoardPieces function
    if (typeof updateBoardPieces === 'function' && chessboard) {
      console.log('🎨 Trying updateBoardPieces...');
      updateBoardPieces(boardState, handleSquareClick, hexagrams);
      
      // Verify it worked by checking if pieces are rendered
      setTimeout(() => {
        const pieces = chessboard.querySelectorAll('.chess-piece, .piece');
        if (pieces.length > 0) {
          console.log(`✅ updateBoardPieces succeeded - ${pieces.length} pieces found`);
          renderSuccess = true;
        } else {
          console.log('⚠️ updateBoardPieces ran but no pieces found, trying fallback...');
          renderBoardToDom(boardState);
        }
      }, 50);
    } 
    // Method 2: Direct fallback
    else {
      console.log('🎨 Using direct fallback DOM rendering');
      renderBoardToDom(boardState);
      renderSuccess = true;
    }
    
    // Clear any existing highlights
    if (typeof clearHighlights === 'function') {
      clearHighlights();
    }
    
    // Update any UI indicators
    updateTurnIndicator();
    
    console.log('✅ Board rendering process completed');
    
    // Run diagnostic after rendering
    setTimeout(() => {
      const diagnostic = debugBoardState();
      if (diagnostic.whitePieces === 0 && diagnostic.blackPieces === 0) {
        console.warn('⚠️ No pieces found after rendering - forcing fallback');
        renderBoardToDom(boardState);
      }
    }, 100);
    
  } catch (error) {
    console.error('❌ Error rendering board:', error);
    // Final fallback: try direct DOM manipulation
    renderBoardToDom(boardState);
  }
}

// Fallback function to render board directly to DOM
function renderBoardToDom(board) {
  console.log('🎨 Fallback DOM rendering');
  
  const chessboard = document.getElementById('chessboard');
  if (!chessboard) {
    console.error('❌ No chessboard element found for fallback rendering');
    return;
  }
  
  // Check if we have a table structure
  const rows = chessboard.querySelectorAll('tr');
  if (rows.length !== 8) {
    console.error('❌ Chessboard table structure invalid - expected 8 rows, found:', rows.length);
    return;
  }
  
  console.log('🎯 Found chessboard table with 8 rows, rendering pieces...');
  
  // Clear existing pieces first
  const allSquares = chessboard.querySelectorAll('td.square');
  allSquares.forEach(square => {
    // Remove any existing piece content but keep coordinate labels and hex numbers
    const existingPieces = square.querySelectorAll('.piece, .chess-piece');
    existingPieces.forEach(piece => piece.remove());
    
    // Also clear text content that might be pieces
    const childNodes = Array.from(square.childNodes);
    childNodes.forEach(node => {
      if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().match(/[♔♕♖♗♘♙♚♛♜♝♞♟]/)) {
        node.remove();
      }
    });
  });
  
  // Add pieces based on board state
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = board[row][col];
      if (piece) {
        const squareElement = document.getElementById(`square-${row}-${col}`);
        if (squareElement) {
          // Create piece element
          const pieceElement = document.createElement('div');
          pieceElement.className = 'chess-piece';
          pieceElement.style.cssText = `
            font-size: 40px;
            line-height: 1;
            user-select: none;
            pointer-events: none;
            z-index: 10;
            position: relative;
          `;
          
          // Map piece codes to Unicode symbols
          const pieceSymbols = {
            WP: '♙', WR: '♖', WN: '♘', WB: '♗', WQ: '♕', WK: '♔',
            BP: '♟', BR: '♜', BN: '♞', BB: '♝', BQ: '♛', BK: '♚',
          };
          
          pieceElement.textContent = pieceSymbols[piece] || piece;
          squareElement.appendChild(pieceElement);
          
          console.log(`✅ Placed ${piece} (${pieceSymbols[piece]}) at [${row}][${col}]`);
        } else {
          console.warn(`⚠️ Could not find square element for [${row}][${col}]`);
        }
      }
    }
  }
  
  console.log('✅ Fallback DOM rendering complete');
  
  // Count rendered pieces for verification
  const renderedPieces = chessboard.querySelectorAll('.chess-piece');
  console.log(`🧮 Rendered ${renderedPieces.length} pieces on board`);
}

// Resume game function - called by resume page
export function resumeGame(resumeData) {
  console.log('🎮 Starting game resume with data:', resumeData);
  if (resumeData && resumeData.user) {
    console.log('👤 resumeData.user:', resumeData.user);
    console.log('  - user.id:', resumeData.user.id);
    console.log('  - user.username:', resumeData.user.username);
    console.log('  - user.can_move:', resumeData.user.can_move);
  } else {
    console.log('👤 No user object in resumeData!');
  }
  console.log('  - resumeData.my_color:', resumeData.my_color);
  console.log('  - resumeData.current_turn:', resumeData.current_turn);
  console.log('  - resumeData.white_player_id:', resumeData.white_player_id);
  console.log('  - resumeData.black_player_id:', resumeData.black_player_id);
  
  if (!resumeData || !resumeData.game_id) {
    console.error('❌ Invalid resume data');
    return;
  }
  
  // Show initial loading message
  const loadingMsg = document.querySelector('.loading-message');
  if (loadingMsg) {
    loadingMsg.textContent = '🎮 Starting game resume...';
    loadingMsg.style.color = '#007bff';
  }
  
  // Set global state
  window.INTRACHANGE_STATE = {
    game_id: resumeData.game_id,
    white_player_id: resumeData.white_player_id,
    black_player_id: resumeData.black_player_id,
    is_singleplayer: resumeData.white_player_id === resumeData.black_player_id,
    mode: 'resume',
    user_id: resumeData.user ? resumeData.user.id : null,
    my_color: resumeData.my_color || null,
    is_my_turn: false, // will recalc below
    moves: resumeData.moves || []
  };

  // Set currentTurn from backend if provided
  if (resumeData.current_turn) {
    currentTurn = resumeData.current_turn === 'white' ? 'W' : 'B';
  }

  // Calculate is_my_turn
  if (window.INTRACHANGE_STATE.is_singleplayer) {
    // In singleplayer, allow user to move for both sides
    window.INTRACHANGE_STATE.is_my_turn = true;
  } else if (window.INTRACHANGE_STATE.my_color) {
    window.INTRACHANGE_STATE.is_my_turn =
      (window.INTRACHANGE_STATE.my_color === 'White' && currentTurn === 'W') ||
      (window.INTRACHANGE_STATE.my_color === 'Black' && currentTurn === 'B');
  }

  console.log('🌐 Global state set:', window.INTRACHANGE_STATE);

  // Start reconstruction
  reconstructBoardFromMoves(resumeData.game_id)
    .then(result => {
      // Update moves if backend returned them
      if (result && result.moves && result.moves.length) {
        window.INTRACHANGE_STATE.moves = result.moves;
      }
      // Update currentTurn from backend if returned
      if (result && result.game && result.game.current_turn) {
        currentTurn = result.game.current_turn === 'white' ? 'W' : 'B';
        // Recalculate is_my_turn after reconstructing moves
        if (window.INTRACHANGE_STATE.is_singleplayer) {
          window.INTRACHANGE_STATE.is_my_turn = true;
        } else if (window.INTRACHANGE_STATE.my_color) {
          window.INTRACHANGE_STATE.is_my_turn =
            (window.INTRACHANGE_STATE.my_color === 'White' && currentTurn === 'W') ||
            (window.INTRACHANGE_STATE.my_color === 'Black' && currentTurn === 'B');
        }
      }
      // --- Resume Game Diagnostics ---
      console.log('📋 Reconstructed board state:', boardState);
      console.log('� Global State after resume:');
      console.log('  my_color:', window.INTRACHANGE_STATE.my_color);
      console.log('  currentTurn:', currentTurn === 'W' ? 'White' : 'Black');
      console.log('  is_my_turn:', window.INTRACHANGE_STATE.is_my_turn);

      // Continue with existing move enabling / UI code...
      console.log('�🎉 Game resumed successfully:', result);

      // Update loading message
      if (loadingMsg) {
        loadingMsg.innerHTML = `
          <div style="color: #28a745; font-weight: bold;">
            ✅ Game reconstructed successfully!<br>
            📊 ${result.moveCount || 0} moves applied<br>
            🎯 Turn: ${currentTurn === 'W' ? 'White' : 'Black'}
          </div>
        `;
      }

      // Enable interactions if user can move
      if (resumeData.user && resumeData.user.can_move) {
        console.log('🎮 User can make moves - enabling interactions');
        // Always re-attach click handlers after reconstruction
        updateBoardPieces(boardState, handleSquareClick, hexagrams);
        if (typeof handleSquareClick === 'function') {
          console.log('🖱️ Square click handler is available');
        }
      } else {
        console.log('👀 User is spectating - interactions disabled');
        // Optionally, remove click handlers for spectators
        updateBoardPieces(boardState, () => {}, hexagrams);
      }

      // Debug: Print final board state
      // (already printed above)

      // Count pieces on board
      let pieceCount = 0;
      for (let row = 0; row < 8; row++) {
        for (let col = 0; col < 8; col++) {
          if (boardState[row][col]) pieceCount++;
        }
      }
      console.log(`🧮 Total pieces on board: ${pieceCount}`);
    })
    .catch(error => {
      console.error('❌ Resume failed:', error);

      if (loadingMsg) {
        loadingMsg.innerHTML = `
          <div class="error-message" style="color: #dc3545;">
            ❌ Failed to resume game: ${error.message}<br>
            <small>Check console for details</small>
          </div>
        `;
      }
    });
}

// Setters for external state
export function setBoardState(state) {
  boardState = state;
}

export function setCurrentTurn(turn) {
  currentTurn = turn;
}

// Export for testing
export function setGameFinished(finished) {
  gameFinished = finished;
}

export function getGameFinished() {
  return gameFinished;
}

// Check if game has ended
function checkEndOfGame(state) {
  if (gameFinished) return null;
  
  // Find both kings on the board
  let whiteKing = null;
  let blackKing = null;
  
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = state[row][col];
      if (piece === 'WK') whiteKing = { row, col };
      if (piece === 'BK') blackKing = { row, col };
    }
  }
  
  // Check if either king is missing (captured)
  if (!whiteKing) {
    gameFinished = true;
    showEndGameNotification('Black wins!');
    setTimeout(() => {
      showArchiveOptions('BLACK_WINS', 'esoteric_king_capture');
    }, 2000);
    return 'BLACK_WINS';
  }
  
  if (!blackKing) {
    gameFinished = true;
    showEndGameNotification('White wins!');
    setTimeout(() => {
      showArchiveOptions('WHITE_WINS', 'esoteric_king_capture');
    }, 2000);
    return 'WHITE_WINS';
  }
  
  // Check for draw conditions and show gentle suggestions
  checkDrawConditions(state);
  
  return null;
}

// Check for potential draw conditions and show gentle suggestions
function checkDrawConditions(state) {
  if (gameFinished) return;
  
  // Check for insufficient material
  const pieces = [];
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      if (state[row][col]) pieces.push(state[row][col]);
    }
  }
  
  // Only kings remaining
  if (pieces.length === 2 && pieces.includes('WK') && pieces.includes('BK')) {
    showDrawSuggestion('insufficient-material', 'Only kings remain on the board. This could be considered a draw position, but feel free to continue exploring the esoteric moves!');
    return;
  }
  
  // King + minor piece vs King
  if (pieces.length === 3) {
    const nonKings = pieces.filter(p => p !== 'WK' && p !== 'BK');
    if (nonKings.length === 1 && (nonKings[0].includes('B') || nonKings[0].includes('N'))) {
      showDrawSuggestion('insufficient-material', 'King and minor piece vs King - traditionally a draw, but Intrachange offers new possibilities!');
      return;
    }
  }
  
  // Check for no legal moves (stalemate condition)
  const gameState = window.INTRACHANGE_STATE;
  const moveCount = gameState?.moves?.length || 0;
  const currentColor = (moveCount % 2 === 0) ? 'W' : 'B';
  
  let hasLegalMoves = false;
  
  // Check all pieces of current player for any legal moves
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = state[row][col];
      if (piece && piece[0] === currentColor) {
        const legalMoves = getLegalMoves(state, row, col) || [];
        if (legalMoves.length > 0) {
          hasLegalMoves = true;
          break;
        }
      }
    }
    if (hasLegalMoves) break;
  }
  
  // If no legal moves available, suggest draw but don't force it
  if (!hasLegalMoves) {
    const colorName = currentColor === 'W' ? 'White' : 'Black';
    showDrawSuggestion('no-legal-moves', `${colorName} has no legal moves available. In traditional chess, this would be stalemate, but in Intrachange, you might find new paths through contemplation and esoteric exploration!`);
    return;
  }
}

// Show gentle draw suggestion with yes/no response
function showDrawSuggestion(type, message) {
  // Remove any existing suggestion
  const existing = document.getElementById('drawSuggestion');
  if (existing) existing.remove();
  
  const banner = document.createElement('div');
  banner.id = 'drawSuggestion';
  banner.style.cssText = `
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 25px;
    border-radius: 25px;
    font-size: 14px;
    z-index: 500;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    opacity: 0.95;
    max-width: 600px;
    text-align: center;
    animation: slideDown 0.3s ease-out;
  `;
  
  banner.innerHTML = `
    <div style="margin-bottom: 10px;">
      <span style="font-size: 16px;">⚖️</span>
      <span style="margin-left: 8px;">${message}</span>
    </div>
    <div>
      <button id="acceptDrawBtn" style="
        background: rgba(255,255,255,0.9); 
        color: #333; 
        border: none; 
        padding: 8px 16px; 
        margin: 0 8px; 
        border-radius: 15px; 
        cursor: pointer;
        font-weight: bold;
        transition: all 0.2s;
      ">✓ Yes, Declare Draw</button>
      <button id="continuePlayingBtn" style="
        background: rgba(255,255,255,0.2); 
        color: white; 
        border: 2px solid rgba(255,255,255,0.5); 
        padding: 8px 16px; 
        margin: 0 8px; 
        border-radius: 15px; 
        cursor: pointer;
        font-weight: bold;
        transition: all 0.2s;
      ">✗ No, Keep Playing</button>
      <span id="dismissBtn" style="
        margin-left: 15px; 
        cursor: pointer; 
        font-size: 18px; 
        opacity: 0.7;
        transition: opacity 0.2s;
      ">×</span>
    </div>
  `;
  
  // Add CSS animation
  if (!document.getElementById('drawSuggestionStyles')) {
    const style = document.createElement('style');
    style.id = 'drawSuggestionStyles';
    style.textContent = `
      @keyframes slideDown {
        from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 0.95; }
      }
      #drawSuggestion button:hover {
        transform: scale(1.05);
      }
      #dismissBtn:hover {
        opacity: 1;
      }
    `;
    document.head.appendChild(style);
  }
  
  document.body.appendChild(banner);
  
  // Add event listeners for responses
  document.getElementById('acceptDrawBtn').addEventListener('click', () => {
    acceptDraw();
  });
  
  document.getElementById('continuePlayingBtn').addEventListener('click', () => {
    continuePlaying();
  });
  
  document.getElementById('dismissBtn').addEventListener('click', () => {
    dismissDrawSuggestion();
  });
  
  console.log(`💭 Draw suggestion shown: ${type}`);
}

// Handle "Yes, Declare Draw" response
function acceptDraw() {
  gameFinished = true;
  dismissDrawSuggestion();
  showEndGameNotification('Game declared a draw by mutual agreement');
  console.log('🤝 Draw accepted by players');
  
  // Show archive options to players
  setTimeout(() => {
    showArchiveOptions('DRAW', 'mutual_agreement');
  }, 2000);
}

// Handle "No, Keep Playing" response  
function continuePlaying() {
  dismissDrawSuggestion();
  console.log('🎮 Players chose to continue playing');
  
  // Show brief confirmation
  const confirmation = document.createElement('div');
  confirmation.style.cssText = `
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(40, 167, 69, 0.9);
    color: white;
    padding: 10px 20px;
    border-radius: 20px;
    font-size: 14px;
    z-index: 500;
  `;
  confirmation.textContent = '🎭 Continue exploring! The game goes on...';
  document.body.appendChild(confirmation);
  
  setTimeout(() => confirmation.remove(), 3000);
}

// Dismiss the draw suggestion
function dismissDrawSuggestion() {
  const banner = document.getElementById('drawSuggestion');
  if (banner) {
    banner.style.animation = 'slideUp 0.3s ease-in';
    setTimeout(() => banner.remove(), 300);
  }
}

// Show archive options after game ends
function showArchiveOptions(gameResult, endReason) {
  // Remove any existing notification
  const existing = document.getElementById('archiveOptions');
  if (existing) existing.remove();
  
  const modal = document.createElement('div');
  modal.id = 'archiveOptions';
  modal.style.cssText = `
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1500;
  `;
  
  const resultText = gameResult === 'DRAW' ? 'Draw' : 
                    gameResult === 'WHITE_WINS' ? 'White Wins' : 'Black Wins';
  
  modal.innerHTML = `
    <div style="
      background: linear-gradient(135deg, #2c3e50, #34495e);
      color: white;
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      max-width: 500px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    ">
      <h2 style="margin: 0 0 20px 0; color: #ecf0f1;">🏁 Game Complete</h2>
      <p style="font-size: 18px; margin: 0 0 30px 0; color: #bdc3c7;">
        Result: <strong>${resultText}</strong>
      </p>
      <p style="margin: 0 0 30px 0; color: #95a5a6;">
        What would you like to do with this game?
      </p>
      <div style="display: flex; gap: 15px; justify-content: center;">
        <button id="archiveGameBtn" style="
          background: linear-gradient(135deg, #27ae60, #2ecc71);
          color: white;
          border: none;
          padding: 15px 25px;
          border-radius: 10px;
          font-size: 16px;
          font-weight: bold;
          cursor: pointer;
          transition: all 0.3s;
          box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        ">📚 Archive Game</button>
        <button id="deleteGameBtn" style="
          background: linear-gradient(135deg, #e74c3c, #c0392b);
          color: white;
          border: none;
          padding: 15px 25px;
          border-radius: 10px;
          font-size: 16px;
          font-weight: bold;
          cursor: pointer;
          transition: all 0.3s;
          box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        ">🗑️ Delete Game</button>
      </div>
      <p style="font-size: 12px; color: #7f8c8d; margin: 20px 0 0 0;">
        Archived games become permanent records and can be viewed later
      </p>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Add hover effects
  const style = document.createElement('style');
  style.textContent = `
    #archiveGameBtn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4); }
    #deleteGameBtn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4); }
  `;
  document.head.appendChild(style);
  
  // Add event listeners
  document.getElementById('archiveGameBtn').addEventListener('click', () => {
    archiveGame(gameResult, endReason);
  });
  
  document.getElementById('deleteGameBtn').addEventListener('click', () => {
    deleteGame();
  });
}

// Archive the finished game
async function archiveGame(gameResult, endReason) {
  try {
    const gameState = window.INTRACHANGE_STATE;
    
    const payload = {
      game_id: gameState.game_id,
      action: 'archive',
      game_result: gameResult,
      end_reason: endReason,
      board_final_state: boardState,
      test_mode: gameState.test_mode || false
    };
    
    const response = await fetch('backend/archive_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('✅ Game archived successfully:', data.game_summary);
      showArchiveConfirmation('archived', data.game_summary);
    } else {
      console.error('❌ Archive failed:', data.error);
      showArchiveConfirmation('error', 'Failed to archive game: ' + data.error);
    }
    
  } catch (error) {
    console.error('❌ Archive request failed:', error);
    showArchiveConfirmation('error', 'Network error while archiving game');
  }
}

// Delete the finished game
async function deleteGame() {
  try {
    const gameState = window.INTRACHANGE_STATE;
    
    const payload = {
      game_id: gameState.game_id,
      action: 'delete'
    };
    
    const response = await fetch('backend/archive_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('✅ Game deleted successfully');
      showArchiveConfirmation('deleted', 'Game has been permanently deleted');
    } else {
      console.error('❌ Delete failed:', data.error);
      showArchiveConfirmation('error', 'Failed to delete game: ' + data.error);
    }
    
  } catch (error) {
    console.error('❌ Delete request failed:', error);
    showArchiveConfirmation('error', 'Network error while deleting game');
  }
}

// Show confirmation after archive/delete action
function showArchiveConfirmation(type, message) {
  const modal = document.getElementById('archiveOptions');
  if (modal) modal.remove();
  
  const confirmation = document.createElement('div');
  confirmation.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: ${type === 'error' ? 'linear-gradient(135deg, #e74c3c, #c0392b)' : 
                 type === 'archived' ? 'linear-gradient(135deg, #27ae60, #2ecc71)' :
                 'linear-gradient(135deg, #95a5a6, #7f8c8d)'};
    color: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    z-index: 1600;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  `;
  
  const icon = type === 'error' ? '❌' : type === 'archived' ? '📚' : '🗑️';
  confirmation.innerHTML = `
    <div style="font-size: 32px; margin-bottom: 15px;">${icon}</div>
    <div style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">
      ${type === 'error' ? 'Error' : type === 'archived' ? 'Game Archived' : 'Game Deleted'}
    </div>
    <div style="font-size: 14px; opacity: 0.9;">${message}</div>
  `;
  
  document.body.appendChild(confirmation);
  
  setTimeout(() => {
    confirmation.remove();
    // Redirect or refresh page after action
    if (type !== 'error') {
      window.location.href = 'dashboard.php';
    }
  }, 4000);
}

// Display end-of-game notification
function showEndGameNotification(message) {
  // Create or update notification element
  let notification = document.getElementById('endGameNotification');
  if (!notification) {
    notification = document.createElement('div');
    notification.id = 'endGameNotification';
    notification.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 0, 0, 0.9);
      color: white;
      padding: 20px 40px;
      border-radius: 10px;
      font-size: 24px;
      font-weight: bold;
      z-index: 1000;
      text-align: center;
      border: 3px solid #gold;
    `;
    document.body.appendChild(notification);
  }
  
  notification.textContent = message;
  notification.style.display = 'block';
  
  console.log(`🏁 GAME ENDED: ${message}`);
}

// Update turn indicator
function updateTurnIndicator() {
  const indicator = document.getElementById('turnIndicator');
  if (!indicator) return;
  
  if (gameFinished) {
    indicator.textContent = 'Game Finished';
    return;
  }
  
  const gameState = window.INTRACHANGE_STATE;
  const isSingleplayer = gameState?.is_singleplayer || 
                        (gameState?.white_player_id === gameState?.black_player_id);
  
  // Use currentTurn variable instead of move count for accuracy
  const currentColor = (currentTurn === 'W') ? 'White' : 'Black';
  
  if (isSingleplayer) {
    indicator.innerHTML = `<span style="color: #28a745; font-weight: bold;">🧘 ${currentColor}'s Turn</span>`;
  } else {
    // Multiplayer mode - check if it's current user's turn
    const isMyTurn = (
      (gameState.my_color === 'White' && currentTurn === 'W') ||
      (gameState.my_color === 'Black' && currentTurn === 'B')
    );
    
    if (isMyTurn) {
      indicator.innerHTML = `<span style="color: #28a745; font-weight: bold;">🎯 Your Turn (${currentColor})</span>`;
    } else {
      indicator.innerHTML = `<span style="color: #6c757d;">⏳ ${currentColor}'s Turn</span>`;
    }
  }
}

// Hexagram checkbox listener
window.addEventListener('DOMContentLoaded', () => {
  const hexCheckbox = document.getElementById('toggleHexagrams');
  if (hexCheckbox) {
    hexCheckbox.addEventListener('change', () => {
      updateBoardPieces(boardState, handleSquareClick, hexagrams);
    });
  }
});

// Handle square click (main move logic)
export async function handleSquareClick(row, col, state = boardState) {
  // --- Move Click Diagnostics ---
  const square = state[row][col];
  let piece = null;
  if (square && typeof square === 'object' && 'piece' in square) {
    piece = square.piece;
  } else if (typeof square === 'string') {
    piece = square;
  }
  const pieceColor = piece?.startsWith('W') ? 'White' : 'Black';

  console.log('🖱️ Square clicked:', row, col);
  console.log('Piece on square:', piece, 'Color:', pieceColor);
  console.log('CurrentTurn:', currentTurn === 'W' ? 'White' : 'Black');
  console.log('my_color:', window.INTRACHANGE_STATE.my_color);
  console.log('is_my_turn:', window.INTRACHANGE_STATE.is_my_turn);

  if (gameFinished) {
    console.log('🚫 Game has ended, no more moves allowed');
    return;
  }

  const selSquare = selectedSquare;

  // If no piece selected, show legal moves
  if (!selSquare) {
    if (!piece) return; // Empty square

    if (!window.INTRACHANGE_STATE.is_my_turn) {
      console.warn('⚠️ Move blocked: Not your turn');
      return;
    }

    // In singleplayer, allow moving both colors
    if (!window.INTRACHANGE_STATE.is_singleplayer) {
      if (piece && pieceColor !== window.INTRACHANGE_STATE.my_color) {
        console.warn('⚠️ Move blocked: Cannot move opponent piece');
        return;
      }
    }
    // Highlight legal moves
    const legalMoves = getLegalMoves(state, row, col) || [];
    const legalSquares = legalMoves.map(m => [m.row, m.col]);
    highlightMoves(legalSquares, [], piece, positionMap, getEsotericTarget);

    // Select the piece
    selectedSquare = { row, col };
    return;
  }

  // If a piece is already selected, check if destination is legal and show confirmation popup
  const { row: selRow, col: selCol } = selSquare;
  const selectedPiece = state[selRow][selCol];
  const destinationLegalMoves = getLegalMoves(state, selRow, selCol) || [];
  const isLegal = destinationLegalMoves.some(m => m.row === row && m.col === col);

  if (isLegal) {
    showMoveConfirmationPopup(row, col, selectedPiece, state, selSquare);
  } else {
    // Not a legal move, clear selection
    selectedSquare = null;
    clearHighlights();
    updateBoardPieces(state, handleSquareClick, hexagrams);
  }
}

// Add move confirmation popup logic
function showMoveConfirmationPopup(row, col, piece, state, selSquare) {
  console.log('🎯 showMoveConfirmationPopup called:', { row, col, piece });
  
  // Remove any existing popup
  const oldPopup = document.getElementById('move-confirm-popup');
  if (oldPopup) oldPopup.remove();

  const boardElement = document.getElementById('chessboard');
  if (!boardElement) {
    console.error('❌ No chessboard element found!');
    return;
  }

  // Create popup
  const popup = document.createElement('div');
  popup.id = 'move-confirm-popup';
  popup.style.position = 'fixed';
  popup.style.zIndex = '10000';
  popup.style.background = '#f8f9fa';
  popup.style.border = '2px solid #6c757d';
  popup.style.borderRadius = '8px';
  popup.style.padding = '10px 18px';
  popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
  popup.style.whiteSpace = 'nowrap';
  popup.innerHTML = `<div style="font-weight:bold; margin-bottom:8px; color:#333;">Move <span style="color:#007cba;">${piece}</span>?</div>`;

  // Position popup in the header area
  popup.style.minWidth = '180px';
  
  // Try to find the turn-info section to insert the popup into (one row lower)
  const turnInfo = document.querySelector('.turn-info');
  const turnIndicator = document.getElementById('turnIndicator');
  
  if (turnInfo) {
    // Insert into turn-info section using absolute positioning to align right
    popup.style.position = 'absolute';
    popup.style.right = '10px';
    popup.style.top = '50%';
    popup.style.transform = 'translateY(-50%)';
    popup.style.display = 'block';
    
    // Make sure turn-info has relative positioning
    turnInfo.style.position = 'relative';
    
    turnInfo.appendChild(popup);
    
    console.log('📍 Popup inserted into turn-info section (right-aligned)');
  } else if (turnIndicator) {
    // Fallback: position near turn indicator using fixed positioning
    const targetRect = turnIndicator.getBoundingClientRect();
    popup.style.position = 'fixed';
    popup.style.left = `${targetRect.right + 20}px`;
    popup.style.top = `${targetRect.top}px`;
    document.body.appendChild(popup);
    console.log('📍 Popup positioned relative to turn indicator');
  } else {
    // Final fallback: position in top-right
    popup.style.position = 'fixed';
    popup.style.right = '20px';
    popup.style.top = '20px';
    popup.style.left = 'auto';
    document.body.appendChild(popup);
    console.log('📍 Popup positioned as fallback in top-right');
  }
  
  console.log('📍 Popup positioning strategy used:', {
    hasTurnInfo: !!turnInfo,
    hasTurnIndicator: !!turnIndicator,
    position: popup.style.position
  });

  // Create Yes/No buttons ONCE and attach handlers immediately
  let moveConfirmYesBtn = document.createElement('button');
  moveConfirmYesBtn.id = 'move-confirm-yes';
  moveConfirmYesBtn.textContent = 'Yes';
  moveConfirmYesBtn.style.marginRight = '10px';
  moveConfirmYesBtn.style.background = '#6c757d';
  moveConfirmYesBtn.style.color = 'white';
  moveConfirmYesBtn.style.border = 'none';
  moveConfirmYesBtn.style.borderRadius = '5px';
  moveConfirmYesBtn.style.padding = '6px 16px';
  moveConfirmYesBtn.style.cursor = 'pointer';

  let moveConfirmNoBtn = document.createElement('button');
  moveConfirmNoBtn.id = 'move-confirm-no';
  moveConfirmNoBtn.textContent = 'No';
  moveConfirmNoBtn.style.background = '#e9ecef';
  moveConfirmNoBtn.style.color = '#495057';
  moveConfirmNoBtn.style.border = 'none';
  moveConfirmNoBtn.style.borderRadius = '5px';
  moveConfirmNoBtn.style.padding = '6px 16px';
  moveConfirmNoBtn.style.cursor = 'pointer';

  // Attach Yes handler
  moveConfirmYesBtn.onclick = async (e) => {
    e.stopPropagation();
    e.preventDefault();
    popup.remove();
    // ...existing move execution logic...
    // Defensive: ensure selSquare is defined and has row/col
    let selRowPopup, selColPopup;
    if (selSquare && typeof selSquare === 'object' && typeof selSquare.row === 'number' && typeof selSquare.col === 'number') {
      selRowPopup = selSquare.row;
      selColPopup = selSquare.col;
    } else if (selectedSquare && typeof selectedSquare === 'object' && typeof selectedSquare.row === 'number' && typeof selectedSquare.col === 'number') {
      selRowPopup = selectedSquare.row;
      selColPopup = selectedSquare.col;
    } else {
      // Can't proceed, missing selection
      popup.remove();
      selectedSquare = null;
      clearHighlights();
      updateBoardPieces(state, handleSquareClick, hexagrams);
      return;
    }
    const selectedPiecePopup = state[selRowPopup][selColPopup];
    const destinationLegalMovesPopup = getLegalMoves(state, selRowPopup, selColPopup) || [];
    const destinationHexNumPopup = positionMap.find(p => p.row === row && p.col === col)?.hexNum;
    const esotericTargetInfoPopup = esotericMoves && destinationHexNumPopup ? getEsotericTarget(selectedPiecePopup, destinationHexNumPopup) : null;
    let validEsotericTargetPopup = null;
    if (esotericTargetInfoPopup) {
      const targetPiecePopup = state[esotericTargetInfoPopup.row][esotericTargetInfoPopup.col];
      const selectedColorPopup = selectedPiecePopup.charAt(0);
      const targetColorPopup = targetPiecePopup ? targetPiecePopup.charAt(0) : null;
      if (!targetPiecePopup || targetColorPopup !== selectedColorPopup) {
        validEsotericTargetPopup = esotericTargetInfoPopup;
      }
    }
    const isLegalPopup = destinationLegalMovesPopup.some(m => m.row === row && m.col === col) || (validEsotericTargetPopup !== null);
    if (!isLegalPopup) {
      selectedSquare = null;
      clearHighlights();
      updateBoardPieces(state, handleSquareClick, hexagrams);
      return;
    }
    // Execute move
    let finalRowPopup, finalColPopup, hexToPopup;
    if (validEsotericTargetPopup) {
      state[validEsotericTargetPopup.row][validEsotericTargetPopup.col] = selectedPiecePopup;
      state[selRowPopup][selColPopup] = null;
      finalRowPopup = validEsotericTargetPopup.row;
      finalColPopup = validEsotericTargetPopup.col;
      hexToPopup = validEsotericTargetPopup.hexNum;
    } else {
      const targetPiecePopup = state[row][col];
      if (targetPiecePopup && (targetPiecePopup === 'WK' || targetPiecePopup === 'BK')) {
        selectedSquare = null;
        clearHighlights();
        updateBoardPieces(state, handleSquareClick, hexagrams);
        return;
      }
      state[row][col] = selectedPiecePopup;
      state[selRowPopup][selColPopup] = null;
      finalRowPopup = row;
      finalColPopup = col;
      hexToPopup = positionMap.find(p => p.row === row && p.col === col)?.hexNum;
    }
    // Defensive: ensure fromCoordPopup is defined
    let fromCoordPopup = positionMap.find(p => p.row === selRowPopup && p.col === selColPopup)?.coord;
    let toCoordPopup = positionMap.find(p => p.row === finalRowPopup && p.col === finalColPopup)?.coord;
    let hexFromPopup = positionMap.find(p => p.row === selRowPopup && p.col === selColPopup)?.hexNum;
    let keywordFromPopup = getKeywordFromHex(hexFromPopup);
    let keywordToPopup = getKeywordFromHex(hexToPopup);
    clearHighlights();
    const commentTextPopup = document.getElementById('nextMoveComment')?.value || '';
    const commentUrlPopup = document.getElementById('nextMoveUrl')?.value || '';
    let coordsPopup = [fromCoordPopup];
    let keywordsPopup = [keywordFromPopup];
    let hexagramsArrayPopup = [hexFromPopup];
    let middleCoordPopup = null, middleHexNumPopup = null, middleKeywordPopup = null;
    if (validEsotericTargetPopup) {
      middleCoordPopup = positionMap.find(p => p.row === row && p.col === col)?.coord;
      middleHexNumPopup = positionMap.find(p => p.row === row && p.col === col)?.hexNum;
      middleKeywordPopup = getKeywordFromHex(middleHexNumPopup);
      coordsPopup = [fromCoordPopup, middleCoordPopup, validEsotericTargetPopup.coord];
      hexagramsArrayPopup = [hexFromPopup, middleHexNumPopup, validEsotericTargetPopup.hexNum];
      keywordsPopup = [keywordFromPopup, middleKeywordPopup, getKeywordFromHex(validEsotericTargetPopup.hexNum)];
    } else {
      coordsPopup.push(toCoordPopup);
      hexagramsArrayPopup.push(hexToPopup);
      keywordsPopup.push(keywordToPopup);
    }
    selectedSquare = null;
    function prepareMovePayloadPopup(payload) {
      if (payload.esoteric) {
        ['hexagrams', 'keywords'].forEach(key => {
          if (!Array.isArray(payload[key])) payload[key] = [];
          while (payload[key].length < 3) payload[key].push(null);
          if (payload[key].length > 3) payload[key] = payload[key].slice(0, 3);
        });
        if (!Array.isArray(payload.coords)) payload.coords = [];
        while (payload.coords.length < 3) payload.coords.push(null);
        if (payload.coords.length > 3) payload.coords = payload.coords.slice(0, 3);
      }
      return payload;
    }
    let payloadPopup = {
      game_id: window.INTRACHANGE_STATE.game_id,
      chess_coordinates: coordsPopup.join('→'),
      from_square: { row: selRowPopup, col: selColPopup, coord: fromCoordPopup },
      to_square: { row: finalRowPopup, col: finalColPopup, coord: toCoordPopup },
      piece_type: selectedPiecePopup,
      hex_from: hexFromPopup,
      hex_to: hexToPopup,
      keyword_from: keywordFromPopup,
      keyword_to: keywordToPopup,
      user_comment: commentTextPopup,
      comment_url: commentUrlPopup,
      hexagrams: hexagramsArrayPopup,
      keywords: keywordsPopup,
      coords: coordsPopup,
      esoteric: validEsotericTargetPopup
        ? {
            coord: middleCoordPopup,
            hexagram: middleHexNumPopup,
            keyword: middleKeywordPopup
          }
        : null
    };
  payloadPopup = prepareMovePayloadPopup(payloadPopup);
  // Remove buggy hexagramsArray.push(hexToPopup); (hexagramsArray is a local variable, not global)
    try {
      const res = await fetch('backend/moves_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payloadPopup)
      });
      const responseText = await res.text();
      let dataPopup;
      try { dataPopup = JSON.parse(responseText); }
      catch (parseError) {
        const testMoveDataPopup = { ...payloadPopup, id: Date.now(), move_number: 1, timestamp: new Date().toISOString() };
        window.dispatchEvent(new CustomEvent('moveMade', { detail: testMoveDataPopup }));
        return;
      }
      if (dataPopup.success) {
        if (window.INTRACHANGE_STATE?.moves) window.INTRACHANGE_STATE.moves.push(dataPopup.move);
        if (typeof window.populateHistoryTimeline === 'function') window.populateHistoryTimeline();
        if (typeof window.updateMoveHistoryPanel === 'function' && dataPopup.move && dataPopup.move.move_data) {
          const mdPopup = typeof dataPopup.move.move_data === 'string' ? JSON.parse(dataPopup.move.move_data) : dataPopup.move.move_data;
          const coordsStrPopup = Array.isArray(mdPopup.coords) ? mdPopup.coords.filter(Boolean).join(' → ') : '';
          const hexagramsStrPopup = Array.isArray(mdPopup.hexagrams) ? mdPopup.hexagrams.filter(Boolean).join(' → ') : '';
          const keywordsStrPopup = Array.isArray(mdPopup.keywords) ? mdPopup.keywords.filter(Boolean).join(' → ') : '';
          window.updateMoveHistoryPanel({
            ...dataPopup.move,
            coordsStr: coordsStrPopup,
            hexagramsStr: hexagramsStrPopup,
            keywordsStr: keywordsStrPopup
          });
        }
        if (window.showMoveCommentModal && typeof window.showMoveCommentModal === "function") {
          window.showMoveCommentModal(dataPopup.move);
        }
        await refreshGameState();
        window.dispatchEvent(new CustomEvent('moveMade', { detail: dataPopup.move }));
      } else console.error('Move API error:', dataPopup.error);
    } catch (errPopup) {
      const testMoveDataPopup = { ...payloadPopup, id: Date.now(), move_number: 1, timestamp: new Date().toISOString() };
      window.dispatchEvent(new CustomEvent('moveMade', { detail: testMoveDataPopup }));
    }
    selectedSquare = null;
    currentTurn = currentTurn === 'W' ? 'B' : 'W';
    updateTurnIndicator(); // Update turn display immediately
    const gameResultPopup = checkEndOfGame(state);
    if (gameResultPopup) {
      // Game has ended, but still update the board to show final position
    }
    clearHighlights();
    updateBoardPieces(state, handleSquareClick, hexagrams);
  };

  // Attach No handler
  moveConfirmNoBtn.onclick = (e) => {
    e.stopPropagation();
    e.preventDefault();
    popup.remove();
    selectedSquare = null;
    clearHighlights();
    updateBoardPieces(state, handleSquareClick, hexagrams);
  };

  // Append buttons to popup
  popup.appendChild(moveConfirmYesBtn);
  popup.appendChild(moveConfirmNoBtn);
  
  // The popup is already positioned and added to DOM in the positioning logic above
  console.log('✅ Popup created and positioned in header');
}

// Manual test functions for debugging - can be called from browser console
window.testBoardReconstruction = function(gameId = 1) {
  console.log(`🧪 Manual board reconstruction test for game ${gameId}`);
  
  // First, show current state
  debugBoardState();
  
  // Then run reconstruction
  reconstructBoardFromMoves(gameId)
    .then(result => {
      console.log('🎉 Test reconstruction completed:', result);
      debugBoardState();
    })
    .catch(error => {
      console.error('❌ Test reconstruction failed:', error);
    });
};

// Export functions for console access
window.debugBoard = debugBoardState;
window.forceRender = function() {
  console.log('🎨 Force rendering current board state');
  renderBoard(boardState);
};

