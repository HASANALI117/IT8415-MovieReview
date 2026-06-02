<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../src/Movie.php';

$genres = Movie::genreList();

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

  // Text-based title beneath the poster (readable + accessible).
  $titleBlock = "<div class=\"card-title\">{$title}</div>";

  return <<<HTML
    <div class="col">
      <a href="/movie/detail.php?id={$id}" class="movie-card">
        <div class="poster-wrap" style="background:{$grad}">
          <img src="/{$poster}" alt="{$title}" loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="poster-fallback" style="display:none"><span>{$title}</span></div>
          <span class="card-rating">&#9733; {$rating}</span>
        </div>
        <div class="card-meta">
          {$titleBlock}
          <div class="card-sub">{$year} &middot; {$g}</div>
        </div>
      </a>
    </div>
    HTML;
}

$page_title = 'Search — MovieReview';
$active_nav = 'search';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-heading">
  <h1>Browse movies</h1>
  <p>Pick a genre, or filter the catalogue by title, year and rating.</p>
</div>

<!-- Genre browser (one-click, preserves the other filters) -->
<ul class="genre-bar">
  <?php foreach ($genres as $g): ?>
    <?php
    $isAll  = ($g === 'All');
    $params = array_filter(
      ['q' => $q, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => $sort],
      fn($v) => $v !== ''
    );
    if (!$isAll) $params['genre'] = $g;
    $href   = '/search.php' . ($params ? '?' . http_build_query($params) : '');
    $active = $isAll ? ($genre === '') : (strcasecmp($g, $genre) === 0);
    ?>
    <li><a class="pill <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($g) ?></a></li>
  <?php endforeach; ?>
</ul>

<!-- Filter bar (genre carried via the pills above) -->
<form class="filter-bar" method="get" action="/search.php">
  <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
  <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Title or keyword…" class="form-control">
  <input type="number" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="From yr" class="form-control" min="1900" max="2100">
  <input type="number" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" placeholder="To yr" class="form-control" min="1900" max="2100">
  <select name="sort" class="form-select">
    <option value="date" <?= $sort === 'date'   ? 'selected' : '' ?>>Newest</option>
    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
    <option value="title" <?= $sort === 'title'  ? 'selected' : '' ?>>A–Z</option>
  </select>
  <button class="glass-btn glass-btn--accent" type="submit">Search</button>
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
    <h3>No matches found</h3>
    <p>Try a shorter keyword, clear filters, or <a href="/search.php">browse all movies</a>.</p>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>