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

}
?>
