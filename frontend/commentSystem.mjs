// commentSystemMerged.mjs - Full-featured Intrachange move commentary system

let currentGameId = null;
let lastMoveDetails = null;
let moveHistory = [];

// Initialize when page loads
window.addEventListener('DOMContentLoaded', () => {
    if (window.INTRACHANGE_STATE?.game_id) {
        currentGameId = window.INTRACHANGE_STATE.game_id;
        initializeCommentSystem();
    }
});

// Listen for move events from the board
window.addEventListener('moveMade', (event) => {
    handleMoveMade(event.detail);
    // Update hexagrams sidebar for all games (singleplayer and multiplayer)
    updateHexagramsSidebar(event.detail);
    // Send move data to Reference Portal popup window
    updateReferencePortal(event.detail);
});

function initializeCommentSystem() {
    setupEventListeners();
    loadGameHistory();

    // Expose globally for boardController integration
    window.commentSystem = {
        onMoveMade: handleMoveMade,
        loadGameHistory
    };
}

function setupEventListeners() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('commentSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '→' : '←';
        });
    }

    const commentForm = document.getElementById('commentForm');
    if (commentForm) commentForm.addEventListener('submit', saveComment);

    // Auto-save with Ctrl/Cmd + Enter
    const commentText = document.getElementById('commentText');
    if (commentText) {
        commentText.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                saveComment(e);
            }
        });
    }
    // Reference toggle functionality
    const referenceToggle = document.getElementById('referenceToggle');
    const referenceSection = document.getElementById('referenceSection');
    
    if (referenceToggle && referenceSection) {
        referenceToggle.addEventListener('click', () => {
            const isHidden = referenceSection.classList.contains('hidden');
            referenceSection.classList.toggle('hidden');
            referenceToggle.textContent = isHidden ? 'Hide Reference' : 'Reference';
        });
    }}

// Called when a move is made on the board
function handleMoveMade(moveDetails) {
    // Only show opponent move notification if not singleplayer
    const gameState = window.INTRACHANGE_STATE;
    const isSingleplayer = gameState?.is_singleplayer || (gameState?.white_player_id === gameState?.black_player_id);
    if (isSingleplayer) return;

    lastMoveDetails = moveDetails;

    // Create and display move readout card
    createMoveReadoutCard(moveDetails);

    // Prefill comment form
    prefillCommentForm(moveDetails);
    
    // Load reference content for the move
    loadReferenceContent(moveDetails);
}

// Update hexagrams sidebar section
function updateHexagramsSidebar(moveDetails) {
    const hexagramList = document.getElementById('hexagram-list');
    const hexagramsSection = document.getElementById('hexagrams-section');
    
    if (!hexagramList || !moveDetails) return;
    
    // Extract hexagrams and keywords from move_data
    let hexagrams = [];
    let keywords = [];
    
    if (moveDetails.move_data) {
        let moveData = moveDetails.move_data;
        if (typeof moveData === 'string') {
            try {
                moveData = JSON.parse(moveData);
            } catch (e) {
                console.warn('Could not parse move_data:', e);
                moveData = null;
            }
        }
        
        if (moveData && Array.isArray(moveData.hexagrams) && Array.isArray(moveData.keywords)) {
            hexagrams = moveData.hexagrams.filter(Boolean);
            keywords = moveData.keywords.filter(Boolean);
        }
    }
    
    // Fallback to legacy fields if move_data not available
    if (hexagrams.length === 0) {
        if (moveDetails.hex_from) hexagrams.push(moveDetails.hex_from);
        if (moveDetails.hex_to) hexagrams.push(moveDetails.hex_to);
        if (moveDetails.keyword_from) keywords.push(moveDetails.keyword_from);
        if (moveDetails.keyword_to) keywords.push(moveDetails.keyword_to);
    }
    
    // Generate hexagram items
    if (hexagrams.length > 0) {
        const items = hexagrams.map((hex, index) => {
            const keyword = keywords[index] || 'Unknown';
            return `
                <p class="hexagram-item">
                    <span class="hexagram-number">${hex}</span>
                    <span class="hexagram-keyword">${keyword}</span>
                </p>
            `;
        }).join('');
        
        hexagramList.innerHTML = items;
    } else {
        hexagramList.innerHTML = '<p style="color: #888; font-style: italic;">No hexagram data available</p>';
    }
    
    // Ensure the details section is open
    if (hexagramsSection) {
        hexagramsSection.open = true;
    }
}

// Send move data to Reference Portal popup window
function updateReferencePortal(moveDetails) {
    // Check if reference portal window exists and is open
    if (window.portalWindow && !window.portalWindow.closed) {
        try {
            // Send move data via postMessage
            window.portalWindow.postMessage({
                type: 'moveData',
                moveDetails: moveDetails
            }, window.location.origin);
            
            console.log('📨 Sent move data to Reference Portal:', moveDetails.chess_coordinates);
        } catch (error) {
            console.warn('Failed to send message to Reference Portal:', error);
        }
    }
}

