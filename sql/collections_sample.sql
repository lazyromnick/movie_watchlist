-- Optional demo folders. Run after seed.sql (and after the Watchlist page has created the folder tables).
USE movie_watchlist;
INSERT IGNORE INTO collections (name, description, color, icon) VALUES
 ('Marvel Marathon', 'The big MCU movies, ready for a long weekend.', 'red', 'film'),
 ('Weekend Picks', 'Easy, fun watches for a lazy Saturday.', 'gold', 'clock'),
 ('Mind-Benders', 'Movies that make you think twice.', 'violet', 'star');
INSERT IGNORE INTO collection_movies (collection_id, movie_id)
SELECT c.collection_id, m.movie_id FROM collections c JOIN movies m ON
   (c.name = 'Marvel Marathon' AND m.title IN ('Iron Man','Thor','The Avengers','Avengers: Infinity War','Avengers: Endgame','Black Panther','Captain America: Civil War','Thor: Ragnarok'))
OR (c.name = 'Weekend Picks'   AND m.title IN ('Toy Story','The Lion King','Top Gun: Maverick','Spider-Man: Into the Spider-Verse','Jurassic Park'))
OR (c.name = 'Mind-Benders'    AND m.title IN ('Inception','Interstellar','The Matrix','Fight Club','Parasite'));
