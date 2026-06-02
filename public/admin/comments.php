<?php
// admin/comments.php
// Admin Panel: view and moderate all comments across all movies

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Comment.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$message = '';

// handle remove or restore actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid    = (int)($_POST['comment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($cid > 0) {
        if ($action === 'remove') {
            Comment::moderate($cid, $_SESSION['user_id'], 1);
            $message = 'Comment removed successfully.';
        } elseif ($action === 'restore') {
            Comment::moderate($cid, $_SESSION['user_id'], 0);
            $message = 'Comment restored.';
        }
    }
}

// search filter
$search   = trim($_GET['search'] ?? '');

// filter by status
$statusFilter = $_GET['status'] ?? 'all';

// load all comments, passing search term to the data object
$comments = Comment::getAllComments($search);

// apply status filter after fetching
if ($statusFilter === 'removed') {
    $comments = array_filter($comments, fn($c) => $c['is_removed'] == 1);
} elseif ($statusFilter === 'active') {
    $comments = array_filter($comments, fn($c) => $c['is_removed'] == 0);
}

// pagination, 10 comments per page
$perPage     = 10;
$totalItems  = count($comments);
$totalPages  = max(1, ceil($totalItems / $perPage));
$currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$offset      = ($currentPage - 1) * $perPage;
$comments    = array_slice(array_values($comments), $offset, $perPage);

$page_title = 'Comment Moderation — Admin Panel';
$active_nav = 'admin';
$admin_tab  = 'comments';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin_nav.php';
?>

  <div class="page-heading">
    <h1>Comment Moderation</h1>
    <p>Remove inappropriate comments or restore previously removed ones.</p>
  </div>

  <?php if ($message): ?>
    <div class="flash flash--ok"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- filters bar -->
  <form method="get" class="filter-bar" action="/admin/comments.php">
    <input type="text" name="search" class="form-control" placeholder="Search by comment, user, or movie…"
           value="<?= htmlspecialchars($search) ?>">
    <a href="/admin/comments.php" class="glass-btn glass-btn--sm <?= $statusFilter === 'all' ? 'glass-btn--accent' : '' ?>">All</a>
    <a href="/admin/comments.php?status=active&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $statusFilter === 'active' ? 'glass-btn--accent' : '' ?>">Active</a>
    <a href="/admin/comments.php?status=removed&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $statusFilter === 'removed' ? 'glass-btn--accent' : '' ?>">Removed</a>
    <button type="submit" class="glass-btn glass-btn--sm glass-btn--accent">Search</button>
    <span style="margin-left:auto;align-self:center;color:var(--ink-soft);font-size:.85rem">
      <?= $totalItems ?> comment(s) found
    </span>
  </form>

  <?php if (empty($comments)): ?>
    <div class="empty-state"><h3>No comments found.</h3></div>
  <?php else: ?>
    <?php foreach ($comments as $c): ?>
      <div class="glass-card" style="padding:18px 22px;margin-bottom:14px;border-left:4px solid <?= $c['is_removed'] ? 'var(--danger)' : 'var(--accent)' ?>;<?= $c['is_removed'] ? 'opacity:.8' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
          <div style="font-size:13px;color:var(--ink-soft)">
            <b style="color:var(--ink)"><?= htmlspecialchars($c['username']) ?></b>
            &nbsp;on&nbsp;
            <span class="badge-pill" style="background:var(--accent-soft);color:var(--accent-dk)"><?= htmlspecialchars($c['movie_title']) ?></span>
            &nbsp;&mdash;&nbsp;<?= date('d M Y, H:i', strtotime($c['created_at'])) ?>
          </div>
          <form method="post" style="margin:0">
            <input type="hidden" name="comment_id" value="<?= $c['comment_id'] ?>">
            <?php if (!$c['is_removed']): ?>
              <input type="hidden" name="action" value="remove">
              <button class="glass-btn glass-btn--sm glass-btn--danger" onclick="return confirm('Remove this comment?')">Remove</button>
            <?php else: ?>
              <input type="hidden" name="action" value="restore">
              <button class="glass-btn glass-btn--sm" onclick="return confirm('Restore this comment?')">Restore</button>
            <?php endif; ?>
          </form>
        </div>

        <?php if ($c['is_removed']): ?>
          <p style="margin:12px 0 0;color:var(--ink-faint);font-style:italic">[This comment has been removed by an admin]</p>
          <?php if ($c['removed_by_name']): ?>
            <p style="font-size:12px;color:var(--danger);margin-top:8px">
              Removed by: <?= htmlspecialchars($c['removed_by_name']) ?>
              <?php if ($c['removed_at']): ?> on <?= date('d M Y', strtotime($c['removed_at'])) ?><?php endif; ?>
            </p>
          <?php endif; ?>
        <?php else: ?>
          <p style="margin:12px 0 0;font-size:15px;line-height:1.6;word-break:break-word"><?= nl2br(htmlspecialchars($c['body'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
      <nav class="pagination-row">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $statusFilter !== 'all' ? '&status=' . $statusFilter : '' ?>"
             class="page-link-pill <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
