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
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header"><a class="brand" href="/">KISS <span>Reviews</span></a></header>
<main class="wrap">
HTML;
}

function page_foot(): void
{
    $year = gmdate('Y');
    echo <<<HTML
</main>
<footer class="site-footer">
  <p>&copy; {$year} KISS Reviews</p>
</footer>
<script src="/assets/app.js" defer></script>
</body>
</html>
HTML;
}
