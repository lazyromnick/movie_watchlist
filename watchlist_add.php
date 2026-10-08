<?php
// "Add to Watchlist": pick existing movies and set their status to To Watch (many at once).
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/picker.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['movie_ids'] ?? [])))));
    if (!csrf_valid()) {
        flash('Your session expired. Please try again.', 'error');
    } elseif (!$ids) {
        flash('Tick at least one movie to add.', 'error');
    } else {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $s = $conn->prepare("UPDATE movies SET watch_status = 'To Watch' WHERE watch_status <> 'To Watch' AND movie_id IN ($in)");
        $s->bind_param(str_repeat('i', count($ids)), ...$ids); $s->execute();
        $n = $s->affected_rows;
        flash("$n " . ($n === 1 ? 'movie' : 'movies') . ' added to your watchlist.');
        redirect('watchlist.php');
    }
    redirect('watchlist_add.php');
}

$movies = $conn->query("SELECT * FROM movies WHERE watch_status <> 'To Watch' ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$page_title = 'Add to Watchlist';
$active = 'watchlist';
$extra_js = ['assets/js/collections.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Watchlist</span>
        <h1>Add to Watchlist</h1>
        <p class="muted">Tick the movies you want to watch. Their status changes to To Watch.</p>
    </div>
    <div class="head-actions">
        <a class="btn" href="add_movie.php?status=To+Watch"><?= icon('plus', 16) ?> Add a brand-new movie</a>
        <a class="btn" href="watchlist.php"><?= icon('chevron-left', 16) ?> Back</a>
    </div>
</div>
<?php if ($movies): render_movie_picker($movies, [], 'your watchlist'); else: ?>
<div class="card empty"><h2>Everything is already on your watchlist</h2><p>Add a brand-new movie to keep it growing.</p><a class="btn btn-primary" href="add_movie.php?status=To+Watch">Add a brand-new movie</a></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
