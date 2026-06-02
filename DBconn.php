<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function getConnection() {
$conn = mysqli_connect(
    'localhost',
    'movie_app',
    'MovieApp#2024',
    'movie_review'
);
    if (!$conn) {
        die('Connection failed: ' . mysqli_connect_error());
    }
    return $conn;
}
?>