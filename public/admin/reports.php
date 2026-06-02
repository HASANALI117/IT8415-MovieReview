<?php
// admin/reports.php
// Admin Panel: generate reports using stored procedures

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

// -------------------------------------------------------
// report 1: top rated movies within a date range
// calls stored procedure p_report_top_movies
// -------------------------------------------------------
function getTopMovies($from, $to, $limit) {
    $conn = getConnection();
    $stmt = $conn->prepare("CALL p_report_top_movies(?, ?, ?)");
    $stmt->bind_param("ssi", $from, $to, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $rows;
}

// -------------------------------------------------------
// report 2: all movies by a specific creator
// calls stored procedure p_report_movies_by_creator
// -------------------------------------------------------
function getMoviesByCreator($creator_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("CALL p_report_movies_by_creator(?)");
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $rows;
}

// get all creators for the dropdown
function getCreators() {
    $conn = getConnection();
    $result = $conn->query(
        "SELECT user_id, username FROM dbProj_users
          WHERE role IN ('creator', 'admin') AND is_active = 1
          ORDER BY username ASC"
    );
    $creators = [];
    while ($row = $result->fetch_assoc()) {
        $creators[] = $row;
    }
    $conn->close();
    return $creators;
}

$creators = getCreators();

// report 1 defaults and results
$r1From    = $_GET['r1_from']  ?? date('Y-01-01');
$r1To      = $_GET['r1_to']    ?? date('Y-m-d');
$r1Limit   = (int)($_GET['r1_limit'] ?? 10);
$r1Results = [];
$r1Run     = isset($_GET['run_r1']);
if ($r1Run) {
    $r1Results = getTopMovies($r1From, $r1To, $r1Limit);
}

// report 2 defaults and results
$r2Creator = (int)($_GET['r2_creator'] ?? 0);
$r2Results = [];
$r2Run     = isset($_GET['run_r2']);
if ($r2Run && $r2Creator > 0) {
    $r2Results = getMoviesByCreator($r2Creator);
}

$page_title = 'Reports — Admin Panel';
$active_nav = 'admin';
$admin_tab  = 'reports';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin_nav.php';
?>

  <div class="page-heading">
    <h1>Reports</h1>
    <p>Generate reports using the filters below. Results are pulled live from the database.</p>
  </div>

  <!-- report 1: top rated movies in a date range -->
  <div class="glass-panel" style="padding:28px 32px;margin-bottom:32px">
    <h3 style="margin:0 0 6px">Report 1 — Most Popular Movies</h3>
    <p style="color:var(--ink-soft);font-size:14px;margin-bottom:20px">Shows the top rated published movies within a selected date range, ordered by average rating.</p>

    <form method="get">
      <input type="hidden" name="run_r1" value="1">
      <?php if ($r2Run): ?>
        <input type="hidden" name="run_r2" value="1">
        <input type="hidden" name="r2_creator" value="<?= $r2Creator ?>">
      <?php endif; ?>

      <div class="filter-bar" style="align-items:flex-end">
        <div style="flex:1 1 160px">
          <label class="form-label" for="r1_from">From date</label>
          <input type="date" id="r1_from" name="r1_from" class="form-control" value="<?= htmlspecialchars($r1From) ?>">
        </div>
        <div style="flex:1 1 160px">
          <label class="form-label" for="r1_to">To date</label>
          <input type="date" id="r1_to" name="r1_to" class="form-control" value="<?= htmlspecialchars($r1To) ?>">
        </div>
        <div style="flex:1 1 120px">
          <label class="form-label" for="r1_limit">Show top</label>
          <select id="r1_limit" name="r1_limit" class="form-select">
            <?php foreach ([5, 10, 20, 50] as $n): ?>
              <option value="<?= $n ?>" <?= $r1Limit == $n ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="glass-btn glass-btn--accent">Generate Report</button>
      </div>
    </form>

    <?php if ($r1Run): ?>
      <?php if (empty($r1Results)): ?>
        <p class="empty-state">No published movies found in this date range.</p>
      <?php else: ?>
        <p style="font-size:13px;color:var(--ink-soft);margin-bottom:12px">
          Showing <b><?= count($r1Results) ?></b> movie(s) published between <b><?= htmlspecialchars($r1From) ?></b> and <b><?= htmlspecialchars($r1To) ?></b>.
        </p>
        <div class="glass-table-wrap">
          <table class="glass-table">
            <thead><tr><th>#</th><th>Title</th><th>Creator</th><th>Year</th><th>Avg Rating</th><th>Ratings</th><th>Views</th><th>Published On</th></tr></thead>
            <tbody>
            <?php foreach ($r1Results as $i => $row): ?>
              <tr>
                <td style="font-weight:700;color:var(--accent)"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['creator'] ?? '—') ?></td>
                <td><?= $row['release_year'] ?? '—' ?></td>
                <td><?= number_format($row['avg_rating'], 2) ?></td>
                <td><?= (int)$row['rating_count'] ?></td>
                <td><?= (int)$row['view_count'] ?></td>
                <td><?= htmlspecialchars($row['published_on'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- report 2: all movies by a specific creator -->
  <div class="glass-panel" style="padding:28px 32px;margin-bottom:32px">
    <h3 style="margin:0 0 6px">Report 2 — Movies by Creator</h3>
    <p style="color:var(--ink-soft);font-size:14px;margin-bottom:20px">Shows all movies (including drafts) submitted by a selected creator.</p>

    <form method="get">
      <input type="hidden" name="run_r2" value="1">
      <?php if ($r1Run): ?>
        <input type="hidden" name="run_r1" value="1">
        <input type="hidden" name="r1_from"  value="<?= htmlspecialchars($r1From) ?>">
        <input type="hidden" name="r1_to"    value="<?= htmlspecialchars($r1To) ?>">
        <input type="hidden" name="r1_limit" value="<?= $r1Limit ?>">
      <?php endif; ?>

      <div class="filter-bar" style="align-items:flex-end">
        <div style="flex:1 1 240px">
          <label class="form-label" for="r2_creator">Select creator</label>
          <select id="r2_creator" name="r2_creator" class="form-select">
            <option value="">— choose a creator —</option>
            <?php foreach ($creators as $c): ?>
              <option value="<?= $c['user_id'] ?>" <?= $r2Creator == $c['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['username']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="glass-btn glass-btn--accent">Generate Report</button>
      </div>
    </form>

    <?php if ($r2Run): ?>
      <?php if ($r2Creator <= 0): ?>
        <p class="empty-state">Please select a creator.</p>
      <?php elseif (empty($r2Results)): ?>
        <p class="empty-state">No movies found for this creator.</p>
      <?php else: ?>
        <?php
            $creatorName = '';
            foreach ($creators as $c) {
                if ($c['user_id'] == $r2Creator) { $creatorName = $c['username']; break; }
            }
        ?>
        <p style="font-size:13px;color:var(--ink-soft);margin-bottom:12px">
          Showing <b><?= count($r2Results) ?></b> movie(s) by <b><?= htmlspecialchars($creatorName) ?></b>.
        </p>
        <div class="glass-table-wrap">
          <table class="glass-table">
            <thead><tr><th>#</th><th>Title</th><th>Status</th><th>Avg Rating</th><th>Ratings</th><th>Views</th><th>Published On</th></tr></thead>
            <tbody>
            <?php foreach ($r2Results as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td>
                  <?php if ($row['is_published']): ?>
                    <span class="badge-pill badge-pill--ok">Published</span>
                  <?php else: ?>
                    <span class="badge-pill badge-pill--draft">Draft</span>
                  <?php endif; ?>
                </td>
                <td><?= number_format($row['avg_rating'], 2) ?></td>
                <td><?= (int)$row['rating_count'] ?></td>
                <td><?= (int)$row['view_count'] ?></td>
                <td><?= $row['published_on'] ? htmlspecialchars($row['published_on']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
