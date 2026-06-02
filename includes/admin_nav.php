<?php
/**
 * Admin section sub-navigation. Set $admin_tab before include:
 *   content | comments | reports | users
 */
$admin_tab = $admin_tab ?? '';
$admin_links = [
    'content'  => ['/admin/movies.php',   'Content'],
    'comments' => ['/admin/comments.php', 'Comments'],
    'reports'  => ['/admin/reports.php',  'Reports'],
    'users'    => ['/admin/users.php',    'Users'],
];
?>
<ul class="genre-bar" style="margin-bottom:1.5rem">
  <?php foreach ($admin_links as $key => [$href, $label]): ?>
    <li><a class="pill <?= $key === $admin_tab ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?></a></li>
  <?php endforeach; ?>
</ul>
