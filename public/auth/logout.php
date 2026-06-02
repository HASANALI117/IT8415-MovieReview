<?php

$page_title = 'Logout';

include 'Header.php';

echo '<h1>Logged out</h1>';

if (!isset($_SESSION['user_id']) )
{
     require_once __DIR__ . '/../../src/Auth.php';
     $url = absolute_url('login.php');
     header("Location: $url");
     exit();
}
else{
   $_SESSION = array();
   session_destroy();
   setcookie('PHPSESSID','', time()-3600,'/','',0,0);
}

echo '<br /><h2>you are now logged out </h2>';
echo '<a href="login.php" class="navlink">Log in</a><br />';

include 'Footer.php';
?>
