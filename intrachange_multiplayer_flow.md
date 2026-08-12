**Intrachange Multiplayer Flow Diagram**

```
+---------------------------+
| Step 0: Preconditions      |
| - Users must be logged in |
| - Games table exists      |
+---------------------------+
             |
             v
+---------------------------+
| Step 1: Manual Multiplayer |
| - Anna enters Gail        |
| - New game row inserted   |
| - Redirect Anna           |
| - Gail manually joins     |
+---------------------------+
             |
             v
+---------------------------+
| Step 2: Join Existing Game |
| - Check if game exists    |
|   between Anna & Gail     |
| - Yes -> reuse game_id    |
| - No -> create new game   |
+---------------------------+
             |
             v
+---------------------------+
| Step 3: Pending/Accept     |
| - Check if opponent online|
| - Online -> real-time      |
|   accept/decline           |
| - Offline -> email invite  |
| - Opponent joins -> active |
+---------------------------+
             |
             v
+---------------------------+
| Step 4: Turn Notifications |
| - Player moves             |
| - Update moves table       |
| - Send email/real-time     |
|   notification             |
+---------------------------+
             |
             v
+---------------------------+
| Step 5: Random Matchmaking |
| - User selects 'Random'    |
| - Add to matchmaking queue |
| - Match found -> create game|
| - Redirect both players     |
+---------------------------+
             |
             v
+---------------------------+
| Step 6: Full Integration   |
| - Invitations, accept/decline|
| - Online/offline handling    |
| - Turn enforcement           |
| - Notifications              |
| - Random matchmaking queue   |
+---------------------------+
```

**Incremental Deployment Plan**

| Step | Feature                       | Test Focus                                      |
|------|-------------------------------|------------------------------------------------|
| 1    | Manual multiplayer             | Verify turn-taking and board sync              |
| 2    | Join existing game check       | Prevent duplicates, both players same board   |
| 3    | Pending invitations & accept   | Ensure black_player joins only after acceptance|
| 4    | Turn notifications             | Verify emails / real-time updates             |
| 5    | Random matchmaking             | Queue system, auto-game creation              |
| 6    | Full online/offline integration| Invitations, acceptance, notifications, queue, status tracking |

**Notes:**
- White (host) always moves first.
- Status values: pending, active, cancelled, finished.
- Invitation methods: real-time, email.
- Each step can be deployed and tested incrementally before moving to the next.

