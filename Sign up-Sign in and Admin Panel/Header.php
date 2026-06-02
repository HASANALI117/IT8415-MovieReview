<?php
session_start();

$_DEBUG = true;

if($_DEBUG)
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

$inactive = 600;

if (isset($_SESSION['timeout'])) {

    $session_life = time() - $_SESSION['timeout'];

    if ($session_life > $inactive) {
        session_destroy();
        header("Location: LoggedOut.php");
    }
}

$_SESSION['timeout'] = time();
?>
<html>
    <head>
       <title>Movie Review</title>
        <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
         <div id="header">
                <h1>Welcome to Movie Review</h1>
                <h2>Carpe Diem</h2>
        </div>

                <div id="navigation">
                <ul>
                        <li><a href="index.php">Main Page</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="view_users.php">View Users</a></li>
                </ul>
        </div>

        <div id="content" >
