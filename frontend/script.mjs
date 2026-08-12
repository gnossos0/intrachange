import { handleSquareClick, initializeBoard, reconstructBoardFromMoves, debugBoardState } from './boardController.mjs';
import { renderBoard } from './ui.mjs';
import { hexagrams } from './hexagrams.mjs';

let boardState;

function onSquareClick(row, col) {
  handleSquareClick(row, col, boardState);
}

document.addEventListener('DOMContentLoaded', () => {
  console.log('🎮 Script.mjs: Initializing game...');
  
  try {
    // Initialize board state
    boardState = initializeBoard();
    console.log('✅ Board state initialized:', boardState);
    
    // Replay moves from database to get current position
    if (window.INTRACHANGE_STATE && window.INTRACHANGE_STATE.moves) {
      console.log('� Replaying', window.INTRACHANGE_STATE.moves.length, 'moves from database...');
      replayMovesBasic(window.INTRACHANGE_STATE.moves);
      console.log('✅ Moves replayed, current board state:', boardState);
    }
    
    // Render the board with pieces
    renderBoard(boardState, onSquareClick, hexagrams);
    console.log('✅ Board rendered with pieces');
    
  } catch (error) {
    console.error('❌ Error in script.mjs initialization:', error);
  }
});

// Fallback to basic initialization for new games
function fallbackInitialization() {
  console.log('🏁 Fallback: Basic board initialization');
  
  // Initialize board state
  boardState = initializeBoard();
  console.log('✅ Board state initialized:', boardState);
  
  // Simple replay for any basic moves (keeping old logic as backup)
  if (window.INTRACHANGE_STATE && window.INTRACHANGE_STATE.moves) {
    console.log('🔄 Replaying', window.INTRACHANGE_STATE.moves.length, 'moves with basic replay...');
    replayMovesBasic(window.INTRACHANGE_STATE.moves);
    console.log('✅ Basic moves replayed, current board state:', boardState);
  }
  
  // Render the board with pieces
  renderBoard(boardState, onSquareClick, hexagrams);
  console.log('✅ Board rendered with pieces');
}

// Basic replay moves from database (fallback for simple cases)
function replayMovesBasic(moves) {
  moves.forEach((move, index) => {
    try {
      if (move.from_square && move.to_square) {
        const from = move.from_square;
        const to = move.to_square;
        
        // Move the piece (simple version - doesn't handle esoteric moves properly)
        const piece = boardState[from.row][from.col];
        if (piece) {
          boardState[to.row][to.col] = piece;
          boardState[from.row][from.col] = null;
          console.log(`🔄 Basic move ${index + 1}: ${piece} from (${from.row},${from.col}) to (${to.row},${to.col})`);
        } else {
          console.warn(`⚠️ No piece found at source for move ${index + 1}`);
        }
      }
    } catch (error) {
      console.error(`❌ Error replaying move ${index + 1}:`, error);
    }
  });
}

// --- Utility: Ensure esoteric moves always send a complete esoteric object ---
function prepareMovePayload(payload) {
  // Only modify if esoteric move
  if (payload.esoteric) {
    // Ensure coords, hexagrams, keywords are arrays of length 3
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

// Attach to window for global access
window.prepareMovePayload = prepareMovePayload;

// Expose boardState globally for debugging
window.getBoardState = () => boardState;
window.debugChessboard = () => {
  console.log('🐛 Debug Info:');
  console.log('Board State:', boardState);
  console.log('Hexagrams loaded:', Object.keys(hexagrams).length > 0);
  console.log('Chessboard element:', document.getElementById('chessboard'));
};
