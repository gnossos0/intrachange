console.log("🔥 hexagramEngine.mjs loaded");

import { hexagrams } from './hexagrams.mjs';
import { positionMap } from './positionMap.mjs';

const boardDiv = document.getElementById('chessboard');
let showKeywords = false;
let showImage = false;

// Create a container for toggles
const toggleContainer = document.createElement('div');
toggleContainer.style.marginBottom = '16px';
boardDiv.before(toggleContainer);

// Add toggle button for keywords
const toggleBtn = document.createElement('button');
toggleBtn.textContent = 'Show Keywords';
toggleBtn.style.marginRight = '8px';
toggleContainer.appendChild(toggleBtn);

// Use checkbox for hexagram image toggle
const hexCheckbox = document.getElementById('toggleHexagrams');
let showHexagrams = false;
if (hexCheckbox) {
  hexCheckbox.addEventListener('change', () => {
    showHexagrams = hexCheckbox.checked;
    updateBoard();
    if (showHexagrams) {
      // Listen for any click to hide hexagrams
      document.addEventListener('mousedown', hideHexagramsOnce, { once: true });
    }
  });
}

function hideHexagramsOnce() {
  showHexagrams = false;
  if (hexCheckbox) hexCheckbox.checked = false;
  updateBoard();
}

// Function to update board display
function updateBoard() {
  const squares = document.querySelectorAll('.square');
  squares.forEach(square => {
    const coord = square.getAttribute('data-coord');
    const pos = positionMap.find(p => p.coord === coord);
    if (!pos) return;
    const hexData = hexagrams[pos.hexNum];
    if (!hexData) return;
    const hexNum = hexData.hexNum;
    // Show hexagram image if checkbox is checked
    if (showHexagrams) {
      // Add hexagram image to square
      const paddedHexNum = hexNum.toString().padStart(2, '0');
      // Show image alongside piece
      let pieceHtml = square.querySelector('.piece-slot')?.innerHTML || '';
      square.innerHTML = `
        <div class="hexagram-img" style="text-align:center;">
      <img src="/img/hex/${paddedHexNum}.svg" style="max-width:50px; max-height:50px; height:50px; display:block; margin:auto;" />
        </div>
        <div class="piece-slot">${pieceHtml}</div>
      `;
    } else {
      // Only show piece
      let pieceHtml = square.querySelector('.piece-slot')?.innerHTML || '';
      square.innerHTML = `<div class="piece-slot">${pieceHtml}</div>`;
    }
  });
}

// Optional: hook into move system to trigger updateBoard when a new square is selected
export function onHexagramSelected(coord) {
  const pos = positionMap.find(p => p.coord === coord);
  if (!pos) return;

  const hexData = hexagrams[pos.hexNum];
  if (!hexData) return;

  const hexNum = hexData.hexNum;

  // Update image if needed
  if (showImage) {
    const paddedHexNum = hexNum.toString().padStart(2, '0');
    hexImage.src = `/img/hex/${paddedHexNum}.svg`;
  }

  updateBoard(); // re-render UI
}

// Initialize display once
updateBoard();
