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

/* ---------------------------------------------------------------------------
 * Base-path awareness for sub-directory deployments.
 *
 * The app is designed to run with public/ as the document root, so every link
 * uses a root-absolute path (/css, /js, /auth ...). When it is instead served
 * from a sub-folder (e.g. the lab UserDir URL
 *   http://host/~user/IT8415-MovieReview/public/ )
 * those paths break.
 *
 * Fix: emit a single <head> <base> element (see below) pointing at the app root
 * and make every in-app URL RELATIVE so the browser resolves it against that
 * base — natively, for CSS/JS/images/links AND AJAX, on every page regardless of
 * how it was reached. The output buffer just strips the leading slash so the
 * existing root-absolute markup becomes relative; no per-link source edits.
 * $BASE_PATH always ends with "/". When public/ IS the docroot it is just "/".
 * ------------------------------------------------------------------------- */
$BASE_PATH = app_base_path();   // defined in session.php; "/" when public/ is the docroot
$BASE_HREF = app_base_href();   // absolute scheme://host/.../public/ for must-load assets

/*
 * Rewrite root-absolute in-app URLs (href="/x", src="/x", action="/x", url(/x))
 * to include the sub-folder base, so the existing markup works whether public/
 * is the document root (local) or a sub-folder (lab). Runs over the final HTML,
 * so it catches URLs however they were built. External URLs (href="http…) and
 * the explicit absolute CSS/JS below start with "h", so they're left untouched.
 */
if (!function_exists('rewrite_base_urls')) {
  function rewrite_base_urls(string $html): string
  {
    $b = app_base_path();
    if ($b === '/') return $html;          // public/ is the docroot — nothing to do
    return strtr($html, [
      'href="/'   => 'href="' . $b,
      "href='/"   => "href='" . $b,
      'src="/'    => 'src="' . $b,
      "src='/"    => "src='" . $b,
      'action="/' => 'action="' . $b,
      "url('/"    => "url('" . $b,
      'url("/'    => 'url("' . $b,
      'url(/'     => 'url(' . $b,
    ]);
  }
}
ob_start('rewrite_base_urls');   // PHP flushes this through the callback at request end

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
  <!-- Critical inline styles: applied instantly so there's no flash of unstyled
       content (white page / giant SVG icons) before theme.css finishes loading. -->
  <style>
    html,
    body {
      margin: 0;
      min-height: 100%;
      background: #0b0d12;
      color: #eef1f6;
      font-family: "DM Sans", system-ui, -apple-system, sans-serif
    }

    .nav-tab svg,
    .glass-btn svg {
      width: 20px;
      height: 20px
    }
  </style>
  <!-- Base path for JS-built URLs (fetch/ajax). -->
  <script>
    window.BASE = <?= json_encode($BASE_PATH) ?>;
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <!-- Explicit absolute URL so the stylesheet always loads (never depends on
       link rewriting) — this is what prevents the flash of unstyled content. -->
  <link href="<?= htmlspecialchars($BASE_HREF, ENT_QUOTES) ?>css/theme.css" rel="stylesheet">
  <?= $head_extra ?>
</head>

<body class="<?= htmlspecialchars($body_class) ?>">

  <!-- full-page ultrablur ambient background -->
  <div class="app-bg" id="appBg" style="background: <?= $bg_css ?>;"></div>
  <div class="app-bg-next" id="appBgNext"></div>

  <!-- ============ NAVBAR (Plex-style) ============ -->
  <nav class="app-nav">
    <div class="nav-inner">

      <!-- left: avatar — only shown for signed-in users -->
      <?php if ($logged_in): ?>
        <a href="/index.php" class="nav-avatar" title="<?= htmlspecialchars($username) ?>">
          <?= strtoupper(substr($username ?: 'U', 0, 1)) ?>
        </a>
      <?php endif; ?>

      <!-- centre: tabs -->
      <ul class="nav-tabs">
        <li>
          <a class="nav-tab <?= $active_nav === 'home' ? 'active' : '' ?>" href="/index.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 11l9-8 9 8" />
              <path d="M5 10v10h14V10" />
            </svg>
            <span>Home</span>
          </a>
        </li>
        <li>
          <a class="nav-tab <?= $active_nav === 'browse' ? 'active' : '' ?>" href="/search.php" aria-label="Browse">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7" />
              <path d="m21 21-4.3-4.3" />
            </svg>
            <span>Browse</span>
          </a>
        </li>
        <?php if ($role === 'creator' || $role === 'admin'): ?>
          <li>
            <a class="nav-tab <?= $active_nav === 'creator' ? 'active' : '' ?>" href="/creator/movies.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="16" rx="2" />
                <path d="M7 4v16M17 4v16M3 9h4M17 9h4M3 15h4M17 15h4" />
              </svg>
              <span>Creator</span>
            </a>
          </li>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
          <li>
            <a class="nav-tab <?= $active_nav === 'admin' ? 'active' : '' ?>" href="/admin/movies.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" />
              </svg>
              <span>Admin</span>
            </a>
          </li>
        <?php endif; ?>
        <!-- auth actions — now part of the tabs row (was the separate nav-right) -->
        <?php if ($logged_in): ?>
          <li>
            <a class="nav-tab" href="/auth/logout.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="M16 17l5-5-5-5" />
                <path d="M21 12H9" />
              </svg>
              <span>Logout</span>
            </a>
          </li>
        <?php else: ?>
          <li>
            <a class="nav-tab" href="/auth/login.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                <path d="M10 17l5-5-5-5" />
                <path d="M15 12H3" />
              </svg>
              <span>Login</span>
            </a>
          </li>
          <li>
            <a class="nav-tab" href="/auth/register.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M19 8v6M22 11h-6" />
              </svg>
              <span>Register</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <main class="app-container <?= htmlspecialchars($container_class) ?>">