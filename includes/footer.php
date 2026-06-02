<?php
// Shared page footer. Pair with includes/header.php.
// Optional before include: $page_scripts (extra script URLs loaded after the core JS).
$page_scripts = $page_scripts ?? [];
$site_name    = $site_name ?? 'MovieReview';
$hide_footer  = $hide_footer ?? false;
$base_href    = app_base_href();   // explicit absolute base so core JS always loads
?>
  </main>

  <script src="<?= htmlspecialchars($base_href, ENT_QUOTES) ?>js/main.js"></script>
  <script src="<?= htmlspecialchars($base_href, ENT_QUOTES) ?>js/search.js"></script>
  <?php foreach ($page_scripts as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
  <?php endforeach; ?>
</body>

</html>
