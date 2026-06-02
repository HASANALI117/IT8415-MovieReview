<?php
// admin_movies.php
// Admin Panel: view and manage all movies across all creators

session_start();
require_once 'DBconn.php';
require_once 'Movie.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message      = '';
$messageType  = 'success';

// handle post actions: delete, publish, unpublish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid    = (int)($_POST['movie_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($mid > 0) {
        if ($action === 'delete') {
            Movie::deleteMovie($mid);
            $message = 'Movie deleted successfully.';
        } elseif ($action === 'publish') {
            Movie::setPublished($mid, 1);
            $message = 'Movie published.';
        } elseif ($action === 'unpublish') {
            Movie::setPublished($mid, 0);
            $message = 'Movie moved back to draft.';
        }
    }
}

// load all movies for display
$movies = Movie::getAllMovies();

// simple search filter applied in php after fetching
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $movies = array_filter($movies, function($m) use ($search) {
        return stripos($m['title'], $search) !== false
            || stripos($m['creator'], $search) !== false;
    });
}

// filter by status if set
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter === 'published') {
    $movies = array_filter($movies, fn($m) => $m['is_published'] == 1);
} elseif ($statusFilter === 'draft') {
    $movies = array_filter($movies, fn($m) => $m['is_published'] == 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Content Management – Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body       { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header     { background: #1a1a2e; color: #fff; padding: 16px 24px;
                     display: flex; justify-content: space-between; align-items: center; }
        header a   { color: #e0b0ff; text-decoration: none; margin-left: 16px; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px 60px; }
        h2         { color: #1a1a2e; margin-bottom: 4px; }
        .subtitle  { color: #666; font-size: 14px; margin-bottom: 24px; }

        /* message */
        .msg-success { background: #d4edda; color: #155724; padding: 12px 16px;
                       border-radius: 6px; margin-bottom: 20px; }
        .msg-error   { background: #f8d7da; color: #721c24; padding: 12px 16px;
                       border-radius: 6px; margin-bottom: 20px; }

        /* filters bar */
        .filters      { display: flex; gap: 12px; align-items: center;
                        flex-wrap: wrap; margin-bottom: 20px; }
        .filters input { padding: 9px 12px; border: 1px solid #ccc; border-radius: 6px;
                         font-size: 14px; width: 260px; }
        .filters input:focus { outline: none; border-color: #6f42c1; }
        .filter-btn   { padding: 9px 16px; border-radius: 6px; border: 1px solid #ccc;
                        background: #fff; font-size: 14px; cursor: pointer;
                        text-decoration: none; color: #333; }
        .filter-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
        .total-count  { margin-left: auto; font-size: 14px; color: #666; }

        /* table */
        table      { width: 100%; border-collapse: collapse; background: #fff;
                     border-radius: 10px; overflow: hidden;
                     box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        th         { background: #1a1a2e; color: #fff; padding: 13px 14px;
                     text-align: left; font-size: 14px; }
        td         { padding: 12px 14px; border-bottom: 1px solid #eee;
                     vertical-align: middle; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td      { background: #f9f9ff; }

        img.thumb  { width: 64px; height: 42px; object-fit: cover; border-radius: 4px; }

        .badge        { display: inline-block; padding: 3px 10px; border-radius: 12px;
                        font-size: 12px; font-weight: bold; }
        .badge-pub    { background: #d4edda; color: #155724; }
        .badge-draft  { background: #fff3cd; color: #856404; }

        .creator-tag  { background: #e8eaf6; color: #3949ab; padding: 2px 8px;
                        border-radius: 10px; font-size: 12px; }

        /* action buttons */
        .btn      { display: inline-block; padding: 6px 12px; border-radius: 5px;
                    font-size: 12px; cursor: pointer; border: none;
                    text-decoration: none; margin-right: 4px; }
        .btn-pub  { background: #28a745; color: #fff; }
        .btn-unpub{ background: #fd7e14; color: #fff; }
        .btn-del  { background: #dc3545; color: #fff; }

        .no-results { text-align: center; color: #888; padding: 50px; }

        /* pagination */
        .pagination   { display: flex; gap: 6px; justify-content: center; margin-top: 24px; }
        .page-btn     { padding: 7px 13px; border-radius: 5px; border: 1px solid #ccc;
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
        <a href="admin_movies.php">Content</a>
        <a href="admin_reports.php">Reports</a>
        <a href="admin_users.php">Users</a>
        <a href="admin_comments.php">Comments</a>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </div>
</header>

<div class="container">
    <h2>Content Management</h2>
    <p class="subtitle">View, publish, unpublish, or delete any movie in the system.</p>

    <?php if ($message): ?>
        <div class="msg-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- filters bar -->
    <form method="get" style="margin:0">
        <div class="filters">
            <input type="text" name="search" placeholder="Search by title or creator..."
                   value="<?php echo htmlspecialchars($search); ?>">

            <a href="admin_movies.php"
               class="filter-btn <?php echo $statusFilter === 'all'       ? 'active' : ''; ?>">
                All
            </a>
            <a href="admin_movies.php?status=published&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $statusFilter === 'published' ? 'active' : ''; ?>">
                Published
            </a>
            <a href="admin_movies.php?status=draft&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $statusFilter === 'draft'     ? 'active' : ''; ?>">
                Drafts
            </a>

            <button type="submit" class="filter-btn">Search</button>

            <span class="total-count">
                <?php echo count($movies); ?> movie(s) found
            </span>
        </div>
    </form>

    <?php if (empty($movies)): ?>
        <p class="no-results">No movies found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Creator</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Views</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movies as $m): ?>
                <tr>
                    <td>
                        <img class="thumb"
                             src="<?php echo htmlspecialchars($m['image_url']); ?>"
                             alt="poster">
                    </td>
                    <td><?php echo htmlspecialchars($m['title']); ?></td>
                    <td>
                        <span class="creator-tag">
                            <?php echo htmlspecialchars($m['creator'] ?? 'Unknown'); ?>
                        </span>
                    </td>
                    <td><?php echo $m['release_year'] ? htmlspecialchars($m['release_year']) : '—'; ?></td>
                    <td>
                        <?php if ($m['is_published']): ?>
                            <span class="badge badge-pub">Published</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($m['avg_rating'], 1); ?> ⭐</td>
                    <td><?php echo (int)$m['view_count']; ?></td>
                    <td><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                    <td>
                        <?php if (!$m['is_published']): ?>
                            <!-- publish draft -->
                            <form method="post" style="display:inline">
                                <input type="hidden" name="movie_id" value="<?php echo $m['movie_id']; ?>">
                                <input type="hidden" name="action" value="publish">
                                <button class="btn btn-pub"
                                        onclick="return confirm('Publish this movie?')">
                                    Publish
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- unpublish published movie -->
                            <form method="post" style="display:inline">
                                <input type="hidden" name="movie_id" value="<?php echo $m['movie_id']; ?>">
                                <input type="hidden" name="action" value="unpublish">
                                <button class="btn btn-unpub"
                                        onclick="return confirm('Move back to draft?')">
                                    Unpublish
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- delete any movie -->
                        <form method="post" style="display:inline">
                            <input type="hidden" name="movie_id" value="<?php echo $m['movie_id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-del"
                                    onclick="return confirm('Permanently delete this movie? This cannot be undone.')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
