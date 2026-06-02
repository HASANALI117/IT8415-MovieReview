<?php
// Single source of DB credentials for the whole app.
// Least-privilege application user from database/04_security.sql.
// Loaded by src/Database.php — no other file hard-codes these.
return [
    'host' => 'localhost',
    'user' => 'DUMMY_USER',
    'pass' => 'DUMMY_PASS',
    'name' => 'DUMMY_DB',
];
