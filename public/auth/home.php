<?php
require_once __DIR__ . '/../../includes/session.php';

// must be logged in
if (!is_logged_in()) {
    header('Location: /auth/login.php');
    exit();
}

$username = $_SESSION['username'] ?? '';
$role     = $_SESSION['role'] ?? '';

$page_title = 'My Account — MovieReview';
$active_nav = 'profile';
$container_class = 'app-container--narrow';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="auth-card" style="max-width:540px;margin:2rem auto">
    <h1>Hello, <?= htmlspecialchars($username) ?></h1>
    <p class="auth-sub">You are signed in to MovieReview.</p>

    <p style="margin:0 0 1.25rem">
      <span class="form-label" style="display:inline">Role:</span>
      <span class="badge-pill" style="background:var(--accent-soft);color:var(--accent-dk)"><?= htmlspecialchars(ucfirst($role)) ?></span>
    </p>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <a href="/index.php" class="glass-btn">Browse movies</a>
      <?php if ($role === 'creator' || $role === 'admin'): ?>
        <a href="/creator/movies.php" class="glass-btn">Creator panel</a>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
        <a href="/admin/movies.php" class="glass-btn">Admin panel</a>
        <a href="/auth/view_users.php" class="glass-btn">Manage users</a>
      <?php endif; ?>
      <a href="/auth/logout.php" class="glass-btn glass-btn--danger">Logout</a>
    </div>
  </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
