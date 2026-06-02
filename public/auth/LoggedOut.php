<?php
require_once __DIR__ . '/../../includes/session.php';

$_SESSION = array();
session_destroy();

$page_title = 'Session expired — MovieReview';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="auth-wrap">
    <div class="auth-card" style="text-align:center">
      <h1>Session expired</h1>
      <p class="auth-sub">Your session has timed out — you have been logged out.</p>
      <a href="/auth/login.php" class="glass-btn glass-btn--accent" style="width:100%">Log in again</a>
    </div>
  </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
