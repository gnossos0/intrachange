<?php /* invite_friends.php */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invite a Player — Intrachange</title>
    <link rel="stylesheet" href="assets/global.css">
    <style>
        :root{
            --bg1:#f4f1e8; --bg2:#e8e0d0; --panel:#faf8f3; --border:#d4c4a8;
            --text:#1a1a1a; --muted:#5a5a5a; --btn:#1a1a1a; --btnText:#faf8f3;
        }
        html,body{height:100%}
        body{
            margin:0; font-family: Georgia, "Times New Roman", serif; color:var(--text);
            background:linear-gradient(135deg,var(--bg1) 0%,var(--bg2) 100%);
        }
        .wrap{max-width:880px;margin:40px auto;padding:0 20px;}
        .header{
            background:var(--panel); border:2px solid var(--border); border-radius:8px;
            padding:22px 28px; display:flex; align-items:center; justify-content:space-between;
        }
        .brand{margin:0; font-weight:normal; font-size:28px;}
        .nav a{color:var(--text); text-decoration:none; opacity:.85}
        .nav a:hover{opacity:1; text-decoration:underline;}
        .card{
            margin-top:18px; background:var(--panel); border:2px solid var(--border);
            border-radius:8px; padding:28px;
        }
        .card h2{margin:0 0 12px 0; font-weight:normal;}
    .muted{color:var(--muted,#5a5a5a); margin:0 0 18px 0;}
    .error{background:#fff3cd; color:#856404; border:1px solid #ffeaa7; padding:12px 14px; border-radius:6px; margin:0 0 16px 0;}
        form .row{margin-bottom:14px;}
        form label{display:block; margin:0 0 6px 0;}
        form input[type="text"],
        form input[type="email"],
        form input[type="number"],
        form textarea,
        form select{
            width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #cdbfa6; border-radius:6px;
            background:#fff; font-family:inherit; font-size:16px; color:var(--text);
        }
        form textarea{min-height:110px; resize:vertical;}
        input[type="submit"]{
            appearance:none; border:none; border-radius:6px; padding:12px 18px; cursor:pointer;
            background:var(--btn); color:var(--btnText); font-family:inherit; font-size:16px; margin-top:8px;
        }
        input[type="submit"]:hover{filter:brightness(1.05);}
    </style>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <meta name="theme-color" content="#e8e0d0">
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1 class="brand">Intrachange</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="card">
        <h2>Invite a Player</h2>
        <p class="muted">Send an invitation to someone <strong><em>without</em></strong> an Intrachange account.</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="send_invitation.php" method="post">
            <!-- Optional: Friend name -->
            <label for="friend_name">Friend's Name (optional)</label>
            <input type="text" name="friend_name" id="friend_name" placeholder="Enter name (optional)">

            <!-- Required: Friend email -->
            <label for="friend_email">Friend's Email</label>
            <input type="email" name="friend_email" id="friend_email" required placeholder="Enter their email address">

            <!-- Optional personal message -->
            <label for="personal_message">Personal Message (optional)</label>
            <textarea name="personal_message" id="personal_message" placeholder="Add a note..."></textarea>

            <!-- Submit button -->
            <input type="submit" value="Send Invitation">
        </form>
        <!-- End existing form -->
    </div>
</div>
</body>
</html>
