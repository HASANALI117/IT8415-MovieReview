<?php
// admin/movies.php
// Admin Panel: view and manage all movies across all creators

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Movie.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$message      = '';
$messageType  = 'ok';

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
        return stripos($m['title'] ?? '', $search) !== false
            || stripos($m['creator'] ?? '', $search) !== false;
    });
}

// filter by status if set
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter === 'published') {
    $movies = array_filter($movies, fn($m) => $m['is_published'] == 1);
} elseif ($statusFilter === 'draft') {
    $movies = array_filter($movies, fn($m) => $m['is_published'] == 0);
}

$page_title = 'Content Management — Admin Panel';
$active_nav = 'admin';
$admin_tab  = 'content';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin_nav.php';
?>

  <div class="page-heading">
    <h1>Content Management</h1>
    <p>View, publish, unpublish, or delete any movie in the system.</p>
  </div>

  <?php if ($message): ?>
    <div class="flash flash--<?= $messageType === 'ok' ? 'ok' : 'error' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- filters bar -->
  <form method="get" class="filter-bar" action="/admin/movies.php">
    <input type="text" name="search" class="form-control" placeholder="Search by title or creator…"
           value="<?= htmlspecialchars($search) ?>">
    <a href="/admin/movies.php" class="glass-btn glass-btn--sm <?= $statusFilter === 'all' ? 'glass-btn--accent' : '' ?>">All</a>
    <a href="/admin/movies.php?status=published&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $statusFilter === 'published' ? 'glass-btn--accent' : '' ?>">Published</a>
    <a href="/admin/movies.php?status=draft&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $statusFilter === 'draft' ? 'glass-btn--accent' : '' ?>">Drafts</a>
    <button type="submit" class="glass-btn glass-btn--sm glass-btn--accent">Search</button>
    <span style="margin-left:auto;align-self:center;color:var(--ink-soft);font-size:.85rem">
      <?= count($movies) ?> movie(s) found
    </span>
  </form>

  <?php if (empty($movies)): ?>
    <div class="empty-state"><h3>No movies found.</h3></div>
  <?php else: ?>
    <div class="glass-table-wrap">
      <table class="glass-table">
        <thead>
          <tr>
            <th>Poster</th><th>Title</th><th>Creator</th><th>Year</th>
            <th>Status</th><th>Rating</th><th>Views</th><th>Added</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($movies as $m): ?>
          <tr>
            <td>
              <img src="/<?= htmlspecialchars($m['image_url']) ?>" alt="poster"
                   style="width:64px;height:42px;object-fit:cover;border-radius:4px"
                   onerror="this.style.visibility='hidden'">
            </td>
            <td><?= htmlspecialchars($m['title']) ?></td>
            <td><span class="badge-pill" style="background:var(--accent-soft);color:var(--accent-dk)"><?= htmlspecialchars($m['creator'] ?? 'Unknown') ?></span></td>
            <td><?= $m['release_year'] ? htmlspecialchars($m['release_year']) : '—' ?></td>
            <td>
              <?php if ($m['is_published']): ?>
                <span class="badge-pill badge-pill--ok">Published</span>
              <?php else: ?>
                <span class="badge-pill badge-pill--draft">Draft</span>
              <?php endif; ?>
            </td>
            <td><?= number_format($m['avg_rating'], 1) ?> ⭐</td>
            <td><?= (int)$m['view_count'] ?></td>
            <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
            <td style="white-space:nowrap">
              <?php if (!$m['is_published']): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                  <input type="hidden" name="action" value="publish">
                  <button class="glass-btn glass-btn--sm" onclick="return confirm('Publish this movie?')">Publish</button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                  <input type="hidden" name="action" value="unpublish">
                  <button class="glass-btn glass-btn--sm" onclick="return confirm('Move back to draft?')">Unpublish</button>
                </form>
              <?php endif; ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="glass-btn glass-btn--sm glass-btn--danger" onclick="return confirm('Permanently delete this movie? This cannot be undone.')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
