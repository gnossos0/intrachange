<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Game Details - Intrachange</title>
<link rel="icon" type="image/jpeg" href="img/icon.jpg">
<style>
  body {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
    min-height: 100vh;
    margin: 0;
    padding: 2rem;
    color: #4a554a;
    line-height: 1.5;
  }

  .header {
    text-align: center;
    margin-bottom: 3rem;
  }

  h1 {
    font-weight: bold;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #5a645a;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
  }
  
  .icon {
    width: 30px;
    height: 30px;
    object-fit: contain;
  }

  .game-info {
    max-width: 800px;
    margin: 0 auto 2rem;
    background: #fafcfa;
    border: 1px solid #d0d8d0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(90, 100, 90, 0.08);
  }

  .players {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    font-size: 0.95rem;
    background-color: #f5f8f5;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid #8a9d8a;
  }

  .moves-container {
    max-width: 900px;
    margin: 0 auto;
    background: #fafcfa;
    border: 1px solid #d0d8d0;
    border-radius: 12px;
    padding: 1.5rem;
  }

  .move-item {
    border-bottom: 1px solid #e8ece8;
    padding: 1rem 0;
    position: relative;
  }

  .move-item:last-child {
    border-bottom: none;
  }

  .back-nav {
    max-width: 800px;
    margin: 0 auto 1.5rem;
    text-align: left;
  }

  .back-button {
    color: #5a645a;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: color 0.2s ease;
  }

  .back-button:hover {
    color: #8a9d8a;
  }

  .move-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .move-number {
    font-weight: bold;
    color: #5a645a;
    font-size: 0.9rem;
  }

  .move-details {
    font-size: 0.85rem;
    color: #7a8d7a;
    margin-bottom: 0.5rem;
  }

  .move-notation {
    font-family: monospace;
    background: #f0f4f0;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    display: inline-block;
    margin-right: 0.5rem;
  }

  .hexagram-info {
    font-size: 0.8rem;
    color: #6a756a;
    margin: 0.25rem 0;
  }

  .comment-section {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f8faf8;
    border-radius: 6px;
    border-left: 3px solid #a8b5a8;
  }

  .comment-text {
    margin-bottom: 0.5rem;
    line-height: 1.4;
  }

  .comment-url {
    font-size: 0.8rem;
  }

  .comment-url a {
    color: #4a7c59;
    text-decoration: none;
  }

  .comment-url a:hover {
    text-decoration: underline;
  }

  .edit-button {
    background: #8a9d8a;
    color: white;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .edit-button:hover {
    background: #7a8d7a;
  }

  .edit-form {
    display: none;
    margin-top: 0.5rem;
    background: #ffffff;
    padding: 1rem;
    border: 1px solid #d0d8d0;
    border-radius: 6px;
  }

  .edit-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 0.5rem;
    border: 1px solid #d0d8d0;
    border-radius: 4px;
    resize: vertical;
    margin-bottom: 0.5rem;
  }

  .edit-form input[type="url"] {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d0d8d0;
    border-radius: 4px;
    margin-bottom: 0.75rem;
  }

  .form-buttons {
    display: flex;
    gap: 0.5rem;
  }

  .save-button {
    background: #4a7c59;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
  }

  .cancel-button {
    background: #a8a8a8;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
  }

  .back-link {
    text-align: center;
    margin: 2rem 0;
  }

  .back-link a {
    color: #5a645a;
    text-decoration: none;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border: 1px solid #d0d8d0;
    border-radius: 6px;
    background: #fafcfa;
    transition: background-color 0.2s;
  }

  .back-link a:hover {
    background: #f0f4f0;
  }

  .loading {
    text-align: center;
    color: #7a8d7a;
    font-style: italic;
    padding: 2rem;
  }

  .error {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem auto;
    max-width: 600px;
    text-align: center;
  }

  .admin-badge {
    background: #4a7c59;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: bold;
    margin-left: 0.5rem;
  }
