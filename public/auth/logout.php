<?php
// Destroy the session and return to the login page. Done before any output
// so the redirect header is valid.
require_once __DIR__ . '/../../includes/session.php';

$_SESSION = array();
session_destroy();
setcookie('PHPSESSID', '', time() - 3600, '/', '', 0, 0);

header('Location: /auth/login.php');
exit();
