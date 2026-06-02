<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../src/Movie.php';

// Featured titles for the cinematic hero + poster strip.
// (Browse-by-genre + the full grid live on search.php, reached via the nav search tab.)
$featured = Movie::getFeatured(5);

// Home rails below the hero — each is one page of published movies under a different sort.
$RAIL_SIZE = 12;
[$trending] = Movie::searchPublic(['sort' => 'views'],  $RAIL_SIZE, 0);
[$topRated] = Movie::searchPublic(['sort' => 'rating'], $RAIL_SIZE, 0);
[$recent]   = Movie::searchPublic(['sort' => 'date'],   $RAIL_SIZE, 0);

$rails = [
  'Trending Now'   => $trending,
  'Top Rated'      => $topRated,
  'Recently Added' => $recent,
];

/** Frameless, logo-aware poster card for the home rails. */
function movie_card(array $m): string
{
  $id     = (int)$m['id'];
  $title  = htmlspecialchars($m['title']);
  $genre  = htmlspecialchars($m['genre']);
  $poster = htmlspecialchars($m['poster']);
  $logo   = htmlspecialchars($m['fanart_logo'] ?? '');
  $rating = number_format((float)$m['rating'], 1);
  $year   = (int)$m['year'];
  $grad   = "linear-gradient(135deg,#{$m['color_tl']},#{$m['color_br']})";

  $titleBlock = $logo !== ''
    ? "<img class=\"card-logo\" src=\"/{$logo}\" alt=\"{$title}\""
      . " onerror=\"this.style.display='none';this.nextElementSibling.style.display='block';\">"
      . "<div class=\"card-title\" style=\"display:none\">{$title}</div>"
    : "<div class=\"card-title\">{$title}</div>";

  return <<<HTML
    <a href="/movie/detail.php?id={$id}" class="movie-card">
      <div class="poster-wrap" style="background:{$grad}">
        <img src="/{$poster}" alt="{$title}" loading="lazy"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="poster-fallback" style="display:none"><span>{$title}</span></div>
        <span class="card-rating">&#9733; {$rating}</span>
      </div>
      <div class="card-meta">
        {$titleBlock}
        <div class="card-sub">{$year} &middot; {$genre}</div>
      </div>
    </a>
    HTML;
}

$page_title      = 'MovieReview — Discover & Review Movies';
$active_nav      = 'home';
$body_class      = 'home';
$container_class = 'home-main';
$hide_footer     = true; // hero-only landing — no footer chrome
require __DIR__ . '/../includes/header.php';
?>

  <!-- ============ CINEMATIC HERO ============ -->
  <section class="phero" id="phero">
    <?php foreach ($featured as $m): ?>
      <div class="phero-slide"
        data-tl="<?= htmlspecialchars($m['color_tl']) ?>"
        data-tr="<?= htmlspecialchars($m['color_tr']) ?>"
        data-br="<?= htmlspecialchars($m['color_br']) ?>"
        data-bl="<?= htmlspecialchars($m['color_bl']) ?>">
        <div class="phero-backdrop" style="background-image:url('/<?= htmlspecialchars($m['fanart_bg']) ?>')"></div>
        <div class="phero-shade"></div>
        <div class="phero-content">
          <div class="phero-inner">
            <h1 class="phero-title"><?= htmlspecialchars($m['title']) ?></h1>
            <div class="phero-meta">
              <span class="phero-badge phero-score">&#9733; <?= number_format((float)$m['rating'], 1) ?></span>
              <span class="phero-badge"><?= (int)$m['year'] ?></span>
              <span class="phero-badge"><?= htmlspecialchars($m['genre']) ?></span>
            </div>
            <p class="phero-desc"><?= htmlspecialchars($m['description']) ?></p>
            <a href="/movie/detail.php?id=<?= (int)$m['id'] ?>" class="glass-btn">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              View Details
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- slider posters (normal-size cards) at the hero bottom — also the slide control -->
    <div class="phero-strip">
      <?php foreach ($featured as $i => $m): ?>
        <?php
          $ft    = htmlspecialchars($m['title']);
          $fp    = htmlspecialchars($m['poster']);
          $flogo = htmlspecialchars($m['fanart_logo'] ?? '');
          $fg    = "linear-gradient(135deg,#{$m['color_tl']},#{$m['color_br']})";
        ?>
        <button class="phero-card" data-i="<?= $i ?>" aria-label="<?= $ft ?>">
          <div class="movie-card">
            <div class="poster-wrap" style="background:<?= $fg ?>">
              <img src="/<?= $fp ?>" alt="<?= $ft ?>" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="poster-fallback" style="display:none"><span><?= $ft ?></span></div>
              <span class="card-rating">&#9733; <?= number_format((float)$m['rating'], 1) ?></span>
            </div>
            <div class="card-meta">
              <?php if ($flogo): ?>
                <img class="card-logo" src="/<?= $flogo ?>" alt="<?= $ft ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                <div class="card-title" style="display:none"><?= $ft ?></div>
              <?php else: ?>
                <div class="card-title"><?= $ft ?></div>
              <?php endif; ?>
              <div class="card-sub"><?= (int)$m['year'] ?> &middot; <?= htmlspecialchars($m['genre']) ?></div>
            </div>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ============ HOME RAILS ============ -->
  <div class="rail-wrap">
    <?php foreach ($rails as $label => $list): ?>
      <?php if (!$list) continue; ?>
      <section class="rail">
        <h2 class="rail-head"><?= htmlspecialchars($label) ?></h2>
        <div class="rail-track">
          <?php foreach ($list as $m): ?>
            <div class="rail-card"><?= movie_card($m) ?></div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
