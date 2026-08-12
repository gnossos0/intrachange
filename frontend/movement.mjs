// === TESTING STUB: checkCorrespondence ===
// Replace this with your real correspondence logic.
// Accepts piece as a string (e.g. 'WN', 'BN') and hexNum (0–63)
function checkCorrespondence(piece, hexNum) {
  // Example: White pieces exoteric on even hexNums, Black on odd (for demo only)
  if (piece[0] === 'W') {
    return hexNum % 2 === 0; // Exoteric for even hexNums
  } else if (piece[0] === 'B') {
    return hexNum % 2 === 1; // Exoteric for odd hexNums
  }
  // Default fallback — allow testing
  return true;
}

// movement.mjs
import { flatToRowCol } from './utils.mjs';
import { esotericMoves, getEsotericTargetIndex } from './esotericMoves.mjs'; // Keep if used
import { getEsotericTarget } from './esotericMoves.mjs';
import { positionMap } from './positionMap.mjs';
// Import checkCorrespondence from the correct source. If it's globally available or attached to window, use that.
// Remove the broken import. If checkCorrespondence is defined elsewhere, ensure it's available in this scope.

function isInside(row, col) {
  return row >= 0 && row < 8 && col >= 0 && col < 8;
}

// =====================
// Movement functions

function getPawnMoves(board, row, col, color) {
  const moves = [];
  const direction = color === 'W' ? -1 : 1;
  const nextRow = row + direction;

  // Single-step forward move (normal)
  if (isInside(nextRow, col) && !board[nextRow][col]) {
    moves.push({ row: nextRow, col });
  }

  // Two-step move from starting position
  const startRow = color === 'W' ? 6 : 1;
  if (row === startRow) {
    const twoStep = row + 2 * direction;
    if (isInside(twoStep, col) && !board[twoStep][col]) {
      let move2 = { row: twoStep, col };
      if (typeof checkCorrespondence === 'function') {
        const hexNum = twoStep * 8 + col;
        if (!checkCorrespondence(color + 'P', hexNum)) {
          const eso2 = getEsotericTargetIndex(color + 'P', hexNum);
          if (eso2) move2 = { ...move2, ...eso2 };
        }
      }
      moves.push(move2);
    }
  }

  // Captures
  const seventhRank = color === 'W' ? 1 : 6;
  const isSeventhRank = nextRow === seventhRank;
  
  for (let dc of [-1, 1]) {
    const captureCol = col + dc;
    if (isInside(nextRow, captureCol)) {
      const target = board[nextRow][captureCol];
      if (target && target[0] !== color) {
        let move = { row: nextRow, col: captureCol };
        
        if (typeof checkCorrespondence === 'function') {
          const hexNum = nextRow * 8 + captureCol;
          
          // Check if this is an esoteric move
          if (!checkCorrespondence(color + 'P', hexNum)) {
            const eso = getEsotericTargetIndex(color + 'P', hexNum);
            
            // ✅ Pawn exception logic for 7th rank captures
            if (isSeventhRank && eso) {
              const esoTargetPiece = board[eso.row][eso.col];
              if (!esoTargetPiece || esoTargetPiece[0] !== color) {
                // Pawn may capture on Exoteric square before continuing Esoteric move
                move = { ...move, ...eso, captureOnExoteric: true };
              }
            } else if (eso) {
              // Normal esoteric move (not 7th rank)
              move = { ...move, ...eso };
            }
          }
        }
        moves.push(move);
      }
    }
  }

  return moves;
}

function getSlidingMoves(board, row, col, color, directions, type) {
  const moves = [];
  for (const [dr, dc] of directions) {
    let r = row + dr, c = col + dc;
    while (isInside(r, c)) {
      const target = board[r][c];
      let move = { row: r, col: c };
      const hexNum = r * 8 + c;
      if (!target) {
        if (typeof checkCorrespondence === 'function') {
          if (!checkCorrespondence(color + type, hexNum)) {
            const eso = getEsotericTargetIndex(color + type, hexNum);
            if (eso) move = { ...move, ...eso };
          }
        }
        moves.push(move);
      } else {
        if (target[0] !== color) {
          if (typeof checkCorrespondence === 'function') {
            if (!checkCorrespondence(color + type, hexNum)) {
              const eso = getEsotericTargetIndex(color + type, hexNum);
              if (eso) move = { ...move, ...eso };
            }
          }
          moves.push(move);
        }
        break;
      }
      r += dr;
      c += dc;
    }
  }
  return moves;
}

function getRookMoves(board, row, col, color) {
  return getSlidingMoves(board, row, col, color, [[1,0], [-1,0], [0,1], [0,-1]], 'R');
}

function getBishopMoves(board, row, col, color) {
  return getSlidingMoves(board, row, col, color, [[1,1], [1,-1], [-1,1], [-1,-1]], 'B');
}

