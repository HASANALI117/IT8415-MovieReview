<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Single connection authority for the whole app (least-privilege movie_app user
// from database/04_security.sql). Every subsystem — home/search, admin, creator,
// auth — goes through this one function. Never connect as root.
function getConnection() {
    $conn = mysqli_connect('localhost', 'movie_app', 'MovieApp#2024', 'movie_review');
    if (!$conn) {
        die('Connection failed: ' . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}
?>