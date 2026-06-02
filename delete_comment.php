<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

require_once 'db_connect.php';
$id = (int)$_POST['id'];

// Soft delete using is_removed 
$stmt = $conn->prepare("UPDATE dbProj_comments SET is_removed = 1, removed_at = CURRENT_TIMESTAMP WHERE comment_id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()) {
    echo "success";
}
?>