<?php
// Shared helpers: escaping, formatting, CSRF, flash messages, posters.
declare(strict_types=1);

const WATCH_STATUSES = ['To Watch', 'Watching', 'Watched'];
const PRIORITIES     = ['Low', 'Medium', 'High'];
const AGE_RATINGS    = ['G', 'PG', 'PG-13', 'R', 'NC-17', 'NR'];

function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** 181 -> "3h 1m", 90 -> "1h 30m", 45 -> "45m" */
function format_duration(?int $min): string {
    if (!$min || $min < 1) return 'Not specified';
    $h = intdiv($min, 60);
    $m = $min % 60;
    return $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
}

/** Guarded date formatting: zero/invalid dates never leak through. */
function format_date(?string $date): string {
    if (!$date || str_starts_with($date, '0000')) return 'Not specified';
    $d = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $d ? $d->format('M j, Y') : 'Not specified';
}

function format_rating(?string $rating): string {
    return $rating === null || $rating === '' ? 'Not rated' : number_format((float)$rating, 1) . ' / 10';
}

function status_class(string $status): string {
    return 'badge-' . strtolower(str_replace(' ', '-', $status));   // badge-to-watch ...
}

// ---- CSRF -------------------------------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

// ---- Flash messages ---------------------------------------------------
function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---- Posters ----------------------------------------------------------
/** Poster image with a generated gradient fallback (no URL, or broken URL). */
function poster(?string $url, string $title, string $class = ''): string {
    $hue  = crc32($title) % 360;
    $init = e(mb_strtoupper(mb_substr($title, 0, 2)));
    $style = "--h1:{$hue};--h2:" . (($hue + 70) % 360);
    $img = $url ? '<img src="' . e($url) . '" alt="Poster for ' . e($title) . '" loading="lazy" onerror="this.remove()">' : '';
    return '<div class="poster ' . e($class) . '" style="' . $style . '"><span class="poster-fallback">' . $init . '</span>' . $img . '</div>';
}
