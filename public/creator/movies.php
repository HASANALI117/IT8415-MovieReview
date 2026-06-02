<?php
// creator/movies.php
// Creator Panel: shows all movies belonging to the logged-in creator

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Movie.php';

// --- Access control: creators and admins only ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['creator', 'admin'])) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$creator_id = $_SESSION['user_id'];
$movies     = Movie::getByCreator($creator_id);

// Handle publish/unpublish action from this page
$message = '';
if (isset($_POST['action'], $_POST['movie_id'])) {
    $mid    = (int)$_POST['movie_id'];
    $action = $_POST['action'];

    if ($action === 'publish') {
        Movie::setPublished($mid, 1);
        $message = 'Movie published successfully.';
    } elseif ($action === 'unpublish') {
        Movie::setPublished($mid, 0);
        $message = 'Movie moved back to drafts.';
    } elseif ($action === 'delete') {
        Movie::deleteMovie($mid);
        $message = 'Movie deleted.';
    }

    // Reload list after action
    $movies = Movie::getByCreator($creator_id);
}

$page_title = 'My Movies — Creator Panel';
$active_nav = 'creator';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="page-heading" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
    <div>
      <h1>My Movies</h1>
      <p>Manage the movies you have submitted.</p>
    </div>
    <a href="/creator/add_movie.php" class="glass-btn glass-btn--accent">+ Add New Movie</a>
  </div>

  <?php if ($message): ?>
    <div class="flash flash--ok"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if (empty($movies)): ?>
    <div class="empty-state"><h3>You have not added any movies yet.</h3></div>
  <?php else: ?>
    <div class="glass-table-wrap">
      <table class="glass-table">
        <thead>
          <tr><th>Poster</th><th>Title</th><th>Year</th><th>Status</th><th>Avg Rating</th><th>Views</th><th>Added</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($movies as $m): ?>
          <tr>
            <td>
              <img src="/<?= htmlspecialchars($m['image_url']) ?>" alt="poster"
                   style="width:60px;height:40px;object-fit:cover;border-radius:4px"
                   onerror="this.style.visibility='hidden'">
            </td>
            <td><?= htmlspecialchars($m['title']) ?></td>
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
                <a class="glass-btn glass-btn--sm" href="/creator/edit_movie.php?id=<?= $m['movie_id'] ?>">Edit</a>
                <form method="post" style="display:inline">
                  <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                  <input type="hidden" name="action" value="publish">
                  <button class="glass-btn glass-btn--sm glass-btn--accent" onclick="return confirm('Publish this movie?')">Publish</button>
                </form>
                <form method="post" style="display:inline">
                  <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="glass-btn glass-btn--sm glass-btn--danger" onclick="return confirm('Delete this movie permanently?')">Delete</button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="movie_id" value="<?= $m['movie_id'] ?>">
                  <input type="hidden" name="action" value="unpublish">
                  <button class="glass-btn glass-btn--sm" onclick="return confirm('Move back to draft?')">Unpublish</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
