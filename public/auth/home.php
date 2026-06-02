<?php
include 'Header.php';

echo '<br />';
echo '<h1>Welcome to the home page</h1>';

$Username = $_SESSION['username'] ?? '';
$Role = $_SESSION['role'] ?? '';

echo "<br /><h3>Hello $Username</h3>";
echo "<p>Your role: $Role</p>";

if ($Role == 'admin') {
    echo '<p><a href="view_users.php" class="navlink">Manage Users</a></p>';
}

echo '</br><a href="logout.php" class="navlink">Logout</a><br />';

include 'Footer.php';
?>
