<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function page_head(string $title, string $description): void
{
    $desc = h($description);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<meta name="description" content="{$desc}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <a class="logo" href="/" aria-label="KISS Reviews home">KISS<small>REVIEWS</small></a>
    <span class="tagline">Keep It Super Simple</span>
  </div>
</header>
<main>
HTML;
}

function page_foot(): void
{
    $year = gmdate('Y');
    echo <<<HTML
</main>
<footer class="site-footer">
  <div class="container">
    <p class="logo-foot">KISS Reviews</p>
    <p><a href="/privacy/">Privacy Policy</a> &middot; <a href="/terms/">Terms of Use</a></p>
    <p>&copy; {$year} KISS Reviews. All rights reserved.</p>
  </div>
</footer>
<script src="/assets/app.js" defer></script>
</body>
</html>
HTML;
}
