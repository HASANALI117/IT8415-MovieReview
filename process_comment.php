<?php
session_start();
require_once 'db_connect.php';

$movie_id = (int)$_POST['movie_id'];
$comment = $_POST['comment'];
$rating = (int)$_POST['rating'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to user 1 for demo

// 1. Rate movie via Stored Procedure 
$stmt_rate = $conn->prepare("CALL p_rate_movie(?, ?, ?)");
$stmt_rate->bind_param("iii", $movie_id, $user_id, $rating);
$stmt_rate->execute();
$stmt_rate->close();

// 2. Insert Comment 
$stmt = $conn->prepare("INSERT INTO dbProj_comments (movie_id, user_id, body) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $movie_id, $user_id, $comment);

if($stmt->execute()) {
    echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error']);
}
$stmt->close();
?>