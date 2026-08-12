# Intrachange Backend Planning Document

This document outlines the **backend requirements** and **user interface inputs** for Intrachange.  
It serves as a framework for database design, user interactions, and gameplay data capture.  
(Frontend JavaScript files will be considered separately.)

---

## 1. User Entry Point

When a player visits the platform, they encounter two options:

- **Play as Guest**  
  - No account required.  
  - Limited functionality (e.g., quick games, no saved history).  

- **Login / Register**  
  - Requires account creation.  
  - Enables full functionality, game history, and matchmaking.  

---

## 2. User Account Information

For registered users, the following data should be stored in the database:

- **Username** (unique, required)  
- **Email** (required, confirmation via email link)  
- **Password** (hashed, required)  
- **Profile Page Data** (optional fields):
  - Display name / bio  
  - Avatar or profile image  
  - Location (optional)  
  - Matchmaking preferences (e.g., play speed, preferred opponents)  

Future expansion: ratings, friends list, and achievements.

---

## 3. Gameplay Interaction: Move Cards

When a move is made on the chessboard, a **Move Card** pops up.  

- The card **automatically displays**:
  - The piece moved  
  - Starting square / keyword  
  - Ending square / keyword  
  - Example:  
    *“White Pawn from 9 Creativity → 17 Congruence”*

- The card then allows **user input** through fields such as:
  - **Reflection / Commentary** (text field)  
  - **Choice of Theme / Interpretation** (dropdown or tag system)  
  - **Optional Annotations** (link to images, audio, etc.)  

These fields are stored per-move in the game database.

---

## 4. Data Storage Model (High-Level)

- **Users Table**  
  Stores account information and profile data.  

- **Games Table**  
  Stores high-level data about each game:
  - Game ID  
  - Player 1 ID  
  - Player 2 ID  
  - Start time / end time  
  - Outcome  

- **Moves Table**  
  Stores detailed move data:
  - Move ID  
  - Game ID (foreign key)  
  - Player ID (foreign key)  
  - Chess coordinates (e.g., `d2 → d4`)  
  - Hexagram numbers (e.g., `9 → 17`)  
  - Keywords  
  - Reflection/commentary text  
  - Timestamp  

---

## 5. Future Features

- Matchmaking system (based on rating, preferences, or random)  
- Game replay viewer with annotations  
- Symbolic archive: searchable reflections by keyword, hexagram, or user  
- Collaborative annotation (shared interpretation of moves)  

---

## Summary

This backend plan establishes the **foundation for Intrachange user management and gameplay data capture**.  
Next steps will include designing the actual **database schema**, **API endpoints**, and the **frontend forms** that connect to this structure.
