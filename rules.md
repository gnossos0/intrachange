# Intrachange Rules

## 1. Foundation
Intrachange is played on a standard chessboard with standard chess pieces.

It also has additional movement rules that can be triggered 50% of the time.

Every move begins with an Exoteric Move (a normal chess move to an Exoteric Square). If an Esoteric Move is triggered, the piece is automatically transferred to its Esoteric Square.

Players do not calculate Esoteric Moves. The system highlights legal Esoteric Moves when a piece is selected or when hovering over an Exoteric Square that will trigger one.

Each board square is mapped to a hexagram; all Esoteric Move destinations are strictly determined by `esotericMoves.mjs`.

## 2. Setup
Use standard chess setup (White: ranks 1–2; Black: ranks 7–8). White moves first.

A piece cannot move into a square occupied by another piece of its own color.

## 3. Movement Rules

### 3.1 Exoteric Moves
- Make a normal chess move into an Exoteric Square.
- If no Esoteric Move is triggered, the piece remains on that Exoteric Square.
- When an Exoteric Move does not result in an Esoteric Move, traditional rules of movement and capture apply (subject to the deleted rules listed below).

### 3.2 Esoteric Moves
- When an Exoteric Move does trigger an Esoteric Move, the piece is automatically transferred to its Esoteric Square.
- All Esoteric Move destinations are strictly determined by `esotericMoves.mjs`.
- During an Esoteric Move, pieces (except the Knight) may pass through other pieces that lie between the Exoteric and Esoteric Squares.

## 4. Capture
- An opponent’s piece is captured if it occupies the Esoteric Square your piece moves into.
- An opponent’s piece is not captured if it occupies the Exoteric Square your piece moves through on the way to the Esoteric Square (see Pawn Exceptions).
- Whenever an Exoteric Move does not result in an Esoteric Move, traditional capture rules apply (subject to the deleted rules listed below).

## 5. Knight Exception
- Knights cannot make an Esoteric Move if any piece (of either color) stands between the Exoteric Square and the Esoteric Square.
- All other pieces may "pass through" intervening pieces during an Esoteric Move.

## 6. Pawn Exceptions
a) Only the Pawn may capture on the Exoteric Square as it passes through to its resulting Esoteric Square when all conditions are met:
- The Exoteric Move is into the Player’s 7th rank.
- The capture follows the traditional pawn rule (one diagonal square forward into a square occupied by an opponent’s piece).
- The Esoteric Move is legal (the Esoteric Square is not occupied by a piece of the same color).

b) A Pawn cannot make an Exoteric Move straight forward into a square if that square is occupied by an opponent’s piece; it may only enter an occupied Exoteric Square via the traditional diagonal capture rule.  
c) From its initial position on the second rank, a Pawn may move one or two squares forward (standard chess rule).  
d) No promotion. Pawn cycling: There is no pawn promotion. Upon reaching its seventh rank (opponent’s second rank), the Pawn returns to the first rank (opponent’s eighth rank) and continues play as a Pawn. If the return square is occupied by a piece of the same color, the move is illegal.

## 7. Rules Deleted from Traditional Rules of Chess
The following rules are deleted from play:
- Castling
- En passant
- Pawn promotion
- Check
- Checkmate

## 8. End of Game
- There is no Check nor Checkmate in the Game. The Game ends only when a Player's King has been captured by an opponent's Esoteric Move.
- Any piece making an Esoteric Move into an Esoteric Square occupied by an opponent's King immediately captures the King and ends the Game.
- The King cannot be captured nor threatened by any Exoteric Move (see Pawn Exceptions, above).
- Games in which neither player is able to capture the opponent's King are drawn.

## Definitions
- **Exoteric Move:** A normal chess move made into an Exoteric Square; it may or may not trigger an Esoteric Move.  
- **Exoteric Square:** The square a piece enters by its Exoteric Move.  
- **Esoteric Move:** An automatic transfer that occurs immediately after certain Exoteric Moves; its destination is determined by `esotericMoves.mjs`.  
- **Esoteric Square:** The square a piece occupies after completing an Esoteric Move.
