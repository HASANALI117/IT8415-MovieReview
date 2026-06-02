<?php

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../Movie.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

[$results] = Movie::searchPublic(['q' => $q], 6, 0);

$slim = array_map(fn($m) => [
    'id'     => $m['id'],
    'title'  => $m['title'],
    'year'   => $m['year'],
    'poster' => $m['poster'],
    'rating' => $m['rating'],
], $results);

echo json_encode($slim);