function getQueenMoves(board, row, col, color) {
  return getSlidingMoves(board, row, col, color, [
    [1,0], [-1,0], [0,1], [0,-1], [1,1], [1,-1], [-1,1], [-1,-1]
  ], 'Q');
}

function getKnightMoves(board, row, col, color, hexNum) {
  // Ensure hexNum is set, even if not passed in
  if (hexNum === undefined && typeof positionMap !== 'undefined') {
    const pos = positionMap.find(p => p.row === row && p.col === col);
    if (pos) hexNum = pos.hexNum;
  }
  console.log(`[movement] getKnightMoves called for ${color}N at (${row},${col}), hexNum=${hexNum}`);
  const deltas = [
    [-2, -1], [-2, 1],
    [-1, -2], [-1, 2],
    [1, -2], [1, 2],
    [2, -1], [2, 1],
  ];

  const piece = `${color}N`;
  const moves = [];

  // Determine if this Knight is in Exoteric or Esoteric mode
  const isCorrespondent = checkCorrespondence(piece, hexNum);
  console.log(`[movement] Knight ${piece} at (${row},${col}) hexNum=${hexNum} isCorrespondent=${isCorrespondent}`);

  // Always generate standard L-shaped moves (Exoteric)
  for (const [dr, dc] of deltas) {
    const r = row + dr, c = col + dc;
    if (isInside(r, c)) {
      const target = board[r][c];
      if (!target || target[0] !== color) {
        moves.push({ row: r, col: c, type: target ? 'capture' : 'legal' });
      }
    }
  }

  // Only add Esoteric move(s) if NOT Exoteric
  if (!isCorrespondent) {
    console.log(`[movement] Knight is in Esoteric mode.`);
    const esoTarget = getEsotericTarget(piece, hexNum);
    if (esoTarget) {
      const { row: targetRow, col: targetCol } = esoTarget;
      console.log(`[movement] Knight Esoteric target: (${targetRow}, ${targetCol})`);

      // Check if any pieces block the path
      const path = getPathBetweenSquares(row, col, targetRow, targetCol);
      const blocked = path.some(([r, c]) => board[r][c] !== null);

      if (blocked) {
        console.log(`[movement] Knight Esoteric move BLOCKED by piece on path.`);
      } else {
        // Only add if not already present (robust duplicate check)
        if (!moves.some(m => m.row === targetRow && m.col === targetCol && m.type === 'esoteric')) {
          moves.push({
            row: targetRow,
            col: targetCol,
            esoteric: true,
            type: 'esoteric'
          });
        }
      }
    } else {
      console.warn(`[movement] No Esoteric target found for ${piece} at hex ${hexNum}`);
    }
  }

  console.log(`[movement] Knight moves generated:`, moves);
  return moves;
}

/**
 * Helper: returns all squares between start and end, exclusive.
 * Adjust as needed depending on your coordinate system.
 */
function getPathBetweenSquares(startRow, startCol, endRow, endCol) {
  const path = [];
  const rowStep = Math.sign(endRow - startRow);
  const colStep = Math.sign(endCol - startCol);

  let r = startRow + rowStep;
  let c = startCol + colStep;

  while (r !== endRow || c !== endCol) {
    path.push([r, c]);
    if (r !== endRow) r += rowStep;
    if (c !== endCol) c += colStep;
  }

  return path;
}

function getKingMoves(board, row, col, color) {
  const deltas = [
    [-1,-1], [-1,0], [-1,1],
    [0,-1],          [0,1],
    [1,-1], [1,0], [1,1],
  ];

  const moves = [];
  for (const [dr, dc] of deltas) {
    const r = row + dr, c = col + dc;
    if (isInside(r, c)) {
      const target = board[r][c];
      let move = { row: r, col: c };
      const hexNum = r * 8 + c;
      if (!target || target[0] !== color) {
        if (typeof checkCorrespondence === 'function') {
          if (!checkCorrespondence(color + 'K', hexNum)) {
            const eso = getEsotericTargetIndex(color + 'K', hexNum);
            if (eso) move = { ...move, ...eso };
          }
        }
        moves.push(move);
      }
    }
  }
  return moves;
}

function getLegalMoves(board, row, col) {
  const piece = board[row][col];
  if (!piece) return [];

  const color = piece[0];
  const type = piece[1];

  switch (type) {
    case 'P': return getPawnMoves(board, row, col, color);
    case 'R': return getRookMoves(board, row, col, color);
    case 'N': return getKnightMoves(board, row, col, color);
    case 'B': return getBishopMoves(board, row, col, color);
    case 'Q': return getQueenMoves(board, row, col, color);
    case 'K': return getKingMoves(board, row, col, color);
    default: return [];
  }
}

export {
  getPawnMoves,
  getRookMoves,
  getKnightMoves,
  getBishopMoves,
  getQueenMoves,
  getKingMoves,
  getLegalMoves,
};