</style>
</head>
<body>

<div class="header">
  <h1>
    <img src="img/icon.jpg" alt="Intrachange" class="icon">
    Game Details
  </h1>
</div>

<div class="back-nav">
  <a href="archive_display.html" class="back-button">← Back to Game Archive</a>
</div>

<div id="gameInfo" class="game-info">
  <div class="loading">Loading game information...</div>
</div>

<div id="movesContainer" class="moves-container">
  <h2>Game Moves</h2>
  <div id="movesList">
    <div class="loading">Loading moves...</div>
  </div>
</div>

<div class="back-link">
  <a href="archive_display.html">← Back to Archives</a>
</div>

<script>
// Global API Base - Must be defined first for all fetch calls
window.API_BASE = window.location.origin + '/intrachange/';
console.log('API_BASE defined as:', window.API_BASE);

// Standardized fetch function with validation
window.safeFetch = async function(endpoint, options = {}) {
    const url = window.API_BASE + endpoint;
    console.log('Fetching:', url);
    
    const response = await fetch(url, options);
    if (!response.ok) {
        throw new Error(`HTTP error ${response.status}`);
    }
    
    const contentType = response.headers.get('content-type');
    if (contentType && contentType.includes('application/json')) {
        return await response.json();
    } else {
        return await response.text();
    }
};
</script>
// Get game ID from URL parameters
const urlParams = new URLSearchParams(window.location.search);
const gameId = urlParams.get('game_id');

if (!gameId) {
  document.getElementById('gameInfo').innerHTML = '<div class="error">No game ID specified</div>';
  document.getElementById('movesList').innerHTML = '<div class="error">Cannot load moves without game ID</div>';
} else {
  loadGameDetails(gameId);
}

async function loadGameDetails(gameId) {
  try {
    const response = await window.safeFetch(`backend/get_game_moves.php?game_id=${gameId}`);
    const data = response; // safeFetch handles JSON parsing
    
    if (data.success) {
      displayGameInfo(data.game, data.user_context);
      displayMoves(data.moves, data.user_context.is_admin);
    } else {
      throw new Error(data.error || 'Failed to load game');
    }
  } catch (error) {
    document.getElementById('gameInfo').innerHTML = `<div class="error">Error: ${error.message}</div>`;
    document.getElementById('movesList').innerHTML = `<div class="error">Error: ${error.message}</div>`;
  }
}

function displayGameInfo(game, userContext) {
  const gameInfoHtml = `
    <h3>Game #${game.id}</h3>
    <div class="players">
      <div>
        <strong>White:</strong> ${game.white_username || 'Unknown'}
      </div>
      <div>
        <strong>Black:</strong> ${game.black_username || 'Unknown'}
      </div>
    </div>
    <div class="details">
      <div><strong>Status:</strong> ${game.status}</div>
      <div><strong>Created:</strong> ${new Date(game.created_at).toLocaleString()}</div>
      ${userContext.is_admin ? '<span class="admin-badge">ADMIN VIEW</span>' : ''}
    </div>
  `;
  
  document.getElementById('gameInfo').innerHTML = gameInfoHtml;
}

