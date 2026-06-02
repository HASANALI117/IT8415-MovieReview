<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Single DB connection authority. Credentials come from config/db.php.
// Returns a fresh mysqli connection each call; callers close their own.
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

// Compatibility wrapper: `new Database()->getConnection()` delegates to getConnection() above.
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
