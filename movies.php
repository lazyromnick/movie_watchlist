<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

const PER_PAGE = 24;
$sorts = [
    'added_desc'  => ['Recently added', 'date_added DESC, movie_id DESC'],
    'title_asc'   => ['Title A-Z', 'title ASC'],
    'title_desc'  => ['Title Z-A', 'title DESC'],
    'year_desc'   => ['Newest release', 'release_year DESC, title ASC'],
    'year_asc'    => ['Oldest release', 'release_year ASC, title ASC'],
    'rating_desc' => ['Highest rated', 'user_rating IS NULL, user_rating DESC, title ASC'],
    'priority'    => ['Priority', "FIELD(priority,'High','Medium','Low'), title ASC"],
];

// ---- Read filters (all whitelisted) ----
$q        = trim((string)($_GET['q'] ?? ''));
$genre    = trim((string)($_GET['genre'] ?? ''));
$platform = trim((string)($_GET['platform'] ?? ''));
$status   = in_array($_GET['status'] ?? '', WATCH_STATUSES, true) ? $_GET['status'] : '';
$priority = in_array($_GET['priority'] ?? '', PRIORITIES, true) ? $_GET['priority'] : '';
$fav      = !empty($_GET['fav']);
$sort     = array_key_exists($_GET['sort'] ?? '', $sorts) ? $_GET['sort'] : 'added_desc';
$view     = ($_GET['view'] ?? '') === 'list' ? 'list' : 'grid';
$page     = max(1, (int)($_GET['page'] ?? 1));
$yr = fn($k) => filter_var($_GET[$k] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1888, 'max_range' => 2100]]);
$yearFrom = $yr('year_from') ?: '';
$yearTo   = $yr('year_to') ?: '';
if ($yearFrom !== '' && $yearTo !== '' && $yearFrom > $yearTo) [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
$minRating = in_array($_GET['min_rating'] ?? '', ['5', '6', '7', '8', '9'], true) ? $_GET['min_rating'] : '';
$runtimes  = ['short' => ['Under 2 hours', 'duration_minutes < 120'], 'medium' => ['2 to 3 hours', 'duration_minutes BETWEEN 120 AND 180'], 'long' => ['Over 3 hours', 'duration_minutes > 180']];
$runtime   = array_key_exists($_GET['runtime'] ?? '', $runtimes) ? $_GET['runtime'] : '';

// ---- Build the query with prepared parameters ----
$where = []; $types = ''; $params = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR director LIKE ? OR `cast` LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
foreach (['genre' => $genre, 'watch_status' => $status, 'priority' => $priority, 'streaming_platform' => $platform] as $col => $val) {
    if ($val !== '') { $where[] = "$col = ?"; $params[] = $val; $types .= 's'; }
}
if ($fav) $where[] = 'favorite = 1';
if ($yearFrom !== '') { $where[] = 'release_year >= ?'; $params[] = $yearFrom; $types .= 'i'; }
if ($yearTo !== '')   { $where[] = 'release_year <= ?'; $params[] = $yearTo;   $types .= 'i'; }
if ($minRating !== '') { $where[] = 'user_rating >= ?'; $params[] = (float)$minRating; $types .= 'd'; }
if ($runtime !== '') $where[] = $runtimes[$runtime][1];   // fixed whitelist strings, no user input
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

function run_query(mysqli $c, string $sql, string $types, array $p): mysqli_result {
    $s = $c->prepare($sql);
    if ($p) $s->bind_param($types, ...$p);
    $s->execute();
    return $s->get_result();
}

$total  = (int)run_query($conn, "SELECT COUNT(*) FROM movies $w", $types, $params)->fetch_row()[0];
$pages  = max(1, (int)ceil($total / PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * PER_PAGE;
$movies = run_query($conn, "SELECT * FROM movies $w ORDER BY {$sorts[$sort][1]} LIMIT " . PER_PAGE . " OFFSET $offset", $types, $params)->fetch_all(MYSQLI_ASSOC);

$genres    = array_column($conn->query("SELECT DISTINCT genre FROM movies ORDER BY genre")->fetch_all(MYSQLI_NUM), 0);
$platforms = array_column($conn->query("SELECT DISTINCT streaming_platform FROM movies WHERE streaming_platform IS NOT NULL AND streaming_platform <> '' ORDER BY streaming_platform")->fetch_all(MYSQLI_NUM), 0);

$base = ['q' => $q, 'genre' => $genre, 'status' => $status, 'priority' => $priority, 'platform' => $platform, 'fav' => $fav ? 1 : '', 'year_from' => $yearFrom, 'year_to' => $yearTo, 'min_rating' => $minRating, 'runtime' => $runtime, 'sort' => $sort, 'view' => $view];
$qs = fn(array $o = []) => http_build_query(array_filter(array_merge($base, $o), fn($v) => $v !== '' && $v !== null));
$back = 'movies.php?' . $qs(['page' => $page]);
$filtered = $q !== '' || $genre !== '' || $status !== '' || $priority !== '' || $platform !== '' || $fav || $yearFrom !== '' || $yearTo !== '' || $minRating !== '' || $runtime !== '';

$chips = [];
if ($q !== '')        $chips[] = ['Search: ' . $q, ['q']];
if ($genre !== '')    $chips[] = ['Genre: ' . $genre, ['genre']];
if ($status !== '')   $chips[] = ['Status: ' . $status, ['status']];
if ($priority !== '') $chips[] = ['Priority: ' . $priority, ['priority']];
if ($platform !== '') $chips[] = ['On ' . $platform, ['platform']];
if ($fav)             $chips[] = ['Favorites', ['fav']];
if ($yearFrom !== '' || $yearTo !== '')
    $chips[] = [$yearFrom !== '' && $yearTo !== '' ? "Years $yearFrom-$yearTo" : ($yearFrom !== '' ? "From $yearFrom" : "Up to $yearTo"), ['year_from', 'year_to']];
if ($minRating !== '') $chips[] = ["Rated $minRating+", ['min_rating']];
if ($runtime !== '')   $chips[] = [$runtimes[$runtime][0], ['runtime']];

$page_title = 'Movies';
$active = 'movies';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Your movies</h1>
        <p class="muted"><?= $total ?> <?= $total === 1 ? 'movie' : 'movies' ?><?= $filtered ? ' match your filters' : ' in your collection' ?></p>
    </div>
    <a class="btn btn-primary" href="add_movie.php">Add movie</a>
</div>

<form class="card filters" method="get" data-autosubmit>
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, director, or cast" aria-label="Search movies" data-live-search>
    <button class="btn btn-primary btn-sm" type="submit">Search</button>
    <select name="genre" aria-label="Genre"><option value="">All genres</option>
        <?php foreach ($genres as $g): ?><option <?= $g === $genre ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select>
    <select name="status" aria-label="Status"><option value="">Any status</option>
        <?php foreach (WATCH_STATUSES as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <select name="priority" aria-label="Priority"><option value="">Any priority</option>
        <?php foreach (PRIORITIES as $p): ?><option <?= $p === $priority ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select>
    <select name="platform" aria-label="Streaming platform"><option value="">Any platform</option>
        <?php foreach ($platforms as $p): ?><option <?= $p === $platform ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select>
    <select name="sort" aria-label="Sort by">
        <?php foreach ($sorts as $k => [$label]): ?><option value="<?= $k ?>" <?= $k === $sort ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <input type="number" class="year" name="year_from" value="<?= e((string)$yearFrom) ?>" placeholder="From year" min="1888" max="2100" aria-label="From year">
    <input type="number" class="year" name="year_to" value="<?= e((string)$yearTo) ?>" placeholder="To year" min="1888" max="2100" aria-label="To year">
    <select name="min_rating" aria-label="Minimum rating"><option value="">Any rating</option>
        <?php foreach ([5, 6, 7, 8, 9] as $r): ?><option value="<?= $r ?>" <?= (string)$r === $minRating ? 'selected' : '' ?>><?= $r ?>+ stars</option><?php endforeach; ?></select>
    <select name="runtime" aria-label="Runtime"><option value="">Any length</option>
        <?php foreach ($runtimes as $k => [$label]): ?><option value="<?= $k ?>" <?= $k === $runtime ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <label class="check-inline"><input type="checkbox" name="fav" value="1" <?= $fav ? 'checked' : '' ?>> Favorites</label>
    <div class="view-toggle">
        <a class="btn btn-sm <?= $view === 'grid' ? 'btn-primary' : '' ?>" href="?<?= e($qs(['view' => 'grid', 'page' => ''])) ?>">Grid</a>
        <a class="btn btn-sm <?= $view === 'list' ? 'btn-primary' : '' ?>" href="?<?= e($qs(['view' => 'list', 'page' => ''])) ?>">List</a>
    </div>
    <?php if ($filtered): ?><a class="btn btn-sm" href="movies.php?view=<?= e($view) ?>">Clear filters</a><?php endif; ?>
</form>

<?php if ($chips): ?>
<div class="chips" aria-label="Active filters">
    <?php foreach ($chips as [$label, $keys]): ?>
        <a class="chip" href="?<?= e($qs(array_fill_keys($keys, '') + ['page' => ''])) ?>" title="Remove this filter"><?= e($label) ?> <span aria-hidden="true">&times;</span></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$movies): ?>
    <div class="card empty">
        <h2>No movies found</h2>
        <p><?= $filtered ? 'Try a different search or clear the filters.' : 'Add your first movie to get started.' ?></p>
        <a class="btn btn-primary" href="<?= $filtered ? 'movies.php' : 'add_movie.php' ?>"><?= $filtered ? 'Clear filters' : 'Add movie' ?></a>
    </div>
<?php else: ?>
<div class="grid grid-movies <?= $view === 'list' ? 'is-list' : '' ?>">
    <?php foreach ($movies as $m): $mid = (int)$m['movie_id']; ?>
    <article class="movie-card">
        <a href="view_movie.php?id=<?= $mid ?>" aria-label="View <?= e($m['title']) ?>"><?= poster($m['poster_url'], $m['title']) ?></a>
        <div class="meta">
            <h3><a href="view_movie.php?id=<?= $mid ?>"><?= e($m['title']) ?></a></h3>
            <p class="muted small"><?= (int)$m['release_year'] ?> &middot; <?= e($m['genre']) ?><?= $m['user_rating'] !== null ? ' &middot; &#9733; ' . number_format((float)$m['user_rating'], 1) : '' ?></p>
            <span class="badge <?= e(status_class($m['watch_status'])) ?>"><?= e($m['watch_status']) ?></span>
            <?php if ($m['watch_count'] > 0): ?><span class="muted small"> &times;<?= (int)$m['watch_count'] ?></span><?php endif; ?>
        </div>
        <div class="card-actions">
            <?php
            $hidden = csrf_field() . '<input type="hidden" name="id" value="' . $mid . '"><input type="hidden" name="back" value="' . e($back) . '">';
            ?>
            <form method="post" action="quick_action.php"><?= $hidden ?><input type="hidden" name="action" value="favorite">
                <button class="btn btn-icon <?= $m['favorite'] ? 'is-on' : '' ?>" type="submit" aria-pressed="<?= $m['favorite'] ? 'true' : 'false' ?>" title="<?= $m['favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>" aria-label="Toggle favorite">&#9829;</button></form>
            <form method="post" action="quick_action.php"><?= $hidden ?><input type="hidden" name="action" value="watched">
                <button class="btn btn-sm" type="submit" title="Set to Watched and add one to the watch count">Watched +1</button></form>
            <form method="post" action="quick_action.php"><?= $hidden ?><input type="hidden" name="action" value="rate">
                <select name="value" class="rate-select" aria-label="Rate <?= e($m['title']) ?>" onchange="this.form.submit()">
                    <option value="">Rate</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                    <?php if ($m['user_rating'] !== null): ?><option value="none">Clear</option><?php endif; ?>
                </select></form>
        </div>
    </article>
    <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Pages">
    <?php if ($page > 1): ?><a class="btn btn-sm" href="?<?= e($qs(['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn-sm" href="?<?= e($qs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
</nav>
<?php endif; endif; ?>
<script>
(() => {
    const form = document.querySelector('form[data-autosubmit]');
    if (!form) return;
    const search = form.querySelector('[data-live-search]');
    let timer;
    form.addEventListener('change', e => { if (e.target !== search) form.submit(); });
    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => { sessionStorage.setItem('refocus', '1'); form.submit(); }, 450);
    });
    if (sessionStorage.getItem('refocus')) {
        sessionStorage.removeItem('refocus');
        search.focus();
        search.setSelectionRange(search.value.length, search.value.length);
    }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
