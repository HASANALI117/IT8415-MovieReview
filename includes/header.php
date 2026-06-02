<?php
/**
 * Shared page header / layout top.
 *
 * A page sets any of these BEFORE `require`-ing this file:
 *   $page_title   string  <title> text                         (default: site name)
 *   $active_nav   string  home|search|admin|creator|profile    (highlights a nav pill)
 *   $body_class   string  extra <body> class (e.g. "home")
 *   $bg_palette   array   [tl,tr,br,bl] hex for the fixed ultrablur bg
 *                         (default: soft brand palette; home overrides via JS)
 *   $head_extra   string  raw HTML appended into <head> (page-specific CSS/JS)
 *   $container_class string extra class on the <main> wrapper
 *
 * Pair every include of this file with includes/footer.php.
 */

require_once __DIR__ . '/session.php';

$site_name = 'MovieReview';

$page_title      = $page_title      ?? $site_name;
$active_nav      = $active_nav      ?? '';
$body_class      = $body_class      ?? '';
$container_class = $container_class ?? '';
$head_extra      = $head_extra      ?? '';

// Fixed moody brand palette for pages without a slider (deep blue/purple/teal).
$bg_palette = $bg_palette ?? ['23306b', '3b2566', '14424f', '2a1d4d'];

/** Build the 4-corner ultrablur radial-gradient stack (PHP twin of buildUltraBlurGradient in main.js).
 *  Saturated, opaque-at-corner blobs that overlap toward the centre and fill the
 *  whole viewport over the dark base — Plex-style ambient. */
if (!function_exists('ultrablur_gradient')) {
    function ultrablur_gradient(string $tl, string $tr, string $br, string $bl): string
    {
        return
            "radial-gradient(ellipse 130% 130% at 0% 0%,     #{$tl} 0%, transparent 78%)," .
            "radial-gradient(ellipse 130% 130% at 100% 0%,   #{$tr} 0%, transparent 78%)," .
            "radial-gradient(ellipse 130% 130% at 100% 100%, #{$br} 0%, transparent 78%)," .
            "radial-gradient(ellipse 130% 130% at 0% 100%,   #{$bl} 0%, transparent 78%)";
    }
}
$bg_css = ultrablur_gradient($bg_palette[0], $bg_palette[1], $bg_palette[2], $bg_palette[3]);

$logged_in = is_logged_in();
$username  = current_username();
$role      = $_SESSION['role'] ?? '';

/** nav pill helper */
function nav_pill(string $href, string $label, string $key, string $active): string
{
    $cls = 'pill' . ($key === $active ? ' active' : '');
    return '<li><a class="' . $cls . '" href="' . $href . '">' . htmlspecialchars($label) . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="/css/theme.css" rel="stylesheet">
  <?= $head_extra ?>
</head>

<body class="<?= htmlspecialchars($body_class) ?>">

  <!-- full-page ultrablur ambient background -->
  <div class="app-bg" id="appBg" style="background: <?= $bg_css ?>;"></div>
  <div class="app-bg-next" id="appBgNext"></div>

  <!-- ============ NAVBAR (Plex-style) ============ -->
  <nav class="app-nav">
    <div class="nav-inner">

      <!-- left: avatar / brand -->
      <a href="<?= $logged_in ? '/auth/home.php' : '/auth/login.php' ?>" class="nav-avatar" title="<?= $logged_in ? htmlspecialchars($username) : 'Sign in' ?>">
        <?php if ($logged_in): ?>
          <?= strtoupper(substr($username ?: 'U', 0, 1)) ?>
        <?php else: ?>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        <?php endif; ?>
      </a>

      <!-- centre: tabs -->
      <ul class="nav-tabs">
        <li>
          <a class="nav-tab <?= $active_nav === 'search' ? 'active' : '' ?>" href="/search.php" aria-label="Search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <span>Search</span>
          </a>
        </li>
        <li>
          <a class="nav-tab <?= $active_nav === 'home' ? 'active' : '' ?>" href="/index.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
            <span>Home</span>
          </a>
        </li>
        <?php if ($role === 'creator' || $role === 'admin'): ?>
          <li>
            <a class="nav-tab <?= $active_nav === 'creator' ? 'active' : '' ?>" href="/creator/movies.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 9h4M17 9h4M3 15h4M17 15h4"/></svg>
              <span>Creator</span>
            </a>
          </li>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
          <li>
            <a class="nav-tab <?= $active_nav === 'admin' ? 'active' : '' ?>" href="/admin/movies.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></svg>
              <span>Admin</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- right: clock + account -->
      <div class="nav-right">
        <?php if ($logged_in): ?>
          <a href="/auth/logout.php" class="glass-btn glass-btn--sm">Logout</a>
        <?php else: ?>
          <a href="/auth/login.php" class="glass-btn glass-btn--sm glass-btn--accent">Login</a>
          <a href="/auth/register.php" class="glass-btn glass-btn--sm">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="app-container <?= htmlspecialchars($container_class) ?>">
