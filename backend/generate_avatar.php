<?php
// Generate default avatar with user initials
header('Content-Type: image/svg+xml');

$username = $_GET['username'] ?? 'U';
$size = $_GET['size'] ?? 200;

// Get initials (max 2 characters)
$initials = strtoupper(substr($username, 0, 2));

// Color scheme based on username hash
$colors = [
    '#8a9d8a', '#7a8d7a', '#6d7a6d', '#5a645a',
    '#9da68a', '#8d967a', '#7a886d', '#64705a'
];
$colorIndex = abs(crc32($username)) % count($colors);
$bgColor = $colors[$colorIndex];

// Generate SVG
echo '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
  <rect width="200" height="200" fill="' . $bgColor . '"/>
  <text x="100" y="125" font-family="Arial, sans-serif" font-size="80" font-weight="bold" 
        text-anchor="middle" fill="white" style="dominant-baseline: middle;">
    ' . htmlspecialchars($initials) . '
  </text>
</svg>';
?>