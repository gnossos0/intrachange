<?php
/**
 * Onboarding entry page for Intrachange.
 *
 * This is a lightweight welcome screen. It does not contain the lesson state
 * machine, the chessboard, or any production features (multiplayer, comments,
 * persistence). Its only job is to introduce the tutorial and launch it when
 * the player clicks "Begin Tutorial".
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-HQRDF2FZBV"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-HQRDF2FZBV');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Intrachange</title>
    <link rel="icon" type="image/jpeg" href="../img/icon.jpg">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #38423f;
            color: #f0ece6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', Arial, Helvetica, sans-serif;
        }

        .welcome-card {
            background: #2d3532;
            border: 2px solid #d4af37;
            border-radius: 8px;
            padding: 36px 40px;
            max-width: 680px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            color: #f0ece6;
        }

        .logo {
            width: 56px;
            height: 56px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0 0 10px 0;
            color: #d4af37;
            font-size: 2.2em;
            font-weight: normal;
        }

        .tagline {
            color: #b8b2aa;
            font-style: italic;
            margin: 0 0 20px 0;
            font-size: 1.05em;
        }

        .intro {
            font-size: 1em;
            line-height: 1.5;
            color: #e0dbd5;
            margin: 0 0 18px 0;
        }

        .intro p {
            margin: 0;
        }

        .intro p:last-child {
            margin-bottom: 0;
        }

        .what-you-learn {
            text-align: left;
            background: #232a28;
            border: 1px solid #4a5451;
            border-radius: 6px;
            padding: 18px 20px;
            margin: 18px 0 24px 0;
        }

        .what-you-learn h2 {
            margin: 0 0 12px 0;
            color: #d4af37;
            font-size: 1.15em;
            font-weight: 600;
        }

        .what-you-learn ul {
            margin: 0;
            padding: 0;
            color: #e0dbd5;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 24px;
            list-style: none;
        }

        .what-you-learn li {
            margin: 0;
        }

        .what-you-learn li::before {
            content: '\2713';
            color: #d4af37;
            font-weight: 700;
            margin-right: 8px;
        }

        .begin-button {
            display: inline-block;
            background: #d4af37;
            color: #1a1a1a;
            border: none;
            border-radius: 4px;
            padding: 14px 30px;
            font-size: 1.1em;
            font-family: 'Manrope', Arial, Helvetica, sans-serif;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .begin-button:hover {
            background: #f4d03f;
            transform: translateY(-1px);
        }

        @media (max-width: 560px) {
            .welcome-card {
                padding: 28px 24px;
            }

            .what-you-learn ul {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <img src="../img/icon.jpg" alt="Intrachange" class="logo">
        <h1>Welcome to Intrachange</h1>
        <p class="tagline">Chess transformed by the I Ching.</p>

        <div class="intro">
            <p>
                Learn the fundamentals of Intrachange in about five minutes. Make real moves, explore the hidden layer, and discover how to win.
            </p>
        </div>

        <div class="what-you-learn">
            <h2>What you'll do</h2>
            <ul>
                <li>Learn the chess foundation</li>
                <li>Discover the hidden hexagram layer</li>
                <li>Make an Esoteric Move</li>
                <li>Learn the winning condition</li>
            </ul>
        </div>

        <a href="onboard.php" class="begin-button">Begin Tutorial</a>
    </div>
</body>
</html>