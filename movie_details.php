<?php
session_start();
require_once 'db_connect.php';

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$stmt = $conn->prepare("SELECT * FROM dbProj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    die("Movie not found. Please check the ID.");
}

$stmt->close();

// Fetch Categories
$stmt_cat = $conn->prepare("SELECT name FROM dbProj_categories c JOIN dbProj_movie_categories mc ON c.category_id = mc.category_id WHERE mc.movie_id = ?");
$stmt_cat->bind_param("i", $movie_id);
$stmt_cat->execute();
$categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

// Fetch Director from Cast tables
$stmt_dir = $conn->prepare("SELECT p.full_name FROM dbProj_movie_cast mc JOIN dbProj_persons p ON mc.person_id = p.person_id WHERE mc.movie_id = ? AND mc.role = 'director' LIMIT 1");
$stmt_dir->bind_param("i", $movie_id);
$stmt_dir->execute();
$director_res = $stmt_dir->get_result()->fetch_assoc();
$director_name = $director_res ? $director_res['full_name'] : "Unknown";
$stmt_dir->close();

// Check if user is admin
$is_admin = isset($_SESSION['Role']) && $_SESSION['Role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $movie['title']; ?> - Movie Details</title>
    <!-- jQuery & jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Arial; background: #fcfcfc; }
        .hero-banner { height: 400px; background: url('<?php echo $movie['fanart_bg']; ?>') center/cover; position: relative; display: flex; align-items: flex-end; }
        .hero-overlay { background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%); width: 100%; padding: 40px; color: white; }
        .movie-container { display: flex; gap: 30px; max-width: 1200px; margin: -50px auto 0; padding: 0 20px; position: relative; z-index: 2; }
        .poster { width: 280px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); border: 4px solid white; }
        .details-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex: 1; }
        .star-rating { font-size: 24px; cursor: pointer; color: #ccc; }
        .star-rating .selected, .star-rating .hover { color: #f39c12; }
        .comment-box { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .comment-item { padding: 10px; border-bottom: 1px solid #f9f9f9; position: relative; }
        .delete-btn { position: absolute; right: 10px; top: 10px; color: red; cursor: pointer; }
        .ui-tooltip { font-size: 12px; }
        .user-nav { position: absolute; top: 20px; right: 40px; color: white; z-index: 10; }
        .user-nav a { color: white; text-decoration: none; font-weight: bold; margin-left: 15px; }
    </style>
</head>
<body>

<div class="user-nav">
    <?php if (isset($_SESSION['UserId'])): ?>
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['Username']); ?></strong>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login to Review</a>
    <?php endif; ?>
</div>

<div class="hero-banner">
    <div class="hero-overlay">
        <h1 style="font-size: 3em; margin: 0;"><?php echo $movie['title']; ?></h1>
        <p style="margin: 5px 0;">
            <?php foreach($categories as $cat) echo "<span class='ui-state-default ui-corner-all' style='padding: 2px 8px; font-size: 0.8em; margin-right: 5px;'>".$cat['name']."</span>"; ?>
        </p>
        <p style="font-size: 1.2em; opacity: 0.9;"><?php echo $movie['release_year']; ?> • Rating: <?php echo $movie['avg_rating']; ?>/5</p>
    </div>
</div>

<div class="movie-container">
    <img src="<?php echo $movie['image_url']; ?>" class="poster" alt="Poster">
    <div class="details-card">
        <p><strong>Director:</strong> <?php echo $director_name; ?></p>
        <p style="line-height: 1.6; color: #444;"><?php echo $movie['synopsis']; ?></p>

        <?php if (isset($_SESSION['UserId'])): ?>
        <div class="rating-section">
            <h3>Rate this Movie</h3>
            <div id="stars" class="star-rating" title="Click to rate">
                <span data-value="1">&#9733;</span>
                <span data-value="2">&#9733;</span>
                <span data-value="3">&#9733;</span>
                <span data-value="4">&#9733;</span>
                <span data-value="5">&#9733;</span>
            </div>
            <input type="hidden" id="selected-rating" value="0">
        </div>
        <?php else: ?>
            <p><em>Please <a href="login.php" style="color:#007bff">login</a> to rate this movie.</em></p>
        <?php endif; ?>
    </div>
</div>

<div class="comment-box" style="width: 80%; margin: 0 auto;">
    <h3>User Comments</h3>
    <?php if (isset($_SESSION['UserId'])): ?>
    <form id="comment-form">
        <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
        <textarea name="comment" id="comment-text" style="width: 100%; height: 80px;" placeholder="Add a comment..."></textarea><br>
        <button type="submit" id="submit-comment">Post Comment</button>
    </form>
    <?php endif; ?>

    <div id="comments-list">
        <!-- Comments loaded via AJAX or PHP Loop -->
        <?php
        // JOIN users to get username and ratings to get stars
        $stmt_comments = $conn->prepare("
            SELECT c.comment_id, c.body, c.created_at, u.username, u.user_id, r.stars 
            FROM dbProj_comments c 
            JOIN dbProj_users u ON c.user_id = u.user_id 
            LEFT JOIN dbProj_ratings r ON c.user_id = r.user_id AND c.movie_id = r.movie_id
            WHERE c.movie_id = ? AND c.is_removed = 0 
            ORDER BY c.created_at DESC");
        $stmt_comments->bind_param("i", $movie_id);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();

        while($row = $comments_result->fetch_assoc()):
        ?>
            <div class="comment-item" id="comment-<?php echo $row['comment_id']; ?>">
                <strong><?php echo htmlspecialchars($row['username']); ?></strong> 
                <span style="color: #f39c12;"><?php echo str_repeat('&#9733;', $row['stars'] ?? 0); ?></span>
                <p><?php echo htmlspecialchars($row['body']); ?></p>
                <?php if($is_admin): ?>
                    <span class="delete-btn ui-icon ui-icon-trash" onclick="confirmDelete(<?php echo $row['comment_id']; ?>)"></span>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        <?php $stmt_comments->close(); ?>
    </div>
</div>

<!-- jQuery UI Dialog for Deletion -->
<div id="delete-confirm" title="Delete Comment?" style="display:none;">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>This comment will be permanently deleted. Are you sure?</p>
</div>

<script>
$(document).ready(function() {
    // jQuery UI Tooltip
    $( document ).tooltip();

    // Star Rating Interaction
    $('#stars span').on('mouseover', function() {
        $(this).prevAll().addBack().addClass('hover');
    }).on('mouseout', function() {
        $(this).siblings().addBack().removeClass('hover');
    }).on('click', function() {
        var val = $(this).data('value');
        $('#selected-rating').val(val);
        $(this).siblings().removeClass('selected');
        $(this).prevAll().addBack().addClass('selected');
        // jQuery UI Effect on selection
        $(this).parent().effect("bounce", {times: 2}, 300);
    });

    // Submit Comment via AJAX
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        var rating = $('#selected-rating').val();
        var comment = $('#comment-text').val();
        
        if(rating == 0) { alert("Please select a rating!"); return; }

        $.ajax({
            url: 'process_comment.php',
            method: 'POST',
            data: $(this).serialize() + "&rating=" + rating,
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status == 'success') {
                    var newComment = `
                        <div class="comment-item" id="comment-${data.id}" style="display:none;">
                            <strong>You</strong> 
                            <span style="color: #f39c12;">${"&#9733;".repeat(rating)}</span>
                            <p>${comment}</p>
                        </div>`;
                    $('#comments-list').prepend(newComment);
                    $(`#comment-${data.id}`).show('blind', 500).effect("highlight", {}, 2000);
                    $('#comment-text').val('');
                }
            }
        });
    });
});

// jQuery UI Dialog for Admin Delete
function confirmDelete(id) {
    $("#delete-confirm").dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        buttons: {
            "Delete": function() {
                $.post('delete_comment.php', {id: id}, function(res) {
                    if(res == 'success') {
                        $(`#comment-${id}`).hide('explode', 1000);
                    }
                });
                $(this).dialog("close");
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });
}
</script>
</body>
</html>