<?php
// Handles the one-click actions on movies.php (favorite, watched +1, rate). POST only, CSRF-protected.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$back = (string)($_POST['back'] ?? '');
if (!preg_match('/^(movies|index)\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/', $back)) $back = 'movies.php';

$id  = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$act = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) redirect('movies.php');
if (!csrf_valid()) { flash('Your session expired. Please try again.', 'error'); redirect($back); }

if ($act === 'favorite') {
    $s = $conn->prepare('UPDATE movies SET favorite = 1 - favorite WHERE movie_id = ?');
    $s->bind_param('i', $id); $s->execute();
    flash('Favorite updated.');
} elseif ($act === 'watched') {
    $s = $conn->prepare("UPDATE movies SET watch_status = 'Watched', watch_count = watch_count + 1 WHERE movie_id = ?");
    $s->bind_param('i', $id); $s->execute();
    flash('Logged as watched.');
} elseif ($act === 'rate') {
    $v = (string)($_POST['value'] ?? '');
    if ($v === 'none') {
        $s = $conn->prepare('UPDATE movies SET user_rating = NULL WHERE movie_id = ?');
        $s->bind_param('i', $id); $s->execute();
        flash('Rating cleared.');
    } elseif (ctype_digit($v) && (int)$v >= 1 && (int)$v <= 10) {
        $r = (float)$v;
        $s = $conn->prepare('UPDATE movies SET user_rating = ? WHERE movie_id = ?');
        $s->bind_param('di', $r, $id); $s->execute();
        flash('Rating saved.');
    }
}
redirect($back);
