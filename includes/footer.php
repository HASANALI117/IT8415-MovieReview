<?php
/**
 * Shared page footer / layout bottom. Pair with includes/header.php.
 *
 * Optional before include:
 *   $page_scripts  array  extra script src URLs, loaded after the core JS
 */
$page_scripts = $page_scripts ?? [];
$site_name    = $site_name ?? 'MovieReview';
$hide_footer  = $hide_footer ?? false;
?>
  </main>

  <?php if (!$hide_footer): ?>
  <footer class="app-footer">
    <div class="app-container">
      <span><?= htmlspecialchars($site_name) ?></span> — IT8415 Database Programming 2 · Group Project
    </div>
  </footer>
  <?php endif; ?>

  <script src="/js/main.js"></script>
  <script src="/js/search.js"></script>
  <?php foreach ($page_scripts as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
  <?php endforeach; ?>
</body>

</html>
