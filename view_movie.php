<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('movies.php');

$stmt = $conn->prepare('SELECT * FROM movies WHERE movie_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
if (!$m) { http_response_code(404); flash('That movie no longer exists.', 'error'); redirect('movies.php'); }

$na = fn($v) => ($v === null || $v === '') ? 'Not specified' : e((string)$v);
$page_title = $m['title'];
$active = 'movies';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <?php if ($m['poster_url']): ?><div class="hero-bg" style="background-image:url('<?= e($m['poster_url']) ?>')"></div><?php endif; ?>
    <div class="hero-body">
        <?= poster($m['poster_url'], $m['title'], 'hero-poster') ?>
        <div>
            <span class="badge"><?= e($m['genre']) ?></span>
            <h1><?= e($m['title']) ?></h1>
            <p class="muted"><?= (int)$m['release_year'] ?> &middot; <?= e(format_duration((int)$m['duration_minutes'])) ?> &middot; <?= e($m['country']) ?> &middot; <?= $na($m['age_rating']) ?></p>
            <p><?= e($m['short_description']) ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="edit_movie.php?id=<?= (int)$m['movie_id'] ?>"><?= icon('edit', 16) ?> Edit movie</a>
                <button class="btn btn-danger" type="button" data-modal-open="#delete-dialog"><?= icon('trash', 16) ?> Delete movie</button>
                <a class="btn" href="movies.php"><?= icon('film', 16) ?> View all movies</a>
            </div>
        </div>
    </div>
</section>

<div class="detail-grid">
    <section class="card">
        <h2>Movie information</h2>
        <dl class="info">
            <div><dt>Director</dt><dd><?= $na($m['director']) ?></dd></div>
            <div><dt>Main cast</dt><dd><?= $na($m['cast']) ?></dd></div>
            <div><dt>Release year</dt><dd><?= (int)$m['release_year'] ?></dd></div>
            <div><dt>Runtime</dt><dd><?= e(format_duration((int)$m['duration_minutes'])) ?></dd></div>
            <div><dt>Language</dt><dd><?= $na($m['language']) ?></dd></div>
            <div><dt>Country</dt><dd><?= $na($m['country']) ?></dd></div>
            <div><dt>Age rating</dt><dd><?= $na($m['age_rating']) ?></dd></div>
            <div><dt>Date added</dt><dd><?= e(format_date($m['date_added'])) ?></dd></div>
        </dl>
    </section>
    <section class="card">
        <h2>Watchlist details</h2>
        <dl class="info">
            <div><dt>Status</dt><dd><?php if ($m['watch_status']): ?><span class="badge <?= e(status_class($m['watch_status'])) ?>"><?= e($m['watch_status']) ?></span><?php else: ?>Not specified<?php endif; ?></dd></div>
            <div><dt>Priority</dt><dd><span class="badge badge-<?= e(strtolower($m['priority'])) ?>"><?= e($m['priority']) ?></span></dd></div>
            <div><dt>Times watched</dt><dd><?= (int)$m['watch_count'] ?></dd></div>
            <div><dt>Your rating</dt><dd><?= e(format_rating($m['user_rating'])) ?></dd></div>
            <div><dt>Streaming on</dt><dd><?= $na($m['streaming_platform']) ?></dd></div>
            <div><dt>Favorite</dt><dd><?= $m['favorite'] ? 'Yes' : 'No' ?></dd></div>
        </dl>
    </section>
    <?php if (!empty($m['review'])): ?>
    <section class="card full-row">
        <h2>Your review</h2>
        <p><?= nl2br(e($m['review'])) ?></p>
    </section>
    <?php endif; ?>
</div>

<dialog id="delete-dialog">
    <h2>Delete this movie?</h2>
    <p class="muted"><?= e($m['title']) ?> will be permanently removed from your collection.</p>
    <form method="post" action="delete_movie.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$m['movie_id'] ?>">
        <div class="dialog-actions">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger"><?= icon('trash', 16) ?> Delete movie</button>
        </div>
    </form>
</dialog>
<?php require __DIR__ . '/includes/footer.php'; ?>
