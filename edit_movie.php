<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/movie_form.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('movies.php');

$stmt = $conn->prepare('SELECT * FROM movies WHERE movie_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { flash('That movie no longer exists.', 'error'); redirect('movies.php'); }

$m = array_intersect_key($row, array_flip(MOVIE_COLS));
foreach ($m as $k => $v) if ($v === null) $m[$k] = '';
if ($m['date_added'] === '0000-00-00') $m['date_added'] = date('Y-m-d');   // legacy-safe
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = movie_from_post();
    $errors = csrf_valid() ? movie_validate($m) : ['title' => 'Your session expired. Please submit again.'];
    if (!$errors) {
        $sql = 'UPDATE movies SET ' . implode(', ', array_map(fn($c) => "`$c` = ?", MOVIE_COLS)) . ' WHERE movie_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(MOVIE_TYPES . 'i', ...[...movie_params($m), $id]);
        $stmt->execute();
        flash('Movie updated.');
        redirect('view_movie.php?id=' . $id);
    }
}

$page_title = 'Edit ' . $row['title'];
$active = 'movies';
require __DIR__ . '/includes/header.php';
?>
<div class="form-layout">
    <aside class="card form-aside">
        <h1>Edit movie</h1>
        <p class="muted"><?= e($row['title']) ?></p>
        <a class="btn btn-sm" href="view_movie.php?id=<?= $id ?>">Cancel</a>
    </aside>
    <section class="card"><?php render_movie_form($m, $errors, 'Save changes'); ?></section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
