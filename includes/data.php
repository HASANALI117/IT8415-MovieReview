<?php
require_once __DIR__ . '/db.php';

const MOVIE_SELECT = "
    SELECT m.movie_id                                   AS id,
           m.title                                      AS title,
           COALESCE(NULLIF(m.short_description,''), m.synopsis, '') AS description,
           m.avg_rating                                 AS rating,
           m.release_year                               AS year,
           m.image_url                                  AS poster,
           m.fanart_bg                                  AS fanart_bg,
           m.fanart_logo                                AS fanart_logo,
           m.color_tl, m.color_tr, m.color_br, m.color_bl,
           m.view_count                                 AS views,
           m.created_at                                 AS created_at,
           COALESCE((
               SELECT c.name
                 FROM dbProj_movie_categories mc
                 JOIN dbProj_categories c ON c.category_id = mc.category_id
                WHERE mc.movie_id = m.movie_id
                ORDER BY c.category_id
                LIMIT 1
           ), '')                                       AS genre
      FROM dbProj_movies m
";

function placeholder_movies(): array
{
    return [
        ['id' => 1, 'tmdb_id' => 693134, 'title' => 'Dune: Part Two', 'description' => 'Paul Atreides unites with the Fremen to seek revenge on the conspirators who destroyed his family.', 'rating' => 8.5, 'year' => 2024, 'genre' => 'Sci-Fi', 'creator' => 'admin', 'created_at' => '2024-03-01', 'poster' => 'assets/posters/693134.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/693134.jpg', 'fanart_logo' => 'assets/fanart/logos/693134.png', 'color_tl' => 'c4a24a', 'color_tr' => '8b6914', 'color_br' => '1a2a4a', 'color_bl' => '0d1a33', 'views' => 420],
        ['id' => 2, 'tmdb_id' => 872585, 'title' => 'Oppenheimer', 'description' => 'The story of J. Robert Oppenheimer and his role in the development of the atomic bomb.', 'rating' => 8.4, 'year' => 2023, 'genre' => 'Drama', 'creator' => 'admin', 'created_at' => '2024-02-20', 'poster' => 'assets/posters/872585.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/872585.jpg', 'fanart_logo' => 'assets/fanart/logos/872585.png', 'color_tl' => '2b2b2b', 'color_tr' => '6e5a3c', 'color_br' => '1a1410', 'color_bl' => '0d0b09', 'views' => 510],
        ['id' => 3, 'tmdb_id' => 27205, 'title' => 'Inception', 'description' => 'A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea.', 'rating' => 8.4, 'year' => 2010, 'genre' => 'Thriller', 'creator' => 'admin', 'created_at' => '2024-02-10', 'poster' => 'assets/posters/27205.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/27205.jpg', 'fanart_logo' => 'assets/fanart/logos/27205.png', 'color_tl' => '3a4a5a', 'color_tr' => '1e2a36', 'color_br' => '0e1620', 'color_bl' => '10242e', 'views' => 388],
        ['id' => 4, 'tmdb_id' => 1011985, 'title' => 'Kung Fu Panda 4', 'description' => 'Po must train a new Dragon Warrior while facing a wicked sorceress who plans to conjure all past villains.', 'rating' => 7.1, 'year' => 2024, 'genre' => 'Animation', 'creator' => 'admin', 'created_at' => '2024-03-08', 'poster' => 'assets/posters/1011985.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/1011985.jpg', 'fanart_logo' => 'assets/fanart/logos/1011985.png', 'color_tl' => 'e0a64a', 'color_tr' => 'b56a2a', 'color_br' => '5a3018', 'color_bl' => '8a4a20', 'views' => 295],
        ['id' => 5, 'tmdb_id' => 359724, 'title' => 'Ford v Ferrari', 'description' => 'American car designer Carroll Shelby and driver Ken Miles battle corporate interference to build a race car for Ford.', 'rating' => 8.0, 'year' => 2019, 'genre' => 'Action', 'creator' => 'admin', 'created_at' => '2024-01-30', 'poster' => 'assets/posters/359724.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/359724.jpg', 'fanart_logo' => 'assets/fanart/logos/359724.png', 'color_tl' => 'b03028', 'color_tr' => '6e1a16', 'color_br' => '2a0e0c', 'color_bl' => '4a1410', 'views' => 340],
        ['id' => 6, 'tmdb_id' => 419430, 'title' => 'Get Out', 'description' => 'A young Black man visits his white girlfriend\'s family estate and uncovers a disturbing secret.', 'rating' => 7.8, 'year' => 2017, 'genre' => 'Horror', 'creator' => 'admin', 'created_at' => '2024-01-22', 'poster' => 'assets/posters/419430.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/419430.jpg', 'fanart_logo' => 'assets/fanart/logos/419430.png', 'color_tl' => '3a5a3a', 'color_tr' => '1e2e1e', 'color_br' => '0c160c', 'color_bl' => '16241a', 'views' => 277],
        ['id' => 7, 'tmdb_id' => 313369, 'title' => 'La La Land', 'description' => 'A jazz pianist and an aspiring actress fall in love while pursuing their dreams in Los Angeles.', 'rating' => 7.9, 'year' => 2016, 'genre' => 'Romance', 'creator' => 'admin', 'created_at' => '2024-01-15', 'poster' => 'assets/posters/313369.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/313369.jpg', 'fanart_logo' => 'assets/fanart/logos/313369.png', 'color_tl' => '2a3a7a', 'color_tr' => '7a3a6a', 'color_br' => '1a1430', 'color_bl' => '301a4a', 'views' => 312],
        ['id' => 8, 'tmdb_id' => 496243, 'title' => 'Parasite', 'description' => 'A poor family schemes to become employed by a wealthy household by infiltrating it one member at a time.', 'rating' => 8.5, 'year' => 2019, 'genre' => 'Thriller', 'creator' => 'admin', 'created_at' => '2024-02-05', 'poster' => 'assets/posters/496243.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/496243.jpg', 'fanart_logo' => 'assets/fanart/logos/496243.png', 'color_tl' => '5a5a4a', 'color_tr' => '2e2e22', 'color_br' => '10100c', 'color_bl' => '242018', 'views' => 401],
        ['id' => 9, 'tmdb_id' => 438631, 'title' => 'Dune', 'description' => 'A noble family becomes embroiled in a war for control over the galaxy\'s most valuable asset.', 'rating' => 7.8, 'year' => 2021, 'genre' => 'Sci-Fi', 'creator' => 'admin', 'created_at' => '2024-01-10', 'poster' => 'assets/posters/438631.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/438631.jpg', 'fanart_logo' => 'assets/fanart/logos/438631.png', 'color_tl' => 'c89a5a', 'color_tr' => '8a6a3a', 'color_br' => '3a2a18', 'color_bl' => '5a4424', 'views' => 360],
        ['id' => 10, 'tmdb_id' => 120467, 'title' => 'The Grand Budapest Hotel', 'description' => 'A legendary concierge and his protege become embroiled in the theft of a priceless painting.', 'rating' => 8.1, 'year' => 2014, 'genre' => 'Comedy', 'creator' => 'admin', 'created_at' => '2023-12-28', 'poster' => 'assets/posters/120467.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/120467.jpg', 'fanart_logo' => 'assets/fanart/logos/120467.png', 'color_tl' => 'd06a8a', 'color_tr' => 'b04a6a', 'color_br' => '5a2438', 'color_bl' => '8a3a54', 'views' => 254],
        ['id' => 11, 'tmdb_id' => 324857, 'title' => 'Spider-Man: Into the Spider-Verse', 'description' => 'Teen Miles Morales becomes Spider-Man and joins other Spider-People across the multiverse.', 'rating' => 8.4, 'year' => 2018, 'genre' => 'Animation', 'creator' => 'admin', 'created_at' => '2023-12-20', 'poster' => 'assets/posters/324857.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/324857.jpg', 'fanart_logo' => 'assets/fanart/logos/324857.png', 'color_tl' => 'd02a6a', 'color_tr' => '3a2ad0', 'color_br' => '1a0a3a', 'color_bl' => '6a1a8a', 'views' => 433],
        ['id' => 12, 'tmdb_id' => 157336, 'title' => 'Interstellar', 'description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.', 'rating' => 8.4, 'year' => 2014, 'genre' => 'Sci-Fi', 'creator' => 'admin', 'created_at' => '2023-12-12', 'poster' => 'assets/posters/157336.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/157336.jpg', 'fanart_logo' => 'assets/fanart/logos/157336.png', 'color_tl' => '4a5a6a', 'color_tr' => '2a3642', 'color_br' => '0c1218', 'color_bl' => '18242e', 'views' => 478],
        ['id' => 13, 'tmdb_id' => 329865, 'title' => 'Arrival', 'description' => 'A linguist is recruited to communicate with extraterrestrial visitors before global tensions escalate.', 'rating' => 7.6, 'year' => 2016, 'genre' => 'Drama', 'creator' => 'admin', 'created_at' => '2023-12-01', 'poster' => 'assets/posters/329865.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/329865.jpg', 'fanart_logo' => 'assets/fanart/logos/329865.png', 'color_tl' => '6a7a7a', 'color_tr' => '3a4a4a', 'color_br' => '141c1c', 'color_bl' => '243030', 'views' => 289],
        ['id' => 14, 'tmdb_id' => 807, 'title' => 'Se7en', 'description' => 'Two detectives hunt a serial killer who uses the seven deadly sins as his motives.', 'rating' => 8.4, 'year' => 1995, 'genre' => 'Horror', 'creator' => 'admin', 'created_at' => '2023-11-20', 'poster' => 'assets/posters/807.jpg', 'fanart_bg' => 'assets/fanart/backgrounds/807.jpg', 'fanart_logo' => 'assets/fanart/logos/807.png', 'color_tl' => '3a3a3a', 'color_tr' => '1e1e1e', 'color_br' => '080808', 'color_bl' => '141414', 'views' => 366],
    ];
}

