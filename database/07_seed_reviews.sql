
-- Ratings (1-10). One row per (movie, user); the trigger rebuilds avg_rating.
INSERT INTO dbProj_ratings (movie_id, user_id, stars) VALUES
  (21, 1,  8), (21, 2,  9), (21, 3,  8),
  (22, 1, 10), (22, 2, 10), (22, 3,  9),
  (23, 1, 10), (23, 2,  9), (23, 3, 10),
  (24, 1,  9), (24, 2,  8), (24, 3,  9),
  (25, 1,  9), (25, 2,  8), (25, 3,  9),
  (26, 1,  9), (26, 2, 10), (26, 3,  9),
  (27, 1, 10), (27, 2,  9), (27, 3, 10),
  (28, 1,  8), (28, 2,  8), (28, 3,  9),
  (29, 1,  9), (29, 2, 10), (29, 3,  9),
  (30, 1, 10), (30, 2, 10), (30, 3,  9),
  (31, 1,  8), (31, 2,  9), (31, 3,  9),
  (32, 1,  9), (32, 2,  8), (32, 3,  9),
  (33, 1,  7), (33, 2,  6), (33, 3,  7),
  (34, 1, 10), (34, 2, 10), (34, 3,  9),
  (35, 1, 10), (35, 2,  9), (35, 3,  9),
  (36, 1,  9), (36, 2,  9), (36, 3, 10),
  (37, 1,  9), (37, 2,  8), (37, 3,  9),
  (38, 1, 10), (38, 2,  9), (38, 3,  9),
  (39, 1,  8), (39, 2,  9), (39, 3,  9),
  (40, 1,  9), (40, 2, 10), (40, 3,  8);


INSERT INTO dbProj_comments (movie_id, user_id, body) VALUES
  (21, 2, 'Gorgeous animation and a surprisingly heartfelt story. The kids loved it.'),
  (22, 1, 'Still the gold standard. "Hope is a good thing, maybe the best of things."'),
  (22, 3, 'Andy and Red''s friendship gets me every single time.'),
  (23, 3, 'A masterclass in storytelling — every frame is iconic.'),
  (24, 2, 'Smart, tense and oddly funny. Rocky steals the show.'),
  (25, 1, 'The rare sequel that rivals the original. De Niro is phenomenal.'),
  (26, 2, 'Devastating and essential viewing. The girl in the red coat stays with you.'),
  (27, 3, 'One room, twelve men, pure tension. Brilliant writing.'),
  (28, 1, 'Charming and quietly moving. Marcellus the octopus is a delight.'),
  (29, 2, 'Pure imagination from start to finish — Miyazaki at his best.'),
  (30, 3, 'Ledger''s Joker redefined the genre. Chaos has never looked so good.'),
  (31, 2, 'The ultimate romance. Still playing in cinemas for a reason.'),
  (32, 1, 'Heartbreaking and beautiful. Bring tissues.'),
  (33, 3, 'A bittersweet comedy with real charm. Worth a watch.'),
  (34, 1, 'An epic conclusion. The lighting of the beacons gives me chills.'),
  (34, 2, 'So many endings and I''d happily have watched ten more.'),
  (35, 3, 'Sharp, funny and shocking. Deserved every award it won.'),
  (36, 2, 'Endlessly quotable and effortlessly cool.'),
  (37, 1, 'Beautiful animation and a story that really sticks with you.'),
  (38, 3, 'Ambitious and emotional. The docking scene is unreal.'),
  (39, 1, 'The definitive spaghetti western — and that score!'),
  (40, 2, 'Life is like a box of chocolates. A timeless crowd-pleaser.');


