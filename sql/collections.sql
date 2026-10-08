-- Watchlist folders. Safe to run on an existing database (select movie_watchlist first in phpMyAdmin).
-- The app also runs this automatically the first time you open the Watchlist page.
CREATE TABLE IF NOT EXISTS collections (
    collection_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(60)  NOT NULL,
    description   VARCHAR(255) NULL,
    color         VARCHAR(10)  NOT NULL DEFAULT 'violet',
    icon          VARCHAR(20)  NOT NULL DEFAULT 'folder',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id),
    UNIQUE KEY uq_collection_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collection_movies (
    collection_id INT UNSIGNED NOT NULL,
    movie_id      INT UNSIGNED NOT NULL,
    added_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id, movie_id),
    CONSTRAINT fk_cm_collection FOREIGN KEY (collection_id) REFERENCES collections (collection_id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_movie      FOREIGN KEY (movie_id)      REFERENCES movies (movie_id)           ON DELETE CASCADE
) ENGINE=InnoDB;
