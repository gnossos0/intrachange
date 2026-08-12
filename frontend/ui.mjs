// ui.mjs
import { hexagrams } from './hexagrams.mjs';

// Unicode chess piece symbols by code
const pieceSymbols = {
  WP: '♙', WR: '♖', WN: '♘', WB: '♗', WQ: '♕', WK: '♔',
  BP: '♟', BR: '♜', BN: '♞', BB: '♝', BQ: '♛', BK: '♚',
};

const files = 'abcdefgh';
const ranks = '87654321';

// Render the chessboard pieces, hexagram info, and set up click handlers
export function renderBoard(boardState, onSquareClick, hexagramsMap) {
  const boardElement = document.getElementById('chessboard');
  if (!boardElement) {
    console.error('❌ Chessboard element not found');
    return;
  }

  const rows = boardElement.querySelectorAll('tr');
  if (rows.length !== 8) return console.error('Chessboard must have 8 rows');

  for (let row = 0; row < 8; row++) {
    const cells = rows[row].querySelectorAll('td');
    if (cells.length !== 8) return console.error(`Row ${row} must have 8 cells`);

    for (let col = 0; col < 8; col++) {
      const square = files[col] + ranks[row];
      const td = cells[col];

      // Clear previous content & classes
      td.innerHTML = '';
      td.onclick = null;
      td.classList.remove('highlight-legal', 'highlight-capture', 'highlight');

      // Coordinate label (top-left)
      const coordLabel = document.createElement('div');
      coordLabel.className = 'coord-label';
      coordLabel.textContent = square;
      td.appendChild(coordLabel);

      if (hexagramsMap && hexagramsMap[square]) {
        const hexNum = hexagramsMap[square].hexNum;
        const keyword = hexagramsMap[square].keyword;
        td.dataset.hexNum = hexNum;

        // Hexagram number (top-right)
        const hexNumDiv = document.createElement('div');
        hexNumDiv.className = 'hexNum';
        hexNumDiv.textContent = hexNum;
        td.appendChild(hexNumDiv);

        // Keyword (centered under coord label)
        const keywordDiv = document.createElement('div');
        keywordDiv.className = 'hex-keyword';
        keywordDiv.textContent = keyword;
        td.appendChild(keywordDiv);

        // Render hexagram image
        const hexCheckbox = document.getElementById('toggleHexagrams');
        if (hexCheckbox && hexCheckbox.checked) {
          const hexImg = document.createElement('img');
          hexImg.className = 'hex-img';
          hexImg.src = `img/hex/hexagram${hexNum}.svg`;
          hexImg.style.height = '40px';
          hexImg.style.width = 'auto';
          td.appendChild(hexImg);
        }
      } else {
        td.removeAttribute('data-hex-num');
      }

      // Piece rendering
      const pieceCode = boardState[row][col];
      if (pieceCode) {
        // Create piece container with proper positioning
        const pieceSlot = document.createElement('div');
        pieceSlot.className = 'piece-slot';
        
        const pieceSpan = document.createElement('span');
        pieceSpan.classList.add('piece');
        pieceSpan.textContent = pieceSymbols[pieceCode] || '?';
        pieceSpan.classList.add(pieceCode.startsWith('W') ? 'white-piece' : 'black-piece');
        
        pieceSlot.appendChild(pieceSpan);
        td.appendChild(pieceSlot);
      }

      // Click handler
      td.onclick = () => onSquareClick(row, col);

      // Remove previous hover handlers
      td.onmouseover = null;
      td.onmouseout = null;

      // Debug overlays for move_data have been removed for production UI.
  // End of main for loop
  // --- DEBUG: Warn if any move_data coord is not on the board ---
  if (window.INTRACHANGE_STATE && Array.isArray(window.INTRACHANGE_STATE.moves) && window.INTRACHANGE_STATE.moves.length > 0) {
    const lastMove = window.INTRACHANGE_STATE.moves[window.INTRACHANGE_STATE.moves.length - 1];
    if (lastMove && lastMove.move_data && Array.isArray(lastMove.move_data.coords)) {
      const allSquares = new Set();
      for (let row = 0; row < 8; row++) {
        for (let col = 0; col < 8; col++) {
          allSquares.add(files[col] + ranks[row]);
        }
      }
      lastMove.move_data.coords.forEach((coord, idx) => {
        if (!coord) return; // Suppress warning for empty coords
        if (!allSquares.has(coord)) {
          // Show a warning somewhere obvious (e.g., top of board)
          const boardElement = document.getElementById('chessboard');
          if (boardElement && !document.getElementById('move-data-warning')) {
            const warnDiv = document.createElement('div');
            warnDiv.id = 'move-data-warning';
            warnDiv.style.position = 'absolute';
            warnDiv.style.top = '0';
            warnDiv.style.left = '0';
            warnDiv.style.right = '0';
            warnDiv.style.background = 'rgba(255,0,0,0.15)';
            warnDiv.style.color = 'darkred';
            warnDiv.style.fontWeight = 'bold';
            warnDiv.style.fontSize = '14px';
            warnDiv.style.zIndex = '1000';
            warnDiv.style.textAlign = 'center';
            warnDiv.textContent = `Warning: move_data[${idx}] coord "${coord}" is not a visible square!`;
            boardElement.parentElement.insertBefore(warnDiv, boardElement);
          }
        }
      });
    }
  }
    }
  }
}

