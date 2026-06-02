<?php
// admin_comments.php
// Admin Panel: view and moderate all comments across all movies

session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comment Moderation – Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body       { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header     { background: #1a1a2e; color: #fff; padding: 16px 24px;
                     display: flex; justify-content: space-between; align-items: center; }
        header a   { color: #e0b0ff; text-decoration: none; margin-left: 16px; }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px 60px; }
        h2         { color: #1a1a2e; margin-bottom: 4px; }
        .subtitle  { color: #666; font-size: 14px; margin-bottom: 24px; }

        /* message */
        .msg-success { background: #d4edda; color: #155724; padding: 12px 16px;
                       border-radius: 6px; margin-bottom: 20px; }

        /* filters */
        .filters      { display: flex; gap: 12px; align-items: center;
                        flex-wrap: wrap; margin-bottom: 20px; }
        .filters input { padding: 9px 12px; border: 1px solid #ccc; border-radius: 6px;
                         font-size: 14px; width: 280px; }
        .filters input:focus { outline: none; border-color: #6f42c1; }
        .filter-btn   { padding: 9px 16px; border-radius: 6px; border: 1px solid #ccc;
                        background: #fff; font-size: 14px; cursor: pointer;
                        text-decoration: none; color: #333; }
        .filter-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
        .total-count  { margin-left: auto; font-size: 14px; color: #666; }

        /* comment cards */
        .comment-card  { background: #fff; border-radius: 10px; padding: 18px 22px;
                         box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: 14px;
                         border-left: 4px solid #6f42c1; }
        .comment-card.removed { border-left-color: #dc3545; opacity: 0.75; }

        .card-top      { display: flex; justify-content: space-between;
                         align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .card-meta     { font-size: 13px; color: #555; }
        .card-meta b   { color: #1a1a2e; }
        .card-meta .movie-tag { background: #e8eaf6; color: #3949ab; padding: 2px 8px;
                                border-radius: 10px; font-size: 12px; margin-left: 6px; }

        .card-body     { margin: 12px 0 0; font-size: 15px; color: #333;
                         line-height: 1.6; word-break: break-word; }
        .card-body.redacted { color: #999; font-style: italic; }

        .removed-note  { font-size: 12px; color: #dc3545; margin-top: 8px; }

        /* action buttons */
        .btn       { display: inline-block; padding: 6px 14px; border-radius: 5px;
                     font-size: 13px; cursor: pointer; border: none; }
        .btn-remove  { background: #dc3545; color: #fff; }
        .btn-restore { background: #28a745; color: #fff; }

        .no-results { text-align: center; color: #888; padding: 50px;
                      font-style: italic; }

        /* pagination */
        .pagination  { display: flex; gap: 6px; justify-content: center; margin-top: 28px; }
        .page-btn    { padding: 7px 13px; border-radius: 5px; border: 1px solid #ccc;
                       background: #fff; cursor: pointer; font-size: 14px;
                       text-decoration: none; color: #333; }
        .page-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
        .page-btn:hover:not(.active) { background: #f0f0f0; }
    </style>
</head>
<body>

<header>
    <span>🛡️ Admin Panel</span>
    <div>
        <a href="movies.php">Content</a>
        <a href="reports.php">Reports</a>
        <a href="users.php">Users</a>
        <a href="comments.php">Comments</a>
        <a href="/index.php">Home</a>
        <a href="/auth/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </div>
</header>

<div class="container">
    <h2>Comment Moderation</h2>
    <p class="subtitle">Remove inappropriate comments or restore previously removed ones.</p>

    <?php if ($message): ?>
        <div class="msg-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- filters bar -->
    <form method="get" style="margin:0">
        <div class="filters">
            <input type="text" name="search"
                   placeholder="Search by comment, user, or movie..."
                   value="<?php echo htmlspecialchars($search); ?>">

            <a href="comments.php"
               class="filter-btn <?php echo $statusFilter === 'all'     ? 'active' : ''; ?>">
                All
            </a>
            <a href="comments.php?status=active&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $statusFilter === 'active'  ? 'active' : ''; ?>">
                Active
            </a>
            <a href="comments.php?status=removed&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $statusFilter === 'removed' ? 'active' : ''; ?>">
                Removed
            </a>

            <button type="submit" class="filter-btn">Search</button>

            <span class="total-count">
                <?php echo $totalItems; ?> comment(s) found
            </span>
        </div>
    </form>

    <?php if (empty($comments)): ?>
        <p class="no-results">No comments found.</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment-card <?php echo $c['is_removed'] ? 'removed' : ''; ?>">
                <div class="card-top">
                    <div class="card-meta">
                        <b><?php echo htmlspecialchars($c['username']); ?></b>
                        &nbsp;on&nbsp;
                        <span class="movie-tag">
                            <?php echo htmlspecialchars($c['movie_title']); ?>
                        </span>
                        &nbsp;&mdash;&nbsp;
                        <?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?>
                    </div>

                    <!-- action button -->
                    <form method="post" style="margin:0">
                        <input type="hidden" name="comment_id"
                               value="<?php echo $c['comment_id']; ?>">
                        <?php if (!$c['is_removed']): ?>
                            <input type="hidden" name="action" value="remove">
                            <button class="btn btn-remove"
                                    onclick="return confirm('Remove this comment?')">
                                Remove
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="restore">
                            <button class="btn btn-restore"
                                    onclick="return confirm('Restore this comment?')">
                                Restore
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- comment body, show placeholder if removed -->
                <?php if ($c['is_removed']): ?>
                    <p class="card-body redacted">
                        [This comment has been removed by an admin]
                    </p>
                    <?php if ($c['removed_by_name']): ?>
                        <p class="removed-note">
                            Removed by: <?php echo htmlspecialchars($c['removed_by_name']); ?>
                            <?php if ($c['removed_at']): ?>
                                on <?php echo date('d M Y', strtotime($c['removed_at'])); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="card-body">
                        <?php echo nl2br(htmlspecialchars($c['body'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?php echo $p;
                                         echo $search    ? '&search='  . urlencode($search)    : '';
                                         echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?>"
                       class="page-btn <?php echo $p === $currentPage ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>