// Create a formatted move readout card and add to move history
function createMoveReadoutCard(moveDetails) {

    // No-op: test page move readout removed to prevent duplicate/legacy output
}

// Extract piece type from move data
function extractPieceFromMove(payload) {
    if (payload.piece_type) return payload.piece_type;
    const piece = determinePieceFromContext(payload);
    return piece;
}

// Determine piece type from board context
function determinePieceFromContext(payload) {
    if (window.getBoardState) {
        try {
            const boardState = window.getBoardState();
            const from = payload.from_square;
            if (from && boardState[from.row] && boardState[from.row][from.col]) {
                return boardState[from.row][from.col];
            }
        } catch {}
    }
    return 'Piece';
}

// Convert square object to chess coordinate string
function squareToCoord(square) {
    if (!square || typeof square.row !== 'number' || typeof square.col !== 'number') return null;
    const files = 'abcdefgh';
    const ranks = '87654321';
    return files[square.col] + ranks[square.row];
}

// Prefill comment form for current move
function prefillCommentForm(move) {
    ['moveId', 'hexFrom', 'hexTo', 'keywordFrom', 'keywordTo'].forEach(id => {
        const field = document.getElementById(id);
        if (field) field.value = move[id] || '';
    });

    const commentText = document.getElementById('commentText');
    if (commentText) {
        commentText.value = '';
        commentText.placeholder = `Comment on move ${move.number}: ${move.from_square} → ${move.to_square}`;
        commentText.disabled = false;
        commentText.focus();
    }

    const saveBtn = document.getElementById('saveComment');
    if (saveBtn) saveBtn.disabled = false;
}

// Add historical moves to history (fixed: includes all hexagrams/keywords)
function addToMoveHistory(move) {
    const container = document.getElementById('historyEntries');
    if (!container) return;


    // Robustly parse move.move_data if present
    let moveData = move.move_data;
    if (typeof moveData === 'string') {
        try {
            moveData = JSON.parse(moveData);
        } catch (e) {
            // ...existing code...
            moveData = null;
        }
    }



    // Robust: always join all steps for display, matching modal and live move readout
    let coordsStr = '', hexagramsStr = '', keywordsStr = '', sequenceDisplay = '';
    let piece = '';
    if (moveData && Array.isArray(moveData.coords) && Array.isArray(moveData.hexagrams) && Array.isArray(moveData.keywords)) {
        coordsStr = moveData.coords.filter(Boolean).join(' → ');
        hexagramsStr = moveData.hexagrams.filter(Boolean).join(' → ');
        keywordsStr = moveData.keywords.filter(Boolean).join(' → ');
        // For timeline, show all steps in one line, similar to modal
        sequenceDisplay = moveData.coords.map((coord, i) => {
            const hex = moveData.hexagrams[i] !== undefined ? moveData.hexagrams[i] : '';
            const keyword = moveData.keywords[i] !== undefined ? moveData.keywords[i] : '';
            return `${coord} (${hex} ${keyword})`;
        }).join(' → ');
        // Prepend piece type if available
        piece = (typeof extractPieceFromMove === 'function') ? extractPieceFromMove(move) : (move.piece_type || 'Piece');
        sequenceDisplay = `${piece} ${sequenceDisplay}`;
    } else if (moveData == null && move.hex_from && move.hex_to) {
        coordsStr = move.chess_coordinates || '';
        hexagramsStr = `${move.hex_from} → ${move.hex_to}`;
        keywordsStr = `${move.keyword_from || ''} → ${move.keyword_to || ''}`;
        piece = (typeof extractPieceFromMove === 'function') ? extractPieceFromMove(move) : (move.piece_type || 'Piece');
        sequenceDisplay = `${piece} ${coordsStr} (${hexagramsStr} ${keywordsStr})`;
    } else {
        sequenceDisplay = 'N/A';
    }

    const moveCard = document.createElement('div');
    moveCard.className = 'move-card';
    moveCard.dataset.moveId = move.id;

    const timestamp = new Date(move.timestamp).toLocaleTimeString();

    moveCard.innerHTML = `
        <div class="move-card-header">
            <span class="move-number">#${move.number}</span>
            <span class="move-timestamp">${timestamp}</span>
        </div>
        <div class="move-notation">${sequenceDisplay}</div>
        <div class="move-hexagram-info">Coords: ${coordsStr}<br>Hexagrams: ${hexagramsStr}<br>Keywords: ${keywordsStr}</div>
        <div class="move-comments" id="comments-${move.id}">
            <small>Click to add comment...</small>
        </div>
    `;

    moveCard.addEventListener('click', () => selectMoveForCommenting(move));
    container.appendChild(moveCard);
    container.scrollTop = container.scrollHeight;
}

