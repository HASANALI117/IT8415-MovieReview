<?php

$page_title = 'Loggedout';

include 'Header.php';

echo '<h1>Session has expired</h1>';

$_SESSION = array();
session_destroy();

echo '<br /><h2>Session has timed out - you are now logged out </h2>';
echo '<a href="index.php" class="navlink">Log in</a><br />';

include 'Footer.php';
?>
