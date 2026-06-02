<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Single connection authority for the whole app. Every subsystem — home/search,
 * admin, creator, auth — goes through getConnection(). Credentials live in
 * config/db.php (movie_app, least-privilege). Never connect as root.
 *
 * Returns a fresh mysqli connection on each call; callers that open their own
 * connection are responsible for closing it (the OOP Data Objects do).
 */
function getConnection()
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/db.php';
    }
    $conn = mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    if (!$conn) {
        die('Connection failed: ' . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

/**
 * Auth-panel compatibility wrapper. Existing callers do
 * `new Database()->getConnection()`; this now delegates to the one
 * connection authority above instead of connecting as root.
 */
class Database
{
    protected $dbc = null;

    public function getConnection()
    {
        if ($this->dbc === null) {
            $this->dbc = getConnection();   // shared movie_app connection
        }
        return $this->dbc;
    }

    public function closeDB()
    {
        if ($this->dbc) {
            mysqli_close($this->dbc);
            $this->dbc = null;
        }
    }
}
