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

/* ---------------------------------------------------------------------------
 * Base-path helpers for sub-directory deployments.
 *
 * The app is built to run with public/ as the document root (all links are
 * root-absolute: /css, /auth, /index.php ...). When it is instead served from
 * a sub-folder (e.g. the lab UserDir URL .../IT8415-MovieReview/public/), those
 * paths must be prefixed. app_base_path() detects that prefix from the request;
 * it returns "/" when public/ is the real docroot, so local setups are unchanged.
 * ------------------------------------------------------------------------- */
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

/** Build an in-app URL from a root-relative path (leading slash optional). */
function app_url(string $path = ''): string
{
    return app_base_path() . ltrim($path, '/');
}

/** Absolute base URL (scheme://host/.../public/) — used for assets that must
 *  load no matter what (CSS/JS), so they never depend on link rewriting. */
function app_base_href(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . app_base_path();
}
