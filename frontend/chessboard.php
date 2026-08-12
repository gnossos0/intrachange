<?php
// Example: $selected_game_id, $moves, $current_game, $user_id should be set in parent PHP
// Output game state as JSON for JS modules
$game_state = [
    'game_id' => $selected_game_id ?? null,
    'moves' => $moves ?? [],
    'current_player' => $current_game['next_player_id'] ?? null,
    'user_id' => $user_id ?? null,
    'board' => [] // You can add board state here if available
];
?>
<style>
.comment-actions {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-top: 8px;
}

.comment-actions button {
    flex: 1;
}
</style>
<div id="gameContainer" style="display: flex; gap: 20px;">
    <!-- Chessboard -->
    <div id="chessboard-container">
        <table id="chessboard">
            <!-- Render board squares and pieces here, or let JS do it -->
        </table>
    </div>

    <!-- Comment Sidebar -->
    <div id="commentSidebar" class="collapsed" style="width: 300px; border: 1px solid #ccc; padding: 10px; overflow-y: auto; max-height: 600px;">
        <button id="toggleSidebar" style="margin-bottom: 10px;">→</button>
        <div id="currentMoveInfo">
            <div class="move-coords">Select a move</div>
            <div class="move-hexagrams"></div>
        </div>
        <form id="commentForm">
            <input type="hidden" id="moveId">
            <input type="hidden" id="hexFrom">
            <input type="hidden" id="hexTo">
            <input type="hidden" id="keywordFrom">
            <input type="hidden" id="keywordTo">
            <textarea id="commentText" placeholder="Select a move to comment" style="width:100%; height: 80px;"></textarea>
            <div class="comment-actions">
                <button type="button" id="openReference">Reference</button>
                <button type="submit" id="saveComment">Submit Comment</button>
            </div>
        </form>
        <div id="moveHistory" style="margin-top: 10px;"></div>
    </div>
</div>

<script>
  window.INTRACHANGE_STATE = <?php echo json_encode($game_state); ?>;
</script>
<script type="module" src="frontend/boardController.mjs"></script>
<script type="module" src="frontend/commentSystemClean.mjs"></script>
