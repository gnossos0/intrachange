# Intrachange Developer FAQ
## Next Steps Beyond the Chessboard

### Gameplay & Mechanics

1. **What exactly makes a move "esoteric"?**  
An esoteric move correlates with a changing line in the I Ching hexagram beneath a piece. When a piece moves from a square representing a line that is meant to change, it triggers a “bounce”—a transformation from the original hexagram to a new one. These rules are fixed and encoded in the system.

2. **What are the win conditions?**  
A game ends only when a player’s King is captured by an opponent’s Esoteric Move into an Esoteric Square. There is no check or checkmate. Exoteric moves cannot capture or threaten the King. If neither player manages a valid esoteric King capture, the game is a draw.

3. **Is this a turn-based game or asynchronous?**  
Moves are turn-based. Asynchronous play is expected—players may take turns over long time intervals.

4. **Are hexagrams visible at all times?**  
Yes, by default. Players may toggle overlays (numbers, names, or thematic mappings in art, music, psychology, etc.). Visibility is optional and strategic.

5. **How do exoteric and esoteric rules interact from a developer standpoint?**  
The system integrates standard chess movement with symbolic I Ching transformation. Each piece corresponds to a hexagram line; each square maps to a hexagram. Some moves may trigger both mechanics simultaneously.

6. **What level of UI/UX guidance is expected?**  
A continuum: from minimal enforcement (blocking illegal moves) to full previews and symbolic prompts. Players choose their level of guidance.

7. **Do players need to understand hexagram logic?**  
No. The system highlights and scaffolds meaning, with optional AI or symbolic aids. Players control how much interpretive depth they want.

### Tech & Infrastructure

8. **Engine choice?**  
Browser-native. No Unity or Godot.

9. **Platforms?**  
Web first, with possible later support for mobile/tablet.

10. **Client vs. server storage?**  
Client caches visuals and lightweight logic. Server stores game state, history, and asynchronous play data.

11. **Player profiles?**  
Yes. Each user has a profile and searchable archive of games (dates, modes, notes).

12. **Privacy & export?**  
Games are designed to be shareable. Export options (including symbolic narrative logs) are under consideration.

### Growth & Community

13. **Matchmaking and ranking?**  
Possibly. Could rank skill, engagement, or depth of interpretation. No fixed system yet.

### Meaning-Making & AI

14. **AI role in interpretation?**  
AI assists players in interpreting hexagram shifts with symbolic suggestions, context alignment, and narrative cues.

15. **Types of AI-generated support?**  
Text, imagery, and narrative layers tied to archetypes, motifs, or philosophical categories.

### Logistics & Collaboration

16. **Collaboration tools?**  
Google Docs, GitHub, Figma, Discord. Async workflow.

17. **Deadlines/funding?**  
No fixed deadlines. Milestone-driven, flexible. Minimal funding; priorities are foundation + collaboration.

18. **Intellectual property?**  
Ownership/licensing under discussion. Credit and licensing respect are central.

19. **Commercial or art?**  
Primarily an art/philosophy project, with optional commercialization later.

20. **Dev cadence?**  
Irregular but intentional. Expect communication, check-ins, creative accountability.

21. **Database requirements?**  
Must store:  
- Player IDs  
- Timestamped moves (with exoteric/esoteric + hexagram metadata)  
- Board states after each move  
- Optional player notes  
- Game status (ongoing/paused/complete)  

Games must be pausable and resumable. Playback of symbolic/narrative history is ideal.
