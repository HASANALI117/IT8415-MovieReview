<?php
// Auth-panel DB shim. Kept for backward compatibility with existing callers
// (new Database()->getConnection()), but now delegates to the app's single
// connection authority (../DBconn.php, movie_app user) instead of connecting
// as root. Do not add new credentials here.
require_once __DIR__ . '/../DBconn.php';

class Database
{
    protected $dbc = null;

    public function getConnection()
    {
        if ($this->dbc === null) {
            $this->dbc = getConnection();   // shared movie_app connection
            $this->dbc->set_charset('utf8mb4');
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