export function updateBoardPieces(boardState, onSquareClick, hexagramsMap) {
  renderBoard(boardState, onSquareClick, hexagramsMap);
}

// Highlight a single square (generic)
export function highlightSquare(row, col) {
  const boardElement = document.getElementById('chessboard');
  if (!boardElement) return;
  const td = boardElement.querySelectorAll('tr')[row]?.querySelectorAll('td')[col];
  if (td) td.classList.add('highlight');
}

export function clearHighlights() {
  const boardElement = document.getElementById('chessboard');
  if (!boardElement) return;
  boardElement.querySelectorAll('.highlight-legal, .highlight-capture, .highlight, .highlight-esoteric')
    .forEach(sq => sq.classList.remove('highlight-legal', 'highlight-capture', 'highlight', 'highlight-esoteric'));
}

// Highlight legal & capture moves using row/col pairs
export function highlightMoves(legalMoves, captureMoves, selectedPiece, positionMap, getEsotericTarget) {
  const boardElement = document.getElementById('chessboard');
  if (!boardElement) return;

  console.log('[ui] highlightMoves called:', { legalMoves, captureMoves, selectedPiece });

  // For each legal move, attach a hover handler that dynamically looks up its esoteric target
  if (Array.isArray(legalMoves)) {
    legalMoves.forEach(([row, col]) => {
      const td = boardElement.querySelectorAll('tr')[row]?.querySelectorAll('td')[col];
      if (td) {
        td.classList.add('highlight-legal');
        console.log(`[ui] highlight-legal applied to: row=${row}, col=${col}`);
        
        // Dynamically look up esoteric target for THIS specific move
        td.onmouseover = () => {
          if (selectedPiece && positionMap && getEsotericTarget) {
            const destHexNum = positionMap.find(p => p.row === row && p.col === col)?.hexNum;
            const esotericTarget = destHexNum != null ? getEsotericTarget(selectedPiece, destHexNum) : null;
            
            if (esotericTarget) {
              const eTd = boardElement.querySelectorAll('tr')[esotericTarget.row]?.querySelectorAll('td')[esotericTarget.col];
              if (eTd) {
                eTd.classList.add('highlight-esoteric');
                console.log('[ui] highlight-esoteric applied:', { 
                  eRow: esotericTarget.row, 
                  eCol: esotericTarget.col, 
                  fromHex: destHexNum,
                  piece: selectedPiece 
                });
              }
            }
          }
        };
        
        td.onmouseout = () => {
          if (selectedPiece && positionMap && getEsotericTarget) {
            const destHexNum = positionMap.find(p => p.row === row && p.col === col)?.hexNum;
            const esotericTarget = destHexNum != null ? getEsotericTarget(selectedPiece, destHexNum) : null;
            
            if (esotericTarget) {
              const eTd = boardElement.querySelectorAll('tr')[esotericTarget.row]?.querySelectorAll('td')[esotericTarget.col];
              if (eTd) {
                eTd.classList.remove('highlight-esoteric');
                console.log('[ui] highlight-esoteric removed:', { 
                  eRow: esotericTarget.row, 
                  eCol: esotericTarget.col, 
                  fromHex: destHexNum,
                  piece: selectedPiece 
                });
              }
            }
          }
        };
      } else {
        console.warn(`[ui] highlight-legal: No td found for row=${row}, col=${col}`);
      }
    });
  }

  if (Array.isArray(captureMoves)) {
    captureMoves.forEach(([row, col]) => {
      const td = boardElement.querySelectorAll('tr')[row]?.querySelectorAll('td')[col];
      if (td) td.classList.add('highlight-capture');
    });
  }
}
