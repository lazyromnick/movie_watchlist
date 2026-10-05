<?php
// Usage: $page_title = 'Movies'; $active = 'movies'; require 'includes/header.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
$page_title = $page_title ?? 'Movie Watchlist';
$active     = $active ?? '';
$flash      = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | Movie Watchlist</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="index.php">Movie Watchlist</a>
        <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false">Menu</button>
        <nav class="nav-links" id="nav-links">
            <a href="index.php"  class="<?= $active === 'home'   ? 'is-active' : '' ?>">Dashboard</a>
            <a href="movies.php" class="<?= $active === 'movies' ? 'is-active' : '' ?>">Movies</a>
            <a href="add_movie.php" class="btn btn-primary btn-sm">Add movie</a>
        </nav>
    </div>
</header>
<main class="wrap">
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status">
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>