function selectMoveForCommenting(move) {
    lastMoveDetails = move;
    prefillCommentForm(move);

    document.querySelectorAll('.move-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-move-id="${move.id}"]`)?.classList.add('selected');

    // Show robust modal if available
    if (typeof window.showMoveCommentModal === 'function') {
        // Prepare robust joined strings for modal
        let coordsStr = Array.isArray(move.move_data?.coords) ? move.move_data.coords.filter(Boolean).join(' → ') : (move.chess_coordinates || '');
        let hexagramsStr = Array.isArray(move.move_data?.hexagrams) ? move.move_data.hexagrams.filter(Boolean).join(' → ') : (move.hex_from && move.hex_to ? `${move.hex_from} → ${move.hex_to}` : '');
        let keywordsStr = Array.isArray(move.move_data?.keywords) ? move.move_data.keywords.filter(Boolean).join(' → ') : (move.keyword_from && move.keyword_to ? `${move.keyword_from} → ${move.keyword_to}` : '');
        window.showMoveCommentModal({
            ...move,
            coordsStr,
            hexagramsStr,
            keywordsStr
        });
    }
}

async function saveComment(e) {
    e?.preventDefault();
    if (!lastMoveDetails) return showStatus('No move selected', 'error');

    const commentText = document.getElementById('commentText');
    if (!commentText?.value.trim()) return showStatus('Enter a comment', 'error');

    const saveBtn = document.getElementById('saveComment');
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
        const commentData = {
            move_id: parseInt(document.getElementById('moveId').value),
            content: commentText.value.trim(),
            hex_from: parseInt(document.getElementById('hexFrom').value) || null,
            hex_to: parseInt(document.getElementById('hexTo').value) || null,
            keyword_from: document.getElementById('keywordFrom').value || null,
            keyword_to: document.getElementById('keywordTo').value || null
        };

        const response = await window.safeFetch('backend/comments_api_clean.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(commentData)
        });

        const result = await response.json();
        if (result.success) {
            showStatus('Comment saved!', 'success');
            commentText.value = '';
            updateMoveCardWithComment(result.comment);
        } else {
            showStatus(`Failed: ${result.error}`, 'error');
        }
    } catch (error) {
    // ...existing code...
        showStatus('Error saving comment', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    }
}

function updateMoveCardWithComment(comment) {
    const commentsDiv = document.getElementById(`comments-${comment.move_id}`);
    if (commentsDiv) commentsDiv.innerHTML = `<strong>${comment.username}:</strong> ${comment.content}`;
}

function clearForm() {
    document.getElementById('commentText').value = '';
    document.getElementById('commentText').placeholder = 'Select a move to see details';
    ['moveId', 'hexFrom', 'hexTo', 'keywordFrom', 'keywordTo'].forEach(id => {
        const field = document.getElementById(id);
        if (field) field.value = '';
    });
}

function showStatus(message, type) {
    const statusEl = document.getElementById('commentStatus');
    if (statusEl) {
        statusEl.textContent = message;
        statusEl.className = `status-message ${type}`;
        statusEl.style.display = 'block';

        setTimeout(() => {
            statusEl.style.opacity = '0';
            setTimeout(() => { statusEl.style.display = 'none'; statusEl.style.opacity = '1'; }, 300);
        }, 3000);
    }
}

async function loadGameHistory() {
    if (!currentGameId) return;
    try {
        const response = await window.safeFetch(`backend/moves_api.php?game_id=${currentGameId}`);
        if (!response.ok) {
            // ...existing code...
            return;
        }
        const result = await response.json();
        if (result.moves) displayGameHistory(result.moves);
    } catch (error) {
    // ...existing code...
    }
}

function displayGameHistory(moves) {
    const container = document.getElementById('historyEntries');
    if (!container) return;
    container.innerHTML = '';
    moves.forEach(move => addToMoveHistory(move));
}

export { initializeCommentSystem, handleMoveMade };

// Load reference content for a specific move
function loadReferenceContent(moveDetails) {
    const referenceContent = document.getElementById('referenceContent');
    if (!referenceContent || !moveDetails) return;
    
    // Generate reference content based on move details
    const content = `
        <h3>Move Reference</h3>
        <p><strong>From:</strong> ${moveDetails.keywordFrom || 'Unknown'}</p>
        <p><strong>To:</strong> ${moveDetails.keywordTo || 'Unknown'}</p>
        <p><strong>Hexagram:</strong> ${moveDetails.hexFrom || 'N/A'} → ${moveDetails.hexTo || 'N/A'}</p>
        <div style="margin-top: 15px; font-style: italic; color: #aaa;">
            Reference content for interpreting this move will appear here.
        </div>
    `;
    
    referenceContent.innerHTML = content;
}
