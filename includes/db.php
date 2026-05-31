<?php


// Connection settings 
define('DB_HOST', '127.0.0.1');   // localhost; 127.0.0.1 avoids socket issues on Windows
define('DB_PORT', 3306);          // default MySQL/MariaDB port in XAMPP
define('DB_USER', 'movie_app');       // least-privilege app user (see 04_security.sql)
define('DB_PASS', 'DUMMY_PASS');   // NOT root - app can't drop/alter tables
define('DB_NAME', 'movie_review');// must match CREATE DATABASE in 01_create_tables.sql
define('DB_CHARSET', 'utf8mb4');  // matches the schema's utf8mb4 tables

// Connect 
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_errno) {
    error_log('DB connection failed: ' . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset(DB_CHARSET);
}
