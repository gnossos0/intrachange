# Custom Instructions

## Project Context
Intrachange is a correlative system that unites chess and the I Ching.  
The chessboard (64 squares) maps directly to the 64 hexagrams.

Moves are governed by two types: Exoteric (standard chess correspondence) and Esoteric (hexagram transformation rules). Each is possible 50% of the time.

Each hexagram is defined by a 6-line structure (solid = yang, broken = yin), keywords (from `hexagrams.mjs`), and correlations to chess pieces. Each hexagram is assigned to a square referred to by chess coordinates a1–h8.

Visual clarity, symbolic accuracy, and database consistency are core requirements.

## General Development Guidelines
- Never alter the core correlations between chess pieces and hexagram lines, ranks, or keywords.
- Always reference the official `hexagrams.mjs` for hexagram names.
- Preserve hexagram numbering. They are not sequential. Do not renumber or renormalize.
- Keep code modular. Game logic, rendering, and data (keywords, hexagrams, moves) must be in separate modules.
- Follow functional programming principles where possible. State should be immutable or managed in predictable flows.
- All UI/UX should prioritize clarity, calmness, and immersion (players should focus on meaning, not mechanics).

## Code Standards
- **Language/Frameworks:** JavaScript/TypeScript, React (Next.js optional), Tailwind for styling.
- **File Structure:**
  - `frontend/`: hexagram definitions, movement, esoteric/exoteric rules
  - `backend/`: eventual database of players’ moves and games
  - `img/`: images (hexagram graphics, icons)
- **Comments:** Keep them concise, explanatory, and neutral. Do not editorialize.
- **Accessibility:** Add meaningful alt text to hexagram images; if decorative, use `alt=""`.

## UI/UX Guidelines
- The chessboard must be interactive, with disabled/greyed-out squares showing unavailable moves or at least highlighting legal moves.
- Animations (Framer Motion or CSS transitions) should reinforce symbolic meaning (not just aesthetics) for the “bounce” of an Esoteric Move.
- All moves must be logged with:
  - Chess coordinate (e.g., d8 → g8)
  - Hexagram number(s)
  - Hexagram keyword(s)
  - User comment (optional)
- Avoid clutter: use grid-based layouts, rounded corners, soft shadows, and spacing.

## Development Flow
- Always show a diff of proposed changes before committing.
- Ensure consistency.
- Run tests for move validation and hexagram mapping before merging code.
- Keep `README.md` updated with new features or rules as implemented.

## Key Reminders
- Do not invent new correlations. Stick to documented Intrachange rules as described below.
