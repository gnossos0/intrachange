// render.mjs
// NOTE: For board rendering and highlighting, use ui.mjs instead. This module does NOT support esoteric move highlighting.
import { positionMap } from './positionMap.mjs';

// Build the board once with event listeners and hex info
export function buildBoard(handleSquareClick, hexagrams) {
  const boardEl = document.getElementById("chessboard");
  if (!boardEl) {
    console.error("❌ No board element found in DOM");
    return;
  }

  boardEl.innerHTML = "";

  const hexCheckbox = document.getElementById('toggleHexagrams');
  const showHexagrams = hexCheckbox && hexCheckbox.checked;
  for (let r = 0; r < 8; r++) {
    const rowEl = document.createElement("tr");
    for (let c = 0; c < 8; c++) {
      const cell = document.createElement("td");
      const colorClass = (r + c) % 2 === 0 ? "light" : "dark";
      cell.className = `square ${colorClass}`;
      cell.dataset.row = r;
      cell.dataset.col = c;
      // Get positionMap info
      const pos = positionMap.find(p => p.row === r && p.col === c);
      const chessCoord = pos ? pos.coord : `${String.fromCharCode(97 + c)}${8 - r}`;
      const hexNum = pos ? pos.hexNum : null;
      cell.dataset.coord = chessCoord;
      cell.dataset.hexNum = hexNum;
      cell.onclick = () => handleSquareClick(r, c);
      const hexData = hexagrams[hexNum] || {};
      const hexKeyword = hexData.keyword || "";
      const hexNumber = (hexData.hexNum !== undefined && hexData.hexNum !== null) ? hexData.hexNum : hexNum;
  // Force a visible test image in every cell
  let hexImgHtml = `<img src="https://via.placeholder.com/50/ff0000/ffffff?text=Test" class="hex-img" style="background:red;" />`;
      cell.innerHTML = `
        <div class="square-coord">${chessCoord}</div>
        <div class="hex-info">
          <span class="hex-num">#${hexNumber}</span>
          <span class="hex-keyword">${hexKeyword}</span>
        </div>
        ${hexImgHtml}
        <div class="piece-slot"></div>
      `;
      if (showHexagrams && hexNum !== null && hexNum !== undefined) {
        const img = cell.querySelector('.hex-img');
        if (img) {
          img.style.background = 'red';
          img.style.opacity = '1';
        }
      }
      console.log('[render] cell HTML:', cell.innerHTML);
      rowEl.appendChild(cell);
    }
    boardEl.appendChild(rowEl);
  }
}

// Update pieces on the board without rebuilding
export function updateBoardPieces(state) {
  const hexCheckbox = document.getElementById('toggleHexagrams');
  const showHexagrams = hexCheckbox && hexCheckbox.checked;
  for (let r = 0; r < state.length; r++) {
    for (let c = 0; c < state[r].length; c++) {
      const piece = state[r][c];
      const cell = document.querySelector(`td[data-row='${r}'][data-col='${c}']`);
      if (!cell) continue;
      // Get positionMap info
      const pos = positionMap.find(p => p.row === r && p.col === c);
      const hexNum = pos ? pos.hexNum : null;
      let hexImgHtml = "";
      if (showHexagrams && hexNum !== null && hexNum !== undefined) {
        hexImgHtml = `<img src="../img/hex/hexagram${hexNum}.svg" class="hex-img" />`;
      }
      cell.style.position = "relative";
      cell.innerHTML = `
        ${hexImgHtml}
        <div class="piece-slot">${piece ? `<span class="piece ${piece[0] === "W" ? "white-piece" : "black-piece"}">${piece}</span>` : ""}</div>
      `;
    }
  }
}
// Clear all highlights
export function clearHighlights() {
  document.querySelectorAll(".highlight-legal, .highlight-capture").forEach(el => {
    el.classList.remove("highlight-legal", "highlight-capture");
  });
}
// Add highlights to legal and capture moves
export function highlightMoves(moves, captureMoves) {
  clearHighlights();

  moves.forEach(move => {
    const selector = `[data-row='${move.row}'][data-col='${move.col}']`;
    const cell = document.querySelector(selector);
    if (cell) cell.classList.add("highlight-legal");
  });

  captureMoves.forEach(move => {
    const selector = `[data-row='${move.row}'][data-col='${move.col}']`;
    const cell = document.querySelector(selector);
    if (cell) cell.classList.add("highlight-capture");
  });
}
// Update pieces and highlights after a move
export function updateBoard(state, legalMoves = [], captureMoves = []) {
  updateBoardPieces(state);
  highlightMoves(legalMoves, captureMoves);
}
