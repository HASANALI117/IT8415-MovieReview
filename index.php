<?php
require_once 'includes/session.php';
require_once 'includes/data.php';

$logged_in = is_logged_in();
$username   = current_username();

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 12;
$offset = ($page - 1) * $per;

[$movies, $total] = get_movies($per, $offset);
$total_pages = (int)ceil($total / $per);

// Hero slider: prefer movies that have a real downloaded fanart background.
$featured = get_featured(5);

$genres   = genre_list();
$site_name = 'MovieReview';

/** Build a movie card. Poster falls back to an ultrablur gradient block if missing. */
function movie_card(array $m): string
{
    $id     = (int)$m['id'];
    $title  = htmlspecialchars($m['title']);
    $genre  = htmlspecialchars($m['genre']);
    $poster = htmlspecialchars($m['poster']);
    $rating = number_format((float)$m['rating'], 1);
    $year   = (int)$m['year'];
    $grad   = "linear-gradient(135deg,#{$m['color_tl']},#{$m['color_br']})";

    return <<<HTML
    <div class="col" data-genre="{$genre}">
      <a href="movie-detail.php?id={$id}" class="movie-card">
        <div class="poster-wrap" style="background:{$grad}">
          <img src="{$poster}" alt="{$title}" loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="poster-fallback" style="display:none"><span>{$title}</span></div>
          <span class="card-rating">&#9733; {$rating}</span>
        </div>
        <div class="card-meta">
          <div class="card-title">{$title}</div>
          <div class="card-sub">{$year} &middot; {$genre}</div>
        </div>
      </a>
    </div>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $site_name ?> — Discover & Review Movies</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="app-nav">
  <div class="nav-inner">
    <a href="index.php" class="brand"><?= $site_name ?></a>

    <ul class="nav-pills-row" id="genreTabs">
      <?php foreach ($genres as $g): ?>
        <li>
          <button class="pill <?= $g === 'All' ? 'active' : '' ?>" data-genre="<?= htmlspecialchars($g) ?>">
            <?= htmlspecialchars($g) ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="nav-right">
      <button class="icon-btn" id="searchToggle" aria-label="Search">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      </button>
      <?php if ($logged_in): ?>
        <div class="user-chip">
          <img src="assets/avatars/default.png" width="32" height="32" alt=""
               onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'avatar-fallback',textContent:'<?= strtoupper(substr($username ?: 'U',0,1)) ?>'}));">
          <span class="fw-500 small"><?= htmlspecialchars($username) ?></span>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-primary btn-sm">Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- inline search bar (toggled) -->
  <div class="nav-search" id="navSearch">
    <form action="search.php" method="get" class="nav-search-form" autocomplete="off">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="q" id="liveSearch" placeholder="Search movies…" aria-label="Search movies">
      <div class="search-spinner" id="searchSpinner"></div>
    </form>
    <div class="search-dropdown" id="searchDropdown"></div>
  </div>
</nav>

<!-- ============ HERO SLIDER ============ -->
<section class="hero-section" id="hero">
  <div class="hero-track" id="heroTrack">
    <?php foreach ($featured as $m): ?>
      <div class="hero-slide"
           data-id="<?= (int)$m['id'] ?>"
           data-tl="<?= htmlspecialchars($m['color_tl']) ?>"
           data-tr="<?= htmlspecialchars($m['color_tr']) ?>"
           data-br="<?= htmlspecialchars($m['color_br']) ?>"
           data-bl="<?= htmlspecialchars($m['color_bl']) ?>">
        <div class="hero-bg-img" style="background-image:url('<?= htmlspecialchars($m['fanart_bg']) ?>')"></div>
        <div class="hero-gradient-overlay"></div>
        <div class="hero-content">
          <img src="<?= htmlspecialchars($m['fanart_logo']) ?>" class="hero-logo" alt="<?= htmlspecialchars($m['title']) ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
          <h1 class="hero-title-text" style="display:none"><?= htmlspecialchars($m['title']) ?></h1>
          <p class="hero-desc"><?= htmlspecialchars($m['description']) ?></p>
          <a href="movie-detail.php?id=<?= (int)$m['id'] ?>" class="btn btn-light btn-lg hero-btn">View Review</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ultrablur ambient overlays (driven by JS) -->
  <div class="hero-bg-blur"></div>
  <div class="hero-bg-blur-next"></div>

  <button class="hero-arrow prev" id="heroPrev" aria-label="Previous">&#8249;</button>
  <button class="hero-arrow next" id="heroNext" aria-label="Next">&#8250;</button>
  <div class="hero-dots" id="heroDots"></div>
</section>

<!-- ============ GENRE-FILTERED GRID ============ -->
<main class="container-xxl py-4">
  <h2 class="section-label" id="sectionLabel">Trending Now</h2>

  <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3" id="movieGrid">
    <?php foreach ($movies as $m) echo movie_card($m); ?>
  </div>

  <div class="empty-genre" id="emptyGenre" style="display:none">
    No movies in this genre yet.
  </div>

  <?php if ($total_pages > 1): ?>
    <nav class="pagination-row" aria-label="Pagination">
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?page=<?= $p ?>" class="page-link-pill <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</main>

<footer class="app-footer">
  <div class="container-xxl">
    <span><?= $site_name ?></span> — IT8415 Database Programming 2 · Group Project
  </div>
</footer>

<script src="js/main.js"></script>
<script src="js/search.js"></script>
</body>
</html>
