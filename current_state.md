# Intrachange – Current State of Affairs

## Frontend

| Feature | Status | Notes |
|---------|--------|-------|
| Board Rendering | ✅ Loads dynamically via JS array | Uses grid layout; no table HTML |
| Pieces | ✅ Move according to standard chess rules | Console shows no errors |
| Hexagrams | ⚪ Not displayed | Hover previews and transformations pending |
| Esoteric Moves | ⚪ Not triggering | No console logs; logic present but not executing |
| Legal Move Highlighting | ⚪ Not implemented | Needs hover/selection feedback |

## JS Structure

- **Root Container:** `<div id="root">` established as canvas.
- **Modular Functions:** `renderBoard()`, `renderPieces()`, `renderHexagramInfo()`, `renderControls()` in progress.
- **Data-Driven Rendering:** Board state stored in arrays/objects; DOM generated via loops.
- **Event Handling:** Click events wired via JS; hover effects pending.
- **Dynamic Updates:** Board state updates on moves; UI re-renders only standard moves.
- **Styling:** Grid layout and basic piece styles exist; advanced animations not added.

## Backend / Data

- `hexagrams.mjs` present and accessible.
- `esotericMoves.mjs` exists, integration with board logic incomplete.
- Move logging structure conceptual; hexagram metadata capture not implemented.

## Pending / Next Steps

1. Verify board controller & UI paths for new JS array structure.
2. Implement automatic Esoteric Moves and logging.
3. Highlight legal moves and potential Esoteric Moves on hover.
4. Integrate hexagram visuals with piece movement.
5. Add animations (Framer Motion / CSS) for Esoteric “bounce.”
6. Capture move metadata (coordinates, hexagram, keywords, Exoteric/Esoteric classification).
7. Test Knight exceptions, Pawn return rules, and edge cases.
8. Ensure asynchronous play and server-side state persistence.

## Testing Status

- Basic moves: functional ✅  
- Esoteric logic: not yet executed ⚪  
- UI interactions: partial; hover & highlighting pending ⚪  

## Notes

- Recent `index.html` rewrite simplified the structure; dependent modules need path adjustments.
- Copilot Agent onboarding focus: understanding modular structure, mapping data → logic → DOM, and rules integration (Exoteric/Esoteric).
