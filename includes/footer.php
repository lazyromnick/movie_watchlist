</main>
<?php if (($active ?? '') === 'home'):
$footGenres = $conn->query("SELECT genre FROM movies GROUP BY genre ORDER BY COUNT(*) DESC, genre LIMIT 5")->fetch_all(MYSQLI_NUM);
?>
<footer class="site-footer">
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <a class="brand" href="index.php"><span class="brand-mark"><?= icon('film', 22) ?></span>
                    <span class="brand-text"><span class="brand-name"><?= e($nameA) ?><b><?= e($nameB) ?></b></span></span></a>
                <p class="muted foot-blurb">Your personal movie tracker. Log what you watch, save what is next, and rate and review every movie in one place.</p>
            </div>
            <div>
                <h4>Quick navigation</h4>
                <a href="index.php">Dashboard</a><a href="movies.php">All movies</a>
                <a href="watchlist.php">Watchlist</a><a href="movies.php?status=Watched">Watched</a>
                <a href="movies.php?fav=1">Favorites</a><a href="add_movie.php">Add a movie</a>
            </div>
            <div>
                <h4>Top genres</h4>
                <?php foreach ($footGenres as [$g]): ?><a href="movies.php?genre=<?= urlencode($g) ?>"><?= e($g) ?></a><?php endforeach; ?>
            </div>
        </div>
        <div class="foot-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built with PHP &amp; MySQL.</span>
            <span><?= e(APP_TAGLINE) ?></span>
        </div>
    </div>
</footer>
<?php endif; ?>
<script src="assets/js/app.js"></script>
<?php foreach (($extra_js ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