/**
 * Genres shown in the navbar / filter tabs (in order).
 */
function genre_list(): array
{
    return ['All', 'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Drama', 'Horror', 'Romance', 'Sci-Fi', 'Thriller'];
}


function get_movies(int $limit = 12, int $offset = 0): array
{
    global $conn;
    if ($conn) {
        $cnt = $conn->query("SELECT COUNT(*) c FROM dbProj_movies WHERE is_published = 1");
        if ($cnt) {
            $total = (int)$cnt->fetch_assoc()['c'];
            $stmt = $total > 0
                ? $conn->prepare(MOVIE_SELECT . " WHERE m.is_published = 1
                                  ORDER BY m.created_at DESC, m.movie_id DESC LIMIT ? OFFSET ?")
                : null;
            if ($stmt) {
                $stmt->bind_param('ii', $limit, $offset);
                $stmt->execute();
                return [$stmt->get_result()->fetch_all(MYSQLI_ASSOC), $total];
            }
        }
    }
    // Fallback: placeholder array while DB isn't ready / empty.
    $all = placeholder_movies();
    usort($all, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return [array_slice($all, $offset, $limit), count($all)];
}


function get_featured(int $n = 5): array
{
    [$rows] = get_movies(60, 0);
    $withBg = array_values(array_filter($rows, fn($m) =>
    !empty($m['fanart_bg']) && is_file(__DIR__ . '/../' . $m['fanart_bg'])));
    $pick = count($withBg) >= $n ? $withBg : array_merge($withBg, $rows);

    $seen = [];
    $out = [];
    foreach ($pick as $m) {
        if (isset($seen[$m['id']])) continue;
        $seen[$m['id']] = true;
        $out[] = $m;
        if (count($out) >= $n) break;
    }
    return $out;
}

function search_movies(array $filters, int $limit = 10, int $offset = 0): array
{
    $q        = trim($filters['q'] ?? '');
    $genre    = trim($filters['genre'] ?? '');
    $dateFrom = trim($filters['date_from'] ?? '');
    $dateTo   = trim($filters['date_to'] ?? '');
    $sort     = $filters['sort'] ?? 'date';

    global $conn;
    $order = match ($sort) {
        'rating' => 'm.avg_rating DESC',
        'title'  => 'm.title ASC',
        default  => 'm.created_at DESC, m.movie_id DESC',
    };

    if ($conn) {
        $where = ['m.is_published = 1'];
        $types = '';
        $vals  = [];
        if ($q !== '') {
            $where[] = '(m.title LIKE ? OR m.short_description LIKE ? OR m.synopsis LIKE ?)';
            $like = '%' . $q . '%';
            $types .= 'sss';
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
        }
        if ($genre !== '' && strcasecmp($genre, 'All') !== 0) {
            $where[] = 'EXISTS (SELECT 1 FROM dbProj_movie_categories mc
                                JOIN dbProj_categories c ON c.category_id = mc.category_id
                               WHERE mc.movie_id = m.movie_id AND c.name = ?)';
            $types .= 's';
            $vals[] = $genre;
        }
        if ($dateFrom !== '') {
            $where[] = 'm.release_year >= ?';
            $types .= 'i';
            $vals[] = (int)$dateFrom;
        }
        if ($dateTo   !== '') {
            $where[] = 'm.release_year <= ?';
            $types .= 'i';
            $vals[] = (int)$dateTo;
        }
        $clause = 'WHERE ' . implode(' AND ', $where);

        $cntStmt = $conn->prepare("SELECT COUNT(*) c FROM dbProj_movies m $clause");
        $stmt    = $conn->prepare(MOVIE_SELECT . " $clause ORDER BY $order LIMIT ? OFFSET ?");
        if ($cntStmt && $stmt) {
            if ($types !== '') $cntStmt->bind_param($types, ...$vals);
            $cntStmt->execute();
            $total = (int)$cntStmt->get_result()->fetch_assoc()['c'];

            $stmt->bind_param($types . 'ii', ...array_merge($vals, [$limit, $offset]));
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            return [$rows, $total];
        }
    }

    $rows = placeholder_movies();
    if ($q !== '') {
        $rows = array_filter($rows, fn($m) =>
        stripos($m['title'], $q) !== false || stripos($m['description'], $q) !== false);
    }
    if ($genre !== '' && strcasecmp($genre, 'All') !== 0) {
        $rows = array_filter($rows, fn($m) => strcasecmp($m['genre'], $genre) === 0);
    }
    if ($dateFrom !== '') $rows = array_filter($rows, fn($m) => (int)$m['year'] >= (int)$dateFrom);
    if ($dateTo   !== '') $rows = array_filter($rows, fn($m) => (int)$m['year'] <= (int)$dateTo);
    $rows = array_values($rows);

    usort($rows, match ($sort) {
        'rating' => fn($a, $b) => $b['rating'] <=> $a['rating'],
        'title'  => fn($a, $b) => strcasecmp($a['title'], $b['title']),
        default  => fn($a, $b) => strcmp($b['created_at'], $a['created_at']),
    });

    return [array_slice($rows, $offset, $limit), count($rows)];
}
