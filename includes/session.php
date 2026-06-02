<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_username(): string
{
    return $_SESSION['username'] ?? '';
}

// Base-path helpers for sub-folder deployments. app_base_path() detects the URL
// prefix up to /public/, or returns "/" when public/ is the real docroot.
function app_base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $pos = strpos($script, '/public/');
        $base = $pos !== false ? substr($script, 0, $pos + 8) : '/';
    }
    return $base;
}

// Build an in-app URL from a root-relative path (leading slash optional).
function app_url(string $path = ''): string
{
    return app_base_path() . ltrim($path, '/');
}

// Absolute base URL (scheme://host/.../public/) for assets that must always load.
function app_base_href(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . app_base_path();
}