function displayMoves(moves, isAdmin) {
  if (moves.length === 0) {
    document.getElementById('movesList').innerHTML = '<div class="loading">No moves found for this game</div>';
    return;
  }
  
  const movesHtml = moves.map(move => {
    const hexagramInfo = move.hex_from && move.hex_to ? 
      `Hexagrams: ${move.hex_from} (${move.keyword_from || 'Unknown'}) → ${move.hex_to} (${move.keyword_to || 'Unknown'})` :
      '';
    
    const hasComment = move.user_comment || move.comment_url;
    
    return `
      <div class="move-item" data-move-id="${move.id}">
        <div class="move-header">
          <span class="move-number">Move ${move.move_number}</span>
          ${isAdmin ? `<button class="edit-button" onclick="toggleEdit(${move.id})">Edit</button>` : ''}
        </div>
        
        <div class="move-details">
          <span class="move-notation">${move.chess_coordinates || move.from_position + '→' + move.to_position}</span>
          <span class="piece-info">${move.piece_type}</span>
        </div>
        
        ${hexagramInfo ? `<div class="hexagram-info">${hexagramInfo}</div>` : ''}
        
        <div class="comment-section" style="${hasComment ? '' : 'display: none;'}">
          <div class="comment-display" id="comment-display-${move.id}">
            ${move.user_comment ? `<div class="comment-text">${move.user_comment}</div>` : ''}
            ${move.move_number ? `<div class="move-image">
              <img src="backend/images/${move.move_number}.png" 
                   alt="Hexagram ${move.move_number}" 
                   style="max-width:200px; height:auto; border:1px solid #ccc; border-radius:8px; margin:10px 0;" 
                   onerror="this.style.display='none'">
            </div>` : ''}
          </div>
          
          ${isAdmin ? `
          <div class="edit-form" id="edit-form-${move.id}">
            <textarea id="comment-${move.id}" placeholder="Enter symbolism or meaning for this move...">${move.user_comment || ''}</textarea>
            <input type="url" id="url-${move.id}" placeholder="Optional URL for related content" value="${move.comment_url || ''}">
            <div class="form-buttons">
              <button class="save-button" onclick="saveEdit(${move.id})">Save</button>
              <button class="cancel-button" onclick="cancelEdit(${move.id})">Cancel</button>
            </div>
          </div>
          ` : ''}
        </div>
        
        ${!hasComment && isAdmin ? `
        <div class="comment-section" style="display: none;" id="empty-comment-${move.id}">
          <div class="edit-form">
            <textarea id="comment-${move.id}" placeholder="Add symbolism or meaning for this move..."></textarea>
            <input type="url" id="url-${move.id}" placeholder="Optional URL for related content">
            <div class="form-buttons">
              <button class="save-button" onclick="saveEdit(${move.id})">Save</button>
              <button class="cancel-button" onclick="cancelEdit(${move.id})">Cancel</button>
            </div>
          </div>
        </div>
        ` : ''}
      </div>
    `;
  }).join('');
  
  document.getElementById('movesList').innerHTML = movesHtml;
}

function toggleEdit(moveId) {
  const commentDisplay = document.getElementById(`comment-display-${moveId}`);
  const editForm = document.getElementById(`edit-form-${moveId}`);
  const emptyComment = document.getElementById(`empty-comment-${moveId}`);
  
  if (editForm) {
    editForm.style.display = editForm.style.display === 'block' ? 'none' : 'block';
    if (commentDisplay) {
      commentDisplay.style.display = editForm.style.display === 'block' ? 'none' : 'block';
    }
  } else if (emptyComment) {
    emptyComment.style.display = 'block';
  }
}

function cancelEdit(moveId) {
  const commentDisplay = document.getElementById(`comment-display-${moveId}`);
  const editForm = document.getElementById(`edit-form-${moveId}`);
  const emptyComment = document.getElementById(`empty-comment-${moveId}`);
  
  if (editForm) {
    editForm.style.display = 'none';
    if (commentDisplay) {
      commentDisplay.style.display = 'block';
    }
  }
  
  if (emptyComment) {
    emptyComment.style.display = 'none';
  }
}

async function saveEdit(moveId) {
  const comment = document.getElementById(`comment-${moveId}`).value.trim();
  const url = document.getElementById(`url-${moveId}`).value.trim();
  
  try {
    const response = await fetch('backend/admin_edit_move.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        move_id: moveId,
        user_comment: comment,
        comment_url: url
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Reload the page to show updated content
      window.location.reload();
    } else {
      alert('Error saving: ' + (result.error || 'Unknown error'));
    }
  } catch (error) {
    alert('Error: ' + error.message);
  }
}
</script>

</body>
</html>