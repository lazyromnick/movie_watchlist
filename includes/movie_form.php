<?php
// Single source of truth for the 20-field movie record: defaults, input, validation, DB params, form.
// Used by add_movie.php and edit_movie.php so the two can never drift apart.
declare(strict_types=1);

const MOVIE_COLS = ['title','short_description','release_year','genre','director','cast','duration_minutes',
    'language','country','age_rating','watch_status','watch_count','date_added','poster_url','user_rating',
    'review','streaming_platform','priority','favorite'];
const MOVIE_TYPES = 'ssisssissssissdsssi';   // bind_param types, same order as MOVIE_COLS

function movie_defaults(): array {
    $m = array_fill_keys(MOVIE_COLS, '');
    return array_merge($m, ['language' => 'English', 'watch_status' => 'To Watch', 'watch_count' => '0',
        'date_added' => date('Y-m-d'), 'priority' => 'Medium', 'favorite' => 0]);
}

function movie_from_post(): array {
    $m = [];
    foreach (MOVIE_COLS as $c) $m[$c] = trim((string)($_POST[$c] ?? ''));
    $m['favorite'] = isset($_POST['favorite']) ? 1 : 0;
    return $m;
}

function movie_validate(array $m): array {
    $e = [];
    $text = ['title' => 150, 'genre' => 50, 'director' => 100, 'cast' => 255, 'language' => 40, 'country' => 60];
    foreach ($text as $c => $max) {
        if ($m[$c] === '') $e[$c] = 'This field is required.';
        elseif (mb_strlen($m[$c]) > $max) $e[$c] = "Keep this under $max characters.";
    }
    if ($m['short_description'] === '') $e['short_description'] = 'This field is required.';
    $int = fn($v, $lo, $hi) => filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => $lo, 'max_range' => $hi]]) !== false;
    if (!$int($m['release_year'], 1888, 2100))      $e['release_year'] = 'Enter a year between 1888 and 2100.';
    if (!$int($m['duration_minutes'], 1, 1000))     $e['duration_minutes'] = 'Enter minutes between 1 and 1000.';
    if (!$int($m['watch_count'], 0, 9999))          $e['watch_count'] = 'Enter 0 or more.';
    if (!in_array($m['age_rating'], AGE_RATINGS, true))    $e['age_rating'] = 'Choose an age rating.';
    if (!in_array($m['watch_status'], WATCH_STATUSES, true)) $e['watch_status'] = 'Choose a status.';
    if (!in_array($m['priority'], PRIORITIES, true))       $e['priority'] = 'Choose a priority.';
    $d = DateTime::createFromFormat('Y-m-d', $m['date_added']);
    if (!$d || $d->format('Y-m-d') !== $m['date_added'])   $e['date_added'] = 'Enter a valid date.';
    if ($m['poster_url'] !== '' && (!filter_var($m['poster_url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $m['poster_url'])))
        $e['poster_url'] = 'Enter a full http(s) URL, or leave blank.';
    if ($m['user_rating'] !== '' && (!is_numeric($m['user_rating']) || $m['user_rating'] < 0 || $m['user_rating'] > 10))
        $e['user_rating'] = 'Enter a rating from 0 to 10, or leave blank.';
    if (mb_strlen($m['streaming_platform']) > 50) $e['streaming_platform'] = 'Keep this under 50 characters.';
    return $e;
}

/** Values in MOVIE_COLS order, with optional fields stored as NULL (never 0 or ''). */
function movie_params(array $m): array {
    $v = [];
    foreach (MOVIE_COLS as $c) {
        $x = $m[$c];
        if (in_array($c, ['release_year','duration_minutes','watch_count','favorite'], true)) $x = (int)$x;
        elseif ($c === 'user_rating') $x = $x === '' ? null : (float)$x;
        elseif (in_array($c, ['poster_url','review','streaming_platform'], true)) $x = $x === '' ? null : (string)$x;
        $v[] = $x;
    }
    return $v;
}

function f_open(string $n, string $label, array $err, bool $req, bool $full): void {
    echo '<div class="field' . ($full ? ' full' : '') . (isset($err[$n]) ? ' has-error' : '') . '">';
    echo '<label for="' . $n . '">' . e($label) . ($req ? ' <span class="req">*</span>' : '') . '</label>';
}
function f_close(string $n, array $err): void {
    echo (isset($err[$n]) ? '<div class="field-error">' . e($err[$n]) . '</div>' : '') . '</div>';
}
function f_input(string $n, string $label, array $m, array $err, string $type = 'text', array $a = [], bool $req = true, bool $full = false): void {
    f_open($n, $label, $err, $req, $full);
    $attr = '';
    foreach ($a as $k => $v) $attr .= " $k=\"" . e((string)$v) . '"';
    if ($type === 'textarea') echo "<textarea id=\"$n\" name=\"$n\"$attr>" . e((string)$m[$n]) . '</textarea>';
    else echo "<input id=\"$n\" name=\"$n\" type=\"$type\" value=\"" . e((string)$m[$n]) . "\"$attr>";
    f_close($n, $err);
}
function f_select(string $n, string $label, array $m, array $err, array $opts, bool $req = true): void {
    f_open($n, $label, $err, $req, false);
    echo "<select id=\"$n\" name=\"$n\">";
    if ($n === 'age_rating') echo '<option value="">Choose...</option>';
    foreach ($opts as $o) echo '<option' . ($m[$n] === $o ? ' selected' : '') . '>' . e($o) . '</option>';
    echo '</select>';
    f_close($n, $err);
}

function render_movie_form(array $m, array $err, string $submit, string $cancelUrl): void { ?>
    <form method="post" novalidate class="form-page">
        <?= csrf_field() ?>
        <div class="form-main">
            <?php if ($err): ?><div class="flash flash-error" role="alert"><span>Please fix the highlighted fields.</span></div><?php endif; ?>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--magenta)">Basic info</h2>
                <div class="form-grid">
                    <?php
                    f_input('title', 'Title', $m, $err, 'text', ['maxlength' => 150], true, true);
                    f_input('short_description', 'Description', $m, $err, 'textarea', [], true, true);
                    ?>
                </div>
            </section>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--gold)">Movie details</h2>
                <div class="form-grid">
                    <?php
                    f_input('release_year', 'Release year', $m, $err, 'number', ['min' => 1888, 'max' => 2100]);
                    f_input('genre', 'Genre', $m, $err, 'text', ['list' => 'genres', 'maxlength' => 50]);
                    f_input('director', 'Director', $m, $err);
                    f_input('cast', 'Main cast', $m, $err);
                    f_input('duration_minutes', 'Duration (minutes)', $m, $err, 'number', ['min' => 1]);
                    f_select('age_rating', 'Age rating', $m, $err, AGE_RATINGS);
                    f_input('language', 'Language', $m, $err);
                    f_input('country', 'Country', $m, $err);
                    ?>
                </div>
            </section>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--violet)">Watchlist</h2>
                <div class="form-grid">
                    <?php
                    f_select('watch_status', 'Watch status', $m, $err, WATCH_STATUSES);
                    f_select('priority', 'Priority', $m, $err, PRIORITIES);
                    f_input('watch_count', 'Watch count', $m, $err, 'number', ['min' => 0]);
                    f_input('date_added', 'Date added', $m, $err, 'date');
                    f_input('streaming_platform', 'Streaming platform', $m, $err, 'text', ['maxlength' => 50], false);
                    ?>
                    <div class="field check">
                        <input id="favorite" type="checkbox" name="favorite" value="1" <?= $m['favorite'] ? 'checked' : '' ?>>
                        <label for="favorite">Mark as favorite</label>
                    </div>
                </div>
            </section>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--blue)">Poster &amp; review</h2>
                <div class="form-grid">
                    <?php
                    f_input('poster_url', 'Poster URL', $m, $err, 'url', ['placeholder' => 'https://...'], false, true);
                    f_input('user_rating', 'Your rating (0-10)', $m, $err, 'number', ['min' => 0, 'max' => 10, 'step' => '0.1'], false);
                    f_input('review', 'Your review', $m, $err, 'textarea', [], false, true);
                    ?>
                </div>
            </section>
        </div>

        <aside class="form-side">
            <div class="card preview-card">
                <h2 class="form-h" style="--bar:var(--magenta)">Poster preview</h2>
                <div class="poster">
                    <?= poster_art($m['title'] !== '' ? (string)$m['title'] : 'New movie') ?>
                    <img id="poster-preview" alt="Poster preview" hidden<?= $m['poster_url'] ? ' src="' . e((string)$m['poster_url']) . '"' : '' ?>>
                </div>
                <p class="muted small">Paste a poster link to preview it. Without one, a generated poster is used.</p>
            </div>
            <div class="card side-actions">
                <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> <?= e($submit) ?></button>
                <a class="btn" href="<?= e($cancelUrl) ?>">Cancel</a>
            </div>
        </aside>
        <datalist id="genres"><?php foreach (['Action','Adventure','Animation','Comedy','Crime','Drama','Fantasy','Horror','Romance','Sci-Fi','Thriller'] as $g) echo '<option value="' . $g . '">'; ?></datalist>
    </form>
    <script>
    (() => {
        const input = document.getElementById('poster_url'), img = document.getElementById('poster-preview');
        if (!input || !img) return;
        const sync = () => { img.hidden = !(img.complete && img.naturalWidth > 0); };
        img.addEventListener('load', sync);
        img.addEventListener('error', () => { img.hidden = true; });
        input.addEventListener('input', () => {
            img.hidden = true;
            const v = input.value.trim();
            if (/^https?:\/\//i.test(v)) img.src = v; else img.removeAttribute('src');
        });
        sync();
    })();
    </script>
<?php }
