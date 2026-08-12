<?php
// Example: $selected_game_id, $moves, $current_game, $user_id should be set in parent PHP
// Output game state as JSON for JS modules
$game_state = [
    'game_id' => $selected_game_id ?? null,
    'moves' => $moves ?? [],
    'current_player' => $current_game['next_player_id'] ?? null,
    'user_id' => $user_id ?? null,
    'board' => [] // Add board state if available
];
?><style>
.comment-actions {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-top: 8px;
}

.comment-actions button {
    flex: 1;
}
</style><!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Intrachange Chessboard</title>
   <style>
       /* Container */
       #main-container {
           display: flex;
           gap: 20px;
           padding: 20px;
           font-family: Arial, sans-serif;
       }

       /* Chessboard */
       #chessboard-container {
           flex-shrink: 0;
       }
       #chessboard {
           border-collapse: collapse;
           border: 2px solid #333;
       }
       #chessboard td {
           width: 60px;
           height: 60px;
           text-align: center;
           vertical-align: middle;
           font-size: 32px;
           cursor: pointer;
       }
       #chessboard .white { background-color: #f0d9b5; }
       #chessboard .black { background-color: #b58863; }

       /* Comment Sidebar */
       #commentSidebar {
           width: 300px;
           max-width: 300px;
           min-width: 50px;
           border: 2px solid #333;
           border-radius: 8px;
           padding: 10px;
           background-color: #f9f9f9;
           transition: width 0.3s;
           overflow: hidden;
       }
       #commentSidebar.collapsed {
           width: 40px;
           padding: 5px;
       }

       /* Toggle button */
       #toggleSidebar {
           display: block;
           margin-bottom: 10px;
           cursor: pointer;
           font-weight: bold;
           width: 30px;
       }

       /* Hexagrams section */
       .nav-disclosure {
           margin-bottom: 15px;
       }
       .nav-item {
           display: flex;
           align-items: center;
           font-size: 14px;
           font-weight: bold;
           color: #333;
           cursor: pointer;
           padding: 8px 0;
           border: none;
           background: none;
       }
       .triangle {
           margin-right: 8px;
           font-size: 12px;
           transition: transform 0.2s;
       }
       .nav-disclosure[open] .triangle {
           transform: rotate(90deg);
       }
       .disclosure-items {
           padding-left: 20px;
       }
       .disclosure-items p {
           margin: 5px 0;
           font-size: 13px;
           color: #555;
       }
       .hexagram-item {
           display: flex;
           justify-content: space-between;
           align-items: center;
       }
       .hexagram-number {
           font-weight: bold;
           color: #2c3e50;
       }
       .hexagram-keyword {
           color: #7f8c8d;
           font-style: italic;
       }

       /* Comment form */
       #commentForm textarea {
           width: 100%;
           height: 60px;
           resize: none;
           padding: 5px;
           margin-bottom: 5px;
           font-size: 14px;
       }
       #commentForm button {
           margin-right: 5px;
           padding: 5px 10px;
           cursor: pointer;
       }

       /* Move history */
       #moveHistory {
           margin-top: 10px;
           max-height: 400px;
           overflow-y: auto;
       }
       .move-card {
           border: 1px solid #ccc;
           border-radius: 5px;
           padding: 5px;
           margin-bottom: 5px;
           cursor: pointer;
           background-color: #fff;
           transition: background 0.2s;
       }
       .move-card.selected {
           background-color: #d0eaff;
       }
       .move-card-header {
           display: flex;
           justify-content: space-between;
           font-size: 12px;
           margin-bottom: 2px;
       }
       .move-notation {
           font-weight: bold;
           font-size: 14px;
       }
       .move-hexagram-info {
           font-size: 12px;
           color: #555;
       }
       .move-comments {
           font-size: 12px;
           margin-top: 3px;
           color: #333;
       }
   </style>
</head>
<body>
<div id="main-container">

  <div id="chessboard-container">
    <table id="chessboard">
      <!-- JS will populate board squares and pieces -->
    </table>
  </div>

  <!-- Comment sidebar -->
  <div id="commentSidebar" class="collapsed">
    <button id="toggleSidebar">→</button>
    <details class="nav-disclosure" id="hexagrams-section" open>
      <summary class="nav-item" role="tab" aria-selected="false" data-target="hexagrams">
        <span class="triangle" aria-hidden="true">▶</span>
        Hexagrams
      </summary>
      <div class="disclosure-items" id="hexagram-list">
        <p style="color: #888; font-style: italic;">Make a move to see hexagram data...</p>
      </div>
    </details>

    <form id="commentForm">
      <input type="hidden" id="moveId">
      <input type="hidden" id="hexFrom">
      <input type="hidden" id="hexTo">
      <input type="hidden" id="keywordFrom">
      <input type="hidden" id="keywordTo">
      <textarea id="commentText" placeholder="Select a move to comment"></textarea><br>
      <button type="submit" id="saveComment">Save Comment</button>
      <button type="button" id="clearComment">Clear</button>
    </form>

    <div id="moveHistory">
      <!-- Move history cards will appear here -->
    </div>
  </div>

</div>

<script>
  window.INTRACHANGE_STATE = <?php echo json_encode($game_state); ?>;
</script>
<script type="module" src="frontend/boardController.mjs"></script>
<script type="module" src="frontend/commentSystem.mjs"></script>
</body>
</html>
