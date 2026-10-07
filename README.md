# CineTrack

*Your movies. Your story.* A personal movie collection and watchlist built with **PHP and MySQL**. Keep detailed movie records, track what you've watched and what's next, rate and review movies, and find anything fast with search and filters. The interface is dark and cinematic, with purple, magenta, and blue accents.

![Dashboard](docs/screenshots/dashboard.png)

## Features

**Landing page** (`landing.php`)
- Public overview page: hero, feature highlights, the full toolset, a 4-step "How it works", and a filterable showcase of sample movies and series
- The Get Started / Start Tracking buttons are placeholders for the upcoming signup and login pages

**Collection**
- Full CRUD for movies: add, view, edit, delete
- 20 fields per movie, including genre, director, cast, runtime, age rating, streaming platform, personal rating, and review
- Seeded with 52 movies, including 24 Marvel Cinematic Universe films

**Dashboard**
- Stat cards: total movies, watched, to watch, favorites, average rating, hours watched
- "Pick something for tonight": a random unwatched movie, weighted by priority (High is picked 3x as often as Low)
- High-priority movies to watch next, and a "Continue watching" row for movies in progress
- Top genres chart that links into the filtered collection

**Search and filters**
- Live search across title, director, and cast
- Filter by genre, status, priority, streaming platform, favorites, year range, minimum rating, and runtime
- Active-filter chips; click one to remove only that filter
- Seven sort options, grid or list view, and pagination
- Every filtered view has a shareable URL

**Quick actions** (no page reload to edit)
- Favorite toggle, "Watched +1" (sets status and adds to the watch count), and one-click 1-10 rating

## Screenshots

| Collection | Movie details |
|---|---|
| ![Collection](docs/screenshots/movies.png) | ![Details](docs/screenshots/view.png) |

| Add / edit form | Filters |
|---|---|
| ![Form](docs/screenshots/form.png) | ![Filters](docs/screenshots/filters.png) |

## Tech stack

- **Backend:** PHP 8.1+ with `mysqli` and prepared statements
- **Database:** MySQL 8.0.16+ or MariaDB 10.2+ (uses `CHECK` constraints)
- **Frontend:** plain HTML, CSS (design tokens, responsive), and a small amount of vanilla JavaScript
- **Fonts:** Fraunces and Outfit via Google Fonts (system fonts are used if offline)
- **Local environment:** XAMPP

No frameworks and no external APIs.

## Setup (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\` (for example `C:\xampp\htdocs\movie_watchlist`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and go to the **Import** tab.
4. Import `sql/schema.sql`, then `sql/seed.sql`.
   *Warning: `schema.sql` drops and recreates the `movie_watchlist` database.*
5. Open `http://localhost/movie_watchlist/` (use your folder name).

If your MySQL root user has a password, set it in `config/db.php`.

## Project structure

```
movie_watchlist/
├── landing.php          Public landing page
├── index.php            Dashboard
├── movies.php           Collection with search, filters, sorting, paging
├── add_movie.php        Create
├── view_movie.php       Read (single movie)
├── edit_movie.php       Update
├── delete_movie.php     Delete (POST only)
├── quick_action.php     Favorite / watched +1 / rate (POST only)
├── tools/fetch_posters.php  One-time poster fetcher (local only)
├── config/db.php        Database connection and session
├── includes/
│   ├── header.php, footer.php
│   ├── helpers.php      Escaping, formatting, CSRF, flash messages, posters
│   └── movie_form.php   Shared 20-field form, validation, and DB parameters
├── assets/css/style.css Design system
├── assets/js/app.js     Menu, flash messages, modal
└── sql/schema.sql, seed.sql (and posters.sql after running the poster tool)
```

## Database

One table, `movies`, with 20 fields:

| Field | Type | Notes |
|---|---|---|
| `movie_id` | INT, auto-increment | Primary key |
| `title`, `short_description` | VARCHAR, TEXT | |
| `release_year` | SMALLINT | `CHECK` 1888-2100 |
| `genre`, `director`, `cast` | VARCHAR | |
| `duration_minutes` | SMALLINT | `CHECK` > 0; shown as "2h 30m" |
| `language`, `country` | VARCHAR | |
| `age_rating` | VARCHAR(10) | G, PG, PG-13, R, NC-17, NR |
| `watch_status` | ENUM | To Watch, Watching, Watched |
| `watch_count` | SMALLINT | Default 0 |
| `date_added` | DATE | Defaults to today |
| `poster_url` | VARCHAR(500), NULL | External image URL |
| `user_rating` | DECIMAL(3,1), NULL | `CHECK` 0-10; NULL means not rated |
| `review` | TEXT, NULL | |
| `streaming_platform` | VARCHAR(50), NULL | |
| `priority` | ENUM | Low, Medium, High |
| `favorite` | TINYINT(1) | 0 or 1 |

## Design and security decisions

- **Prepared statements everywhere.** User input is never concatenated into SQL. Sorting uses a whitelist of fixed clauses.
- **One shared form.** Add and Edit both use `includes/movie_form.php`, so the 20-field logic exists once.
- **CSRF protection** on every form and action. Delete and quick actions accept POST only, so a link can't change data.
- **Output escaping** with `htmlspecialchars()` on all displayed values.
- **Server-side validation** with inline error messages; optional fields are stored as `NULL`, so an empty rating never becomes 0.0.
- **Safe redirects.** Quick actions return only to `movies.php`.
- **Guarded formatting.** Invalid or zero dates display as "Not specified".
- **Poster fallback.** Movies without a poster, or with a broken link, show a generated gradient with the title's initials.

## Posters

Movies without a poster get a generated one: colors, glows, stars, a center shape, and the title are all derived from the title, so every movie looks different and always looks the same.

**Get real posters in one click:** open `http://localhost/<your-folder>/tools/fetch_posters.php` and press the button. It looks each movie up on Wikipedia (no API key needed), saves the poster links, and writes them to `sql/posters.sql`. Anything marked "check" is a best guess; fix it with **Edit movie** if it's wrong. Commit `sql/posters.sql` and collaborators can import it after `seed.sql` to get the same posters.

You can also paste any direct image link into **Poster URL** when adding or editing a movie.

## Possible next steps

- Multi-select genre filters
- User accounts so each person has their own watchlist
- Charts for ratings and watch history over time

## License

Add a license of your choice before publishing (for example MIT).
