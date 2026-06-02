<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../src/Movie.php';

// The home page is hero-only: the top 10 rated published movies, shown as the
// cinematic backdrop slider + poster strip. (Browse-by-genre and the full grid
// live on search.php, reached via the nav search tab.)
[$featured] = Movie::searchPublic(['sort' => 'rating'], 10, 0);

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
            <?php $hlogo = htmlspecialchars($m['fanart_logo'] ?? ''); ?>
            <?php if ($hlogo): ?>
              <img class="phero-logo" src="/<?= $hlogo ?>" alt="<?= htmlspecialchars($m['title']) ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
              <h1 class="phero-title" style="display:none"><?= htmlspecialchars($m['title']) ?></h1>
            <?php else: ?>
              <h1 class="phero-title"><?= htmlspecialchars($m['title']) ?></h1>
            <?php endif; ?>
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
          $ft = htmlspecialchars($m['title']);
          $fp = htmlspecialchars($m['poster']);
          $fg = "linear-gradient(135deg,#{$m['color_tl']},#{$m['color_br']})";
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
              <div class="card-title"><?= $ft ?></div>
              <div class="card-sub"><?= (int)$m['year'] ?> &middot; <?= htmlspecialchars($m['genre']) ?></div>
            </div>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
  </section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
