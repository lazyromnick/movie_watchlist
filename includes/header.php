<?php
// Usage: $page_title = 'Movies'; $active = 'movies'; require 'includes/header.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
$page_title = $page_title ?? APP_NAME;
$active     = $active ?? '';
$flash      = take_flash();

// Placeholder identity. When login exists, set $_SESSION['user'] = ['name' => ..., 'email' => ...].
$user = $_SESSION['user'] ?? ['name' => 'Guest', 'email' => null];
$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
$navWatchlist = (int)$conn->query("SELECT COUNT(*) FROM movies WHERE watch_status = 'To Watch'")->fetch_row()[0];
preg_match('/^(.+?)([A-Z][a-z0-9]*)$/', APP_NAME, $nm);
[$nameA, $nameB] = $nm ? [$nm[1], $nm[2]] : [APP_NAME, ''];
$navLinks = [
    'home'      => ['index.php', 'home', 'Home'],
    'movies'    => ['movies.php', 'film', 'Movies'],
    'watchlist' => ['watchlist.php', 'bookmark', 'Watchlist'],
    'watched'   => ['movies.php?status=Watched', 'check', 'Watched'],
    'favorites' => ['movies.php?fav=1', 'heart', 'Favorites'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($base_href)): ?><base href="<?= e($base_href) ?>"><?php endif; ?>
    <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_NAME) ?>: <?= e(APP_TAGLINE) ?>">
    <meta name="theme-color" content="#08040f">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="assets/img/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="index.php" aria-label="<?= e(APP_NAME) ?> home">
            <span class="brand-mark"><?= icon('film', 22) ?></span>
            <span class="brand-text">
                <span class="brand-name"><?= e($nameA) ?><b><?= e($nameB) ?></b></span>
                <span class="brand-tag"><?= e(APP_TAGLINE) ?></span>
            </span>
        </a>

        <nav class="nav-pill" id="nav-links" aria-label="Main">
            <?php foreach ($navLinks as $key => [$href, $ic, $label]): ?>
                <a href="<?= $href ?>" class="<?= $active === $key ? 'is-active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
                    <?= icon($ic, 16) ?> <?= e($label) ?>
                    <?php if ($key === 'watchlist' && $navWatchlist > 0): ?><span class="count"><?= $navWatchlist ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <form class="nav-search" action="movies.php" method="get" role="search">
                <?= icon('search', 16) ?>
                <input type="search" name="q" placeholder="Search movies..." aria-label="Search movies">
            </form>
            <a class="icon-btn icon-btn-primary" href="add_movie.php" title="Add movie" aria-label="Add movie"><?= icon('plus', 18) ?></a>
            <details class="profile">
                <summary aria-label="Account menu"><span class="avatar"><?= e($initial) ?></span><?= icon('down', 14) ?></summary>
                <div class="profile-menu">
                    <div class="profile-head">
                        <span class="avatar"><?= e($initial) ?></span>
                        <div><strong><?= e($user['name']) ?></strong><small><?= e($user['email'] ?? 'Accounts coming soon') ?></small></div>
                    </div>
                    <span class="menu-item is-disabled">Sign in <em>Soon</em></span>
                    <span class="menu-item is-disabled">Create account <em>Soon</em></span>
                </div>
            </details>
            <button class="icon-btn nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false"><?= icon('chevron', 18) ?></button>
        </div>
    </div>
</header>
<main class="wrap">
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status">
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>
