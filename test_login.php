<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testadmin';
$_SESSION['role'] = 'admin';
echo 'Session set! <br>
<a href="creator_movies.php">Creator Panel</a> | 
<a href="admin_movies.php">Admin Panel</a> | 
<a href="admin_users.php">Users</a> | 
<a href="admin_reports.php">Reports</a> | 
<a href="admin_comments.php">Comments</a>';
?>