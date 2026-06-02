<?php
// Connection settings 
define('DB_HOST', '127.0.0.1');   
define('DB_PORT', 3306);          
define('DB_USER', 'movie_app');       
define('DB_PASS', 'DUMMY_PASS');   
define('DB_NAME', 'movie_review');
define('DB_CHARSET', 'utf8mb4');  

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    die("Connection failed. Please check error logs.");
} else {
    $conn->set_charset(DB_CHARSET);
}
?>