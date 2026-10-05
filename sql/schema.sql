-- Movie Watchlist: schema (fresh build, 20 fields)
-- Works on XAMPP MariaDB 10.2+ and MySQL 8.0.16+.
-- WARNING: drops and recreates the database.

DROP DATABASE IF EXISTS movie_watchlist;
CREATE DATABASE movie_watchlist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE movie_watchlist;

CREATE TABLE movies (
    movie_id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    title              VARCHAR(150)  NOT NULL,
    short_description  TEXT          NOT NULL,
    release_year       SMALLINT      NOT NULL,
    genre              VARCHAR(50)   NOT NULL,
    director           VARCHAR(100)  NOT NULL,
    `cast`             VARCHAR(255)  NOT NULL,
    duration_minutes   SMALLINT      NOT NULL,
    `language`         VARCHAR(40)   NOT NULL DEFAULT 'English',
    country            VARCHAR(60)   NOT NULL,
    age_rating         VARCHAR(10)   NOT NULL,
    watch_status       ENUM('To Watch','Watching','Watched') NOT NULL DEFAULT 'To Watch',
    watch_count        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    date_added         DATE          NOT NULL DEFAULT (CURRENT_DATE),
    poster_url         VARCHAR(500)  NULL,
    user_rating        DECIMAL(3,1)  NULL,              -- NULL = not rated
    review             TEXT          NULL,
    streaming_platform VARCHAR(50)   NULL,
    priority           ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    favorite           TINYINT(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (movie_id),
    CONSTRAINT chk_year     CHECK (release_year BETWEEN 1888 AND 2100),
    CONSTRAINT chk_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_rating   CHECK (user_rating IS NULL OR user_rating BETWEEN 0 AND 10),
    CONSTRAINT chk_favorite CHECK (favorite IN (0,1)),
    INDEX idx_title (title),
    INDEX idx_genre (genre),
    INDEX idx_status (watch_status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB;
