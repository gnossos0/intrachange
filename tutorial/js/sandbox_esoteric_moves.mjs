// sandbox_esoteric_moves.mjs
// Correlation 4::4 Sandbox (Clean Bounce-Based Logic)

import { bounce } from '../correlations/correlation_4_4_esoteric.js';

let draggedPiece = null;
let mouseX = 0;
let mouseY = 0;
let currentCandidates = [];

// Track mouse
document.addEventListener('mousemove', e => {
    mouseX = e.clientX;
    mouseY = e.clientY;
});

// --- Utilities ---

function getBounceTarget(squareIndex, pieceCode) {
    console.log('Looking for bounce match:',
  'Square:', squareIndex,
  'Piece:', pieceCode
);
    const entry = bounce.find(b => b.square === squareIndex);
    if (!entry) return undefined;

    const drop = entry.validDrops.find(d => d.piece === pieceCode);
    return drop ? drop.esotericTarget : undefined;
}

// Return ALL candidate squares for this piece
function getCandidateSquares(pieceCode) {
    return bounce
        .filter(entry =>
            entry.validDrops.some(d => d.piece === pieceCode)
        )
        .map(entry => entry.square);
}

function showCandidateOverlay(squareIndex) {
    const square = document.querySelector(`[data-index="${squareIndex}"]`);
    if (!square) return;
    if (square.querySelector('.esoteric-overlay')) return;

    const overlay = document.createElement('div');
    overlay.classList.add('esoteric-overlay');
    overlay.style.position = 'absolute';
    overlay.style.pointerEvents = 'none';
    overlay.style.top = '50%';
    overlay.style.left = '50%';
    overlay.style.transform = 'translate(-50%, -50%)';
    overlay.style.width = '38px';
    overlay.style.height = '38px';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = 5;

    const img = document.createElement('img');
    img.src = '../images/yinyangspin.gif';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';

    overlay.appendChild(img);
    square.style.position = 'relative';
    square.appendChild(overlay);
}

function clearCandidateOverlays() {
    document.querySelectorAll('.esoteric-overlay').forEach(o => o.remove());
    currentCandidates = [];
}

// --- Drag Logic ---

function onMouseDownPiece(e) {
    draggedPiece = e.currentTarget;
    draggedPiece.classList.add('dragging');

    const messageEl = document.getElementById("pieceContextMessage");
    if (!messageEl) return;

    const fullPiece = draggedPiece.dataset.piece;
    if (!fullPiece) return;

    const [pieceColor, pieceTypeRaw] = fullPiece.split("-");
    const pieceType =
        pieceTypeRaw.charAt(0).toUpperCase() + pieceTypeRaw.slice(1);

    let sentence = "";

    if (pieceColor === "white") {
        sentence = `A White ${pieceType} does not correspond to a Broken Line, creating an Esoteric Move.`;
    } else {
        sentence = `A Black ${pieceType} does not correspond to the Solid Line, creating an Esoteric Move.`;
    }

    messageEl.textContent = sentence;
}

function onMouseUpPiece() {
    if (!draggedPiece) return;

    draggedPiece.classList.remove('dragging');

    const pieceCode = draggedPiece.dataset.piece;
    const squares = Array.from(document.querySelectorAll('#reviewChessboard td'));

    const snappedSquare = findClosestSquare(squares, mouseX, mouseY);

    if (snappedSquare) {
        const squareIndex = parseInt(snappedSquare.dataset.index, 10);

        const isCandidate = currentCandidates.includes(squareIndex);

        if (isCandidate) {
            handleEsotericMove(draggedPiece, snappedSquare);
        } else {
            snappedSquare.appendChild(draggedPiece);
        }
    }

    clearCandidateOverlays();
    draggedPiece = null;
}

// --- Magnet Snap ---

function findClosestSquare(squares, x, y) {
    let closest = null;
    let minDist = Infinity;

    squares.forEach(sq => {
        const rect = sq.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const dist = Math.hypot(cx - x, cy - y);

        if (dist < minDist) {
            minDist = dist;
            closest = sq;
        }
    });

    return minDist < 80 ? closest : null;
}

// --- Esoteric Move ---

function handleEsotericMove(piece, candidateSquare) {
    console.log('Dropped on candidate:',
  parseInt(candidateSquare.dataset.index, 10),
  'Piece:',
  piece.dataset.piece
);
    const candidateIndex = parseInt(candidateSquare.dataset.index, 10);
    const pieceCode = piece.dataset.piece;

    candidateSquare.appendChild(piece);

    const targetIndex = getBounceTarget(candidateIndex, pieceCode);
    if (targetIndex === undefined) return;

    const targetSquare = document.querySelector(`[data-index="${targetIndex}"]`);
    if (!targetSquare) return;

    targetSquare.classList.add('valid-target');

    updateHexagramPanel(candidateIndex, targetIndex, pieceCode);

    setTimeout(() => {
        targetSquare.appendChild(piece);
        targetSquare.classList.remove('valid-target');
    }, 2000);
}

// --- Hexagram Panel Placeholder ---

function updateHexagramPanel(candidateIndex, targetIndex, pieceCode) {
    console.log('Hexagram panel update:', candidateIndex, targetIndex, pieceCode);
}

// --- Initialize Sandbox ---

export function initializeSandbox() {
    const pieces = document.querySelectorAll('.sandbox-piece');

    pieces.forEach(p => {
        p.style.cursor = 'grab';
        p.addEventListener('mousedown', onMouseDownPiece);
    });

    document.addEventListener('mouseup', onMouseUpPiece);
}

// --- Side Pieces ---

export function initializeSidePieces() {
    const sidePieces = document.querySelectorAll('.side-piece');

    sidePieces.forEach(piece => {
        piece.style.cursor = 'grab';
        piece.addEventListener('mousedown', onMouseDownPiece);
    });
}

// --- CSS Injection ---

const style = document.createElement('style');
style.textContent = `
.esoteric-overlay img {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.valid-target {
    box-shadow: 0 0 18px 6px rgba(255, 140, 0, 0.95);
    outline: 3px solid orange;
    transition: all 0.15s ease;
}

.dragging {
    opacity: 0.85;
    cursor: grabbing;
}
`;
document.head.appendChild(style);

window.addEventListener('DOMContentLoaded', () => initializeSandbox());