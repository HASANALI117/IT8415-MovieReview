<?php
// ajax_check_title.php
// AJAX endpoint: checks if a movie title already exists in the database
// called by creator_add_movie.php and creator_edit_movie.php via XMLHttpRequest

session_start();
require_once 'DBconn.php';

// only accept requests from logged-in creators or admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['creator', 'admin'])) {
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

// only respond to GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Bad request']);
    exit;
}

$title    = trim($_GET['title']    ?? '');
$exclude  = (int)($_GET['exclude'] ?? 0);  // movie_id to exclude (for edit page)

if ($title === '') {
    echo json_encode(['exists' => false]);
    exit;
}

// check for a matching title, excluding the current movie on edit
$conn = getConnection();

if ($exclude > 0) {
    $stmt = $conn->prepare(
        "SELECT movie_id FROM dbProj_movies
          WHERE title = ? AND movie_id <> ?
          LIMIT 1"
    );
    $stmt->bind_param("si", $title, $exclude);
} else {
    $stmt = $conn->prepare(
        "SELECT movie_id FROM dbProj_movies WHERE title = ? LIMIT 1"
    );
    $stmt->bind_param("s", $title);
}

$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;

$stmt->close();
$conn->close();

// return JSON result to JavaScript
header('Content-Type: application/json');
echo json_encode(['exists' => $exists]);
?>
