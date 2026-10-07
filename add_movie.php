<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/movie_form.php';

$m = movie_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = movie_from_post();
    if (!csrf_valid()) {
        $errors['title'] = 'Your session expired. Please submit again.';
    } else {
        $errors = movie_validate($m);
    }
    if (!$errors) {
        $sql = 'INSERT INTO movies (' . implode(',', array_map(fn($c) => "`$c`", MOVIE_COLS)) . ') VALUES (' . implode(',', array_fill(0, 19, '?')) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(MOVIE_TYPES, ...movie_params($m));
        $stmt->execute();
        flash('Movie added to your watchlist.');
        redirect('view_movie.php?id=' . $conn->insert_id);
    }
}

$page_title = 'Add movie';
$active = 'movies';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow">New entry</span>
        <h1>Add a new movie</h1>
        <p class="muted">Fill in the details and it joins your collection. Fields marked * are required.</p>
    </div>
    <a class="btn" href="movies.php"><?= icon('film', 16) ?> Back to movies</a>
</div>
<?php render_movie_form($m, $errors, 'Add movie to watchlist', 'movies.php'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
