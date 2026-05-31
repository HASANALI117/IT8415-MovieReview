<?php

// Prepared statements (safe from SQL injection - user input is bound, never glued into SQL)

require_once __DIR__ . '/db.php';   // gives us $conn


// get one movie by id
function q_get_movie(mysqli $conn, int $movie_id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM dbProj_movies WHERE movie_id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}


// search published movies by title keyword
function q_search_movies(mysqli $conn, string $keyword): array
{
    $like = '%' . $keyword . '%';
    $stmt = $conn->prepare(
        "SELECT movie_id, title, avg_rating, view_count
           FROM dbProj_movies
          WHERE is_published = 1 AND title LIKE ?
          ORDER BY avg_rating DESC"
    );
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}


// add a comment, returns new comment id
function q_add_comment(mysqli $conn, int $movie_id, int $user_id, string $body): int
{
    $stmt = $conn->prepare("INSERT INTO dbProj_comments (movie_id, user_id, body) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $movie_id, $user_id, $body);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}


// rate a movie by calling the stored procedure
function q_rate_movie(mysqli $conn, int $movie_id, int $user_id, int $stars): void
{
    $stmt = $conn->prepare("CALL p_rate_movie(?, ?, ?)");
    $stmt->bind_param("iii", $movie_id, $user_id, $stars);
    $stmt->execute();
    $stmt->close();
}
