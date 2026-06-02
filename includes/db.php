<?php

$host = 'localhost';
$db   = 'movie_review';
$user = 'movie_app';
$pass = 'MovieApp#2024';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    $conn = null;
}
