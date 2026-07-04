<?php
// Shared <head> tags: favicon + font loading. Included by every page instead of
// duplicating this in each file. Fonts are loaded via <link> (with preconnect)
// rather than a CSS @import — the browser can fetch them in parallel with the
// stylesheet instead of waiting to discover the @import inside it, which avoids
// a flash of unstyled text on slower connections.
?>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23A9762F' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 21V10.5C4 6.36 7.58 3 12 3s8 3.36 8 7.5V21'/%3E%3Cpath d='M4 21h16'/%3E%3C/svg%3E" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/assets/styles.css" />
