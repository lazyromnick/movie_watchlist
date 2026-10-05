<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

// ---- Stats (one query) ----
$st = $conn->query("SELECT COUNT(*) total,
        COALESCE(SUM(watch_status = 'Watched'), 0) watched,
        COALESCE(SUM(watch_status = 'To Watch'), 0) to_watch,
        COALESCE(SUM(watch_status = 'Watching'), 0) watching,
        COALESCE(SUM(favorite), 0) favs,
        AVG(user_rating) avg_rating,
        COALESCE(SUM(duration_minutes * watch_count), 0) mins
    FROM movies")->fetch_assoc();
$hours = (int)round($st['mins'] / 60);

function movie_rows(mysqli $c, string $sql): array {
    return $c->query($sql)->fetch_all(MYSQLI_ASSOC);
}
$continue = movie_rows($conn, "SELECT * FROM movies WHERE watch_status = 'Watching' ORDER BY date_added DESC LIMIT 6");
$next     = movie_rows($conn, "SELECT * FROM movies WHERE watch_status = 'To Watch' AND priority = 'High' ORDER BY date_added ASC, title ASC LIMIT 6");
$genres   = movie_rows($conn, "SELECT genre, COUNT(*) n FROM movies GROUP BY genre ORDER BY n DESC, genre ASC LIMIT 6");
$maxGenre = $genres ? (int)$genres[0]['n'] : 1;

// ---- "Pick something for tonight": random from unwatched, weighted by priority ----
$pick = null;
if (isset($_GET['pick'])) {
    $pool = movie_rows($conn, "SELECT * FROM movies WHERE watch_status IN ('To Watch','Watching')");
    if ($pool) {
        $weights = ['High' => 3, 'Medium' => 2, 'Low' => 1];
        $total = array_sum(array_map(fn($m) => $weights[$m['priority']] ?? 1, $pool));
        $roll = random_int(1, $total);
        foreach ($pool as $m) {
            $roll -= $weights[$m['priority']] ?? 1;
            if ($roll <= 0) { $pick = $m; break; }
        }
    }
}

function mini_card(array $m): string {
    $id = (int)$m['movie_id'];
    return '<a class="movie-card" href="view_movie.php?id=' . $id . '">' . poster($m['poster_url'], $m['title'])
        . '<div class="meta"><h3>' . e($m['title']) . '</h3><p class="muted small">' . (int)$m['release_year'] . ' &middot; ' . e($m['genre']) . '</p></div></a>';
}

$page_title = 'Dashboard';
$active = 'home';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Your watchlist at a glance</h1>
        <p class="muted"><?= $st['to_watch'] + $st['watching'] ?> movies still to see.</p>
    </div>
    <a class="btn btn-primary" href="?pick=1#pick">Pick something for tonight</a>
</div>

<?php if ($pick): ?>
<section class="card pick" id="pick">
    <a href="view_movie.php?id=<?= (int)$pick['movie_id'] ?>"><?= poster($pick['poster_url'], $pick['title']) ?></a>
    <div>
        <p class="muted">Tonight's pick</p>
        <h2><?= e($pick['title']) ?></h2>
        <p class="muted"><?= (int)$pick['release_year'] ?> &middot; <?= e(format_duration((int)$pick['duration_minutes'])) ?> &middot; <?= e($pick['genre']) ?><?= $pick['streaming_platform'] ? ' &middot; on ' . e($pick['streaming_platform']) : '' ?></p>
        <p><?= e($pick['short_description']) ?></p>
        <a class="btn btn-primary btn-sm" href="view_movie.php?id=<?= (int)$pick['movie_id'] ?>">View details</a>
        <a class="btn btn-sm" href="?pick=1#pick">Pick another</a>
    </div>
</section>
<?php elseif (isset($_GET['pick'])): ?>
<section class="card empty" id="pick"><h2>Nothing left to pick</h2><p>Every movie is marked as watched. Add something new.</p><a class="btn btn-primary" href="add_movie.php">Add movie</a></section>
<?php endif; ?>

<div class="grid grid-stats section">
    <div class="card stat"><div class="stat-value"><?= (int)$st['total'] ?></div><div class="stat-label">Movies</div></div>
    <div class="card stat"><div class="stat-value"><?= (int)$st['watched'] ?></div><div class="stat-label">Watched</div></div>
    <div class="card stat"><div class="stat-value"><?= (int)$st['to_watch'] ?></div><div class="stat-label">To watch</div></div>
    <div class="card stat"><div class="stat-value"><?= (int)$st['favs'] ?></div><div class="stat-label">Favorites</div></div>
    <div class="card stat"><div class="stat-value"><?= $st['avg_rating'] !== null ? number_format((float)$st['avg_rating'], 1) : '-' ?></div><div class="stat-label">Average rating</div></div>
    <div class="card stat"><div class="stat-value"><?= number_format($hours) ?></div><div class="stat-label">Hours watched</div></div>
</div>

<?php if ($continue): ?>
<section class="section">
    <div class="section-head"><h2>Continue watching</h2><a class="muted" href="movies.php?status=Watching">See all</a></div>
    <div class="grid grid-movies"><?php foreach ($continue as $m) echo mini_card($m); ?></div>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head"><h2>High priority next</h2><a class="muted" href="movies.php?status=To+Watch&priority=High">See all</a></div>
    <?php if ($next): ?>
        <div class="grid grid-movies"><?php foreach ($next as $m) echo mini_card($m); ?></div>
    <?php else: ?>
        <div class="card empty"><p>No high-priority movies waiting. Set a movie's priority to High and it shows up here.</p></div>
    <?php endif; ?>
</section>

<?php if ($genres): ?>
<section class="section">
    <h2>Top genres</h2>
    <div class="card">
        <?php foreach ($genres as $g): ?>
        <a class="bar-row" href="movies.php?genre=<?= urlencode($g['genre']) ?>">
            <span class="bar-label"><?= e($g['genre']) ?></span>
            <span class="bar-track"><span class="bar-fill" style="width:<?= round($g['n'] / $maxGenre * 100) ?>%"></span></span>
            <span class="muted"><?= (int)$g['n'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
