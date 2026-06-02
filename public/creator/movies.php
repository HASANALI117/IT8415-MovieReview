<?php
// creator_movies.php
// Creator Panel: shows all movies belonging to the logged-in creator

session_start();
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Movie.php';

// --- Access control: creators and admins only ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['creator', 'admin'])) {
    header('Location: /index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Movies – Creator Panel</title>
    <style>
        body        { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header      { background: #1a1a2e; color: #fff; padding: 16px 24px;
                      display: flex; justify-content: space-between; align-items: center; }
        header a    { color: #e0b0ff; text-decoration: none; margin-left: 16px; }
        .container  { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        h2          { color: #1a1a2e; }
        .msg        { background: #d4edda; color: #155724; padding: 10px 16px;
                      border-radius: 6px; margin-bottom: 20px; }
        .btn        { display: inline-block; padding: 7px 14px; border-radius: 5px;
                      font-size: 13px; cursor: pointer; border: none; text-decoration: none; }
        .btn-add    { background: #6f42c1; color: #fff; margin-bottom: 20px; }
        .btn-edit   { background: #007bff; color: #fff; }
        .btn-pub    { background: #28a745; color: #fff; }
        .btn-unpub  { background: #fd7e14; color: #fff; }
        .btn-del    { background: #dc3545; color: #fff; }
        table       { width: 100%; border-collapse: collapse; background: #fff;
                      border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        th          { background: #1a1a2e; color: #fff; padding: 12px 14px; text-align: left; }
        td          { padding: 11px 14px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9ff; }
        .badge      { display: inline-block; padding: 3px 10px; border-radius: 12px;
                      font-size: 12px; font-weight: bold; }
        .badge-pub  { background: #d4edda; color: #155724; }
        .badge-draft{ background: #fff3cd; color: #856404; }
        img.thumb   { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .no-movies  { text-align: center; color: #888; padding: 40px; }
    </style>
</head>
<body>

<header>
    <span>🎬 Creator Panel</span>
    <div>
        <a href="movies.php">My Movies</a>
        <a href="add_movie.php">+ Add Movie</a>
        <a href="/index.php">Home</a>
        <a href="/auth/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </div>
</header>

<div class="container">
    <h2>My Movies</h2>

    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <a href="add_movie.php" class="btn btn-add">+ Add New Movie</a>

    <?php if (empty($movies)): ?>
        <p class="no-movies">You have not added any movies yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Avg Rating</th>
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
                             src="/<?php echo htmlspecialchars($m['image_url']); ?>"
                             alt="poster">
                    </td>
                    <td><?php echo htmlspecialchars($m['title']); ?></td>
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
                            <!-- Draft: can edit, publish, or delete -->
                            <a class="btn btn-edit"
                               href="edit_movie.php?id=<?php echo $m['movie_id']; ?>">Edit</a>

                            <form method="post" style="display:inline">
                                <input type="hidden" name="movie_id"
                                       value="<?php echo $m['movie_id']; ?>">
                                <input type="hidden" name="action" value="publish">
                                <button class="btn btn-pub"
                                        onclick="return confirm('Publish this movie?')">
                                    Publish
                                </button>
                            </form>

                            <form method="post" style="display:inline">
                                <input type="hidden" name="movie_id"
                                       value="<?php echo $m['movie_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-del"
                                        onclick="return confirm('Delete this movie permanently?')">
                                    Delete
                                </button>
                            </form>

                        <?php else: ?>
                            <!-- Published: can only unpublish -->
                            <form method="post" style="display:inline">
                                <input type="hidden" name="movie_id"
                                       value="<?php echo $m['movie_id']; ?>">
                                <input type="hidden" name="action" value="unpublish">
                                <button class="btn btn-unpub"
                                        onclick="return confirm('Move back to draft?')">
                                    Unpublish
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
