<?php
require_once 'DBconn.php';

class Movie {

    // member vars to match table columns
    private $movie_id;
    private $title;
    private $short_description;
    private $synopsis;
    private $release_year;
    private $duration_min;
    private $image_url;
    private $media_url;
    private $download_url;
    private $created_by;
    private $is_published;

    // getters and setters
    public function getMovieId()         { return $this->movie_id; }
    public function getTitle()           { return $this->title; }
    public function getShortDesc()       { return $this->short_description; }
    public function getSynopsis()        { return $this->synopsis; }
    public function getReleaseYear()     { return $this->release_year; }
    public function getDurationMin()     { return $this->duration_min; }
    public function getImageUrl()        { return $this->image_url; }
    public function getMediaUrl()        { return $this->media_url; }
    public function getDownloadUrl()     { return $this->download_url; }
    public function getCreatedBy()       { return $this->created_by; }
    public function getIsPublished()     { return $this->is_published; }

    public function setTitle($v)         { $this->title             = trim($v); }
    public function setShortDesc($v)     { $this->short_description = trim($v); }
    public function setSynopsis($v)      { $this->synopsis          = trim($v); }
    public function setReleaseYear($v)   { $this->release_year      = (int)$v; }
    public function setDurationMin($v)   { $this->duration_min      = (int)$v; }
    public function setImageUrl($v)      { $this->image_url         = $v; }
    public function setMediaUrl($v)      { $this->media_url         = $v; }
    public function setDownloadUrl($v)   { $this->download_url      = $v; }
    public function setCreatedBy($v)     { $this->created_by        = (int)$v; }
    public function setIsPublished($v)   { $this->is_published      = (int)$v; }

    // validation checks
    public function isValid(&$errors) {
        $errors = [];
        if (empty($this->title))
            $errors[] = 'Title is required.';
        if (empty($this->short_description))
            $errors[] = 'Short description is required.';
        if (empty($this->image_url))
            $errors[] = 'A poster image is required.';
        if ($this->release_year && ($this->release_year < 1888 || $this->release_year > 2100))
            $errors[] = 'Release year must be between 1888 and 2100.';
        return empty($errors);
    }

    // inserting, save new movie and return new movie_id or false
    public function addMovie() {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO dbProj_movies
                (title, short_description, synopsis, release_year,
                 duration_min, image_url, media_url, download_url, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssiisssi",
            $this->title,
            $this->short_description,
            $this->synopsis,
            $this->release_year,
            $this->duration_min,
            $this->image_url,
            $this->media_url,
            $this->download_url,
            $this->created_by
        );

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return $newId;
        }

