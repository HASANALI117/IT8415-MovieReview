<?php
require_once 'includes/session.php';
require_once 'Movie.php';

$logged_in = is_logged_in();
$username   = current_username();
$site_name  = 'MovieReview';
$genres     = Movie::genreList();

// Filters from GET
$q        = trim($_GET['q'] ?? '');
$genre    = trim($_GET['genre'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['rating', 'date', 'title'], true) ? $_GET['sort'] : 'date';

$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 10;
$offset = ($page - 1) * $per;

[$results, $total] = Movie::searchPublic(
    ['q' => $q, 'genre' => $genre, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => $sort],
    $per,
    $offset
);
$total_pages = (int)ceil($total / $per);

// Preserve filters across pagination links
$base = $_GET;
unset($base['page']);
$qs = fn(int $p) => http_build_query(array_merge($base, ['page' => $p]));

function s_card(array $m): string
{
    $id     = (int)$m['id'];
    $title  = htmlspecialchars($m['title']);
    $g      = htmlspecialchars($m['genre']);
    $poster = htmlspecialchars($m['poster']);
    $rating = number_format((float)$m['rating'], 1);
    $year   = (int)$m['year'];
    $grad   = "linear-gradient(135deg,#{$m['color_tl']},#{$m['color_br']})";
    return <<<HTML
    <div class="col">
      <a href="movie-detail.php?id={$id}" class="movie-card">
        <div class="poster-wrap" style="background:{$grad}">
          <img src="{$poster}" alt="{$title}" loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="poster-fallback" style="display:none"><span>{$title}</span></div>
          <span class="card-rating">&#9733; {$rating}</span>
        </div>
        <div class="card-meta">
          <div class="card-title">{$title}</div>
          <div class="card-sub">{$year} &middot; {$g}</div>
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
  <title>Search — <?= $site_name ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

<nav class="app-nav">
  <div class="nav-inner">
    <a href="index.php" class="brand"><?= $site_name ?></a>
    <ul class="nav-pills-row">
      <?php foreach ($genres as $g): ?>
        <li><a class="pill <?= strcasecmp($g, $genre ?: 'All') === 0 ? 'active' : '' ?>"
               href="search.php?<?= http_build_query(['q' => $q, 'genre' => $g === 'All' ? '' : $g, 'sort' => $sort]) ?>">
          <?= htmlspecialchars($g) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="nav-right">
      <button class="icon-btn" id="searchToggle" aria-label="Search">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      </button>
      <?php if ($logged_in): ?>
        <div class="user-chip">
          <div class="avatar-fallback"><?= strtoupper(substr($username ?: 'U', 0, 1)) ?></div>
          <span class="fw-500 small"><?= htmlspecialchars($username) ?></span>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-primary btn-sm">Login</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="nav-search" id="navSearch">
    <form action="search.php" method="get" class="nav-search-form" autocomplete="off">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="q" id="liveSearch" value="<?= htmlspecialchars($q) ?>" placeholder="Search movies…" aria-label="Search movies">
      <div class="search-spinner" id="searchSpinner"></div>
    </form>
    <div class="search-dropdown" id="searchDropdown"></div>
  </div>
</nav>

<main class="container-xxl py-4 search-page">

  <!-- Filter bar -->
  <form class="filter-bar" method="get" action="search.php">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Title or keyword…" class="form-control">
    <select name="genre" class="form-select">
      <option value="">All genres</option>
      <?php foreach (array_slice($genres, 1) as $g): ?>
        <option value="<?= htmlspecialchars($g) ?>" <?= strcasecmp($g, $genre) === 0 ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="From yr" class="form-control" min="1900" max="2100">
    <input type="number" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" placeholder="To yr" class="form-control" min="1900" max="2100">
    <select name="sort" class="form-select">
      <option value="date"   <?= $sort === 'date'   ? 'selected' : '' ?>>Newest</option>
      <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
      <option value="title"  <?= $sort === 'title'  ? 'selected' : '' ?>>A–Z</option>
    </select>
    <button class="btn btn-primary" type="submit">Search</button>
  </form>

  <h2 class="section-label">
    <?php if ($q !== ''): ?>
      Search results for &ldquo;<?= htmlspecialchars($q) ?>&rdquo; — <?= $total ?> result<?= $total === 1 ? '' : 's' ?> found
    <?php else: ?>
      <?= $total ?> movie<?= $total === 1 ? '' : 's' ?>
    <?php endif; ?>
  </h2>

  <?php if ($total === 0): ?>
    <div class="empty-state">
      <div class="empty-emoji">🎬</div>
      <h3>No matches found</h3>
      <p>Try a shorter keyword, clear filters, or <a href="search.php">browse all movies</a>.</p>
    </div>
  <?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3">
      <?php foreach ($results as $m) echo s_card($m); ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <nav class="pagination-row" aria-label="Pagination">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <a href="?<?= $qs($p) ?>" class="page-link-pill <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</main>

<footer class="app-footer">
  <div class="container-xxl"><span><?= $site_name ?></span> — IT8415 Database Programming 2 · Group Project</div>
</footer>

<script src="js/search.js"></script>
</body>
</html>
