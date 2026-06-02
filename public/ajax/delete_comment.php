<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

$conn = getConnection();
$id = (int)$_POST['id'];

// Soft delete using is_removed
$stmt = $conn->prepare("UPDATE dbProj_comments SET is_removed = 1, removed_at = CURRENT_TIMESTAMP WHERE comment_id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()) {
    echo "success";
}
?>