        $stmt->close();
        $conn->close();
        return false;
    }

    // selecting and loading one movie by id into object
    public function initWithId($movie_id) {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "SELECT movie_id, title, short_description, synopsis,
                    release_year, duration_min, image_url, media_url,
                    download_url, created_by, is_published
               FROM dbProj_movies
              WHERE movie_id = ?"
        );
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $stmt->bind_result(
            $this->movie_id, $this->title, $this->short_description,
            $this->synopsis, $this->release_year, $this->duration_min,
            $this->image_url, $this->media_url, $this->download_url,
            $this->created_by, $this->is_published
        );

        $found = $stmt->fetch();
        $stmt->close();
        $conn->close();
        return $found;
    }

    // updating, save any edits for unpublished movies only
    public function updateMovie() {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "UPDATE dbProj_movies
                SET title             = ?,
                    short_description = ?,
                    synopsis          = ?,
                    release_year      = ?,
                    duration_min      = ?,
                    image_url         = ?,
                    media_url         = ?,
                    download_url      = ?
              WHERE movie_id    = ?
                AND created_by  = ?
                AND is_published = 0"
        );

        $stmt->bind_param(
            "sssiiissii",
            $this->title,
            $this->short_description,
            $this->synopsis,
            $this->release_year,
            $this->duration_min,
            $this->image_url,
            $this->media_url,
            $this->download_url,
            $this->movie_id,
            $this->created_by
        );

        $ok       = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();
        return ($ok && $affected > 0);
    }

    // selecting all movies belonging to a specific creator
    public static function getByCreator($creator_id) {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "SELECT movie_id, title, short_description, release_year,
                    image_url, is_published, avg_rating, view_count,
                    published_at, created_at
               FROM dbProj_movies
              WHERE created_by = ?
              ORDER BY is_published ASC, created_at DESC"
        );
        $stmt->bind_param("i", $creator_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        $stmt->close();
        $conn->close();
        return $movies;
    }

    // selecting all published and unpublished movies for admin
    public static function getAllMovies() {
        $conn = getConnection();

        $result = $conn->query(
            "SELECT m.movie_id, m.title, m.short_description, m.release_year,
                    m.image_url, m.is_published, m.avg_rating, m.view_count,
                    m.created_at, u.username AS creator
               FROM dbProj_movies m
               LEFT JOIN dbProj_users u ON u.user_id = m.created_by
              ORDER BY m.created_at DESC"
        );

        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        $conn->close();
        return $movies;
    }

    // deleting movies (only admins/ owners can delete)
    public static function deleteMovie($movie_id) {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "DELETE FROM dbProj_movies WHERE movie_id = ?"
        );
        $stmt->bind_param("i", $movie_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    // call publish/unpublish stored procedure
    public static function setPublished($movie_id, $publish) {
        $conn = getConnection();

        $stmt = $conn->prepare("CALL p_set_movie_published(?, ?)");
        $stmt->bind_param("ii", $movie_id, $publish);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    // attach and/or replace categories for a movie
    public static function setCategories($movie_id, array $category_ids) {
        $conn = getConnection();

        // remove old links first
        $del = $conn->prepare(
            "DELETE FROM dbProj_movie_categories WHERE movie_id = ?"
        );
        $del->bind_param("i", $movie_id);
        $del->execute();
        $del->close();

        // insert new links
        $ins = $conn->prepare(
            "INSERT IGNORE INTO dbProj_movie_categories (movie_id, category_id) VALUES (?, ?)"
        );
        foreach ($category_ids as $cat_id) {
            $cat_id = (int)$cat_id;
            $ins->bind_param("ii", $movie_id, $cat_id);
            $ins->execute();
        }
        $ins->close();
        $conn->close();
    }

    // get category ids for a movie
    public static function getCategoryIds($movie_id) {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "SELECT category_id FROM dbProj_movie_categories WHERE movie_id = ?"
        );
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['category_id'];
        }

        $stmt->close();
        $conn->close();
        return $ids;
    }

    /* =========================================================================
       Public site read API (home page + search).
       These map the normalized schema onto the flat key shape the front-end
       templates use: id, title, description, rating, year, genre, poster,
       fanart_bg, fanart_logo, color_*, views, created_at.
       ========================================================================= */

    // SELECT projection shared by all public listing queries. `m` = dbProj_movies.
    private static function publicSelect(): string
    {
        return "
            SELECT m.movie_id                                            AS id,
                   m.title                                               AS title,
                   COALESCE(NULLIF(m.short_description,''), m.synopsis, '') AS description,
                   m.avg_rating                                          AS rating,
                   m.release_year                                        AS year,
                   m.image_url                                           AS poster,
                   m.fanart_bg                                           AS fanart_bg,
                   m.fanart_logo                                         AS fanart_logo,
                   m.color_tl, m.color_tr, m.color_br, m.color_bl,
                   m.view_count                                          AS views,
                   m.created_at                                          AS created_at,
                   COALESCE((
                       SELECT c.name
                         FROM dbProj_movie_categories mc
                         JOIN dbProj_categories c ON c.category_id = mc.category_id
                        WHERE mc.movie_id = m.movie_id
                        ORDER BY c.category_id
                        LIMIT 1
                   ), '')                                                AS genre
              FROM dbProj_movies m
        ";
    }

    // Genre filter labels for the navbar / tabs. 'All' first, then category names.
    public static function genreList(): array
    {
        $genres = ['All'];
        $conn = getConnection();
        if ($res = $conn->query("SELECT name FROM dbProj_categories ORDER BY name")) {
            while ($row = $res->fetch_assoc()) $genres[] = $row['name'];
        }
        $conn->close();
        return $genres;
    }

    // One page of published movies, newest first. Returns [rows, totalCount].
    public static function getPublished(int $limit = 12, int $offset = 0): array
    {
        $conn = getConnection();
        $total = (int)($conn->query(
            "SELECT COUNT(*) c FROM dbProj_movies WHERE is_published = 1"
        )->fetch_assoc()['c'] ?? 0);

        $stmt = $conn->prepare(
            self::publicSelect() .
            " WHERE m.is_published = 1
              ORDER BY m.created_at DESC, m.movie_id DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return [$rows, $total];
    }

    // Hero slider set: prefer published movies that actually have a downloaded
    // fanart background, falling back to newest published.
    public static function getFeatured(int $n = 5): array
    {
        [$rows] = self::getPublished(60, 0);
        $withBg = array_values(array_filter($rows, fn($m) =>
            !empty($m['fanart_bg']) && is_file(__DIR__ . '/' . $m['fanart_bg'])));
        $pick = count($withBg) >= $n ? $withBg : array_merge($withBg, $rows);

        $seen = [];
        $out  = [];
        foreach ($pick as $m) {
            if (isset($seen[$m['id']])) continue;
            $seen[$m['id']] = true;
            $out[] = $m;
            if (count($out) >= $n) break;
        }
        return $out;
    }

    /**
     * Search + filter published movies. $filters: q, genre, date_from, date_to, sort.
     * Dynamic WHERE with bound params — user input never touches the SQL string.
     * Returns [rows, totalMatched].
     */
    public static function searchPublic(array $filters, int $limit = 10, int $offset = 0): array
    {
        $q        = trim($filters['q'] ?? '');
        $genre    = trim($filters['genre'] ?? '');
        $dateFrom = trim($filters['date_from'] ?? '');
        $dateTo   = trim($filters['date_to'] ?? '');
        $sort     = $filters['sort'] ?? 'date';

        $order = match ($sort) {
            'rating' => 'm.avg_rating DESC',
            'title'  => 'm.title ASC',
            default  => 'm.created_at DESC, m.movie_id DESC',
        };

        $where = ['m.is_published = 1'];
        $types = '';
        $vals  = [];
        if ($q !== '') {
            $where[] = '(m.title LIKE ? OR m.short_description LIKE ? OR m.synopsis LIKE ?)';
            $like = '%' . $q . '%';
            $types .= 'sss';
            $vals[] = $like; $vals[] = $like; $vals[] = $like;
        }
        if ($genre !== '' && strcasecmp($genre, 'All') !== 0) {
            $where[] = 'EXISTS (SELECT 1 FROM dbProj_movie_categories mc
                                JOIN dbProj_categories c ON c.category_id = mc.category_id
                               WHERE mc.movie_id = m.movie_id AND c.name = ?)';
            $types .= 's';
            $vals[] = $genre;
        }
        if ($dateFrom !== '') { $where[] = 'm.release_year >= ?'; $types .= 'i'; $vals[] = (int)$dateFrom; }
        if ($dateTo   !== '') { $where[] = 'm.release_year <= ?'; $types .= 'i'; $vals[] = (int)$dateTo; }
        $clause = 'WHERE ' . implode(' AND ', $where);

        $conn = getConnection();

        $cntStmt = $conn->prepare("SELECT COUNT(*) c FROM dbProj_movies m $clause");
        if ($types !== '') $cntStmt->bind_param($types, ...$vals);
        $cntStmt->execute();
        $total = (int)$cntStmt->get_result()->fetch_assoc()['c'];
        $cntStmt->close();

        $stmt = $conn->prepare(self::publicSelect() . " $clause ORDER BY $order LIMIT ? OFFSET ?");
        $stmt->bind_param($types . 'ii', ...array_merge($vals, [$limit, $offset]));
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return [$rows, $total];
    }

}
?>
