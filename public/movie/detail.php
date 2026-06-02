<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';

$conn = getConnection();

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$stmt = $conn->prepare("SELECT * FROM dbProj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    die("Movie not found. Please check the ID.");
}

$stmt->close();

// Categories
$stmt_cat = $conn->prepare("SELECT name FROM dbProj_categories c JOIN dbProj_movie_categories mc ON c.category_id = mc.category_id WHERE mc.movie_id = ?");
$stmt_cat->bind_param("i", $movie_id);
$stmt_cat->execute();
$categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

// Director
$stmt_dir = $conn->prepare("SELECT p.full_name FROM dbProj_movie_cast mc JOIN dbProj_persons p ON mc.person_id = p.person_id WHERE mc.movie_id = ? AND mc.role = 'director' LIMIT 1");
$stmt_dir->bind_param("i", $movie_id);
$stmt_dir->execute();
$director_res = $stmt_dir->get_result()->fetch_assoc();
$director_name = $director_res ? $director_res['full_name'] : "Unknown";
$stmt_dir->close();

// Cast (non-director), for the Cast & Crew row
$stmt_cast = $conn->prepare("SELECT p.full_name, mc.role FROM dbProj_movie_cast mc JOIN dbProj_persons p ON mc.person_id = p.person_id WHERE mc.movie_id = ? AND mc.role <> 'director' LIMIT 12");
$stmt_cast->bind_param("i", $movie_id);
$stmt_cast->execute();
$cast = $stmt_cast->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cast->close();

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Ambient background from this movie's ultrablur corner colors.
$bg_palette = null;
if (!empty($movie['color_tl'])) {
    $bg_palette = [$movie['color_tl'], $movie['color_tr'], $movie['color_br'], $movie['color_bl']];
}

$page_title = htmlspecialchars($movie['title']) . ' — MovieReview';
// jQuery + jQuery UI in <head> so the inline ready() handler works.
$head_extra = '<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">'
            . '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>'
            . '<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>';
require __DIR__ . '/../../includes/header.php';
?>

  <!-- ===== Plex-style header: poster left, info right ===== -->
  <div class="pdetail-top">
    <img src="/<?= htmlspecialchars($movie['image_url']) ?>" class="pdetail-poster" alt="<?= htmlspecialchars($movie['title']) ?> poster"
         onerror="this.style.visibility='hidden'">

    <div class="pdetail-info">
      <h1><?= htmlspecialchars($movie['title']) ?></h1>
      <div class="pdetail-year"><?= (int)$movie['release_year'] ?></div>

      <div class="pdetail-meta">
        <span class="pdetail-score">&#9733; <?= number_format((float)$movie['avg_rating'], 1) ?>/5</span>
        <?php if (!empty($movie['duration_min'])): ?>
          <span class="dot">&bull;</span><span><?= (int)$movie['duration_min'] ?> min</span>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
          <span class="pdetail-tag"><?= htmlspecialchars($cat['name']) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="pdetail-actions">
        <?php if (is_logged_in()): ?>
          <span style="color:var(--ink-soft);font-size:.9rem">Your rating:</span>
          <div id="stars" class="star-rating" title="Click to rate">
            <span data-value="1">&#9733;</span>
            <span data-value="2">&#9733;</span>
            <span data-value="3">&#9733;</span>
            <span data-value="4">&#9733;</span>
            <span data-value="5">&#9733;</span>
          </div>
          <input type="hidden" id="selected-rating" value="0">
        <?php else: ?>
          <a href="/auth/login.php" class="glass-btn glass-btn--accent">Login to rate &amp; review</a>
        <?php endif; ?>
      </div>

      <p class="pdetail-synopsis"><?= nl2br(htmlspecialchars($movie['synopsis'])) ?></p>
      <p class="pdetail-director"><b>Director:</b> <?= htmlspecialchars($director_name) ?></p>
    </div>
  </div>

  <?php if (!empty($cast)): ?>
    <hr class="section-divider">
    <h2 class="section-label">Cast &amp; Crew</h2>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <?php foreach ($cast as $c): ?>
        <span class="pdetail-tag" style="padding:6px 14px">
          <?= htmlspecialchars($c['full_name']) ?>
          <span style="color:var(--ink-faint)">&middot; <?= htmlspecialchars($c['role']) ?></span>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ===== Ratings & Reviews ===== -->
  <hr class="section-divider">
  <div class="comment-box">
    <h2 class="section-label">Ratings &amp; Reviews</h2>

    <?php if (is_logged_in()): ?>
      <form id="comment-form" class="glass-panel" style="padding:16px;margin-bottom:1.25rem">
        <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
        <textarea name="comment" id="comment-text" class="form-control glass-textarea" placeholder="Write a review… (select a star rating above first)"></textarea>
        <button type="submit" id="submit-comment" class="glass-btn glass-btn--accent" style="margin-top:10px">Post Review</button>
      </form>
    <?php endif; ?>

    <div id="comments-list">
      <?php
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

      while ($row = $comments_result->fetch_assoc()):
      ?>
        <div class="comment-item" id="comment-<?= $row['comment_id'] ?>">
          <strong><?= htmlspecialchars($row['username']) ?></strong>
          <span style="color:#f5a623"><?= str_repeat('&#9733;', $row['stars'] ?? 0) ?></span>
          <p><?= htmlspecialchars($row['body']) ?></p>
          <?php if ($is_admin): ?>
            <span class="delete-btn ui-icon ui-icon-trash" onclick="confirmDelete(<?= $row['comment_id'] ?>)"></span>
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
        $(this).parent().effect("bounce", {times: 2}, 300);
    });

    // Submit Comment via AJAX
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        var rating = $('#selected-rating').val();
        var comment = $('#comment-text').val();

        if(rating == 0) { alert("Please select a rating!"); return; }

        $.ajax({
            url: (window.BASE || '/') + 'ajax/process_comment.php',
            method: 'POST',
            data: $(this).serialize() + "&rating=" + rating,
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status == 'success') {
                    var newComment = `
                        <div class="comment-item" id="comment-${data.id}" style="display:none;">
                            <strong>You</strong>
                            <span style="color: #f5a623;">${"&#9733;".repeat(rating)}</span>
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

function confirmDelete(id) {
    $("#delete-confirm").dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        buttons: {
            "Delete": function() {
                $.post((window.BASE || '/') + 'ajax/delete_comment.php', {id: id}, function(res) {
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

<?php require __DIR__ . '/../../includes/footer.php'; ?>
