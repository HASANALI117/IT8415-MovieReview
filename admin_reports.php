<?php
// admin_reports.php
// Admin Panel: generate reports using stored procedures

session_start();
require_once 'DBconn.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports – Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body       { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header     { background: #1a1a2e; color: #fff; padding: 16px 24px;
                     display: flex; justify-content: space-between; align-items: center; }
        header a   { color: #e0b0ff; text-decoration: none; margin-left: 16px; }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px 60px; }
        h2         { color: #1a1a2e; margin-bottom: 4px; }
        .subtitle  { color: #666; font-size: 14px; margin-bottom: 30px; }

        /* report cards */
        .report-card       { background: #fff; border-radius: 10px; padding: 28px 32px;
                             box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-bottom: 32px; }
        .report-card h3    { margin: 0 0 6px; font-size: 17px; color: #1a1a2e; }
        .report-card .desc { color: #666; font-size: 14px; margin-bottom: 20px; }

        /* filter row inside each report */
        .filter-row        { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;
                             background: #f8f8ff; border-radius: 8px; padding: 16px 18px;
                             margin-bottom: 24px; }
        .filter-row .fg    { display: flex; flex-direction: column; gap: 5px; }
        .filter-row label  { font-size: 13px; font-weight: bold; color: #444; }
        .filter-row input,
        .filter-row select { padding: 8px 11px; border: 1px solid #ccc; border-radius: 6px;
                             font-size: 14px; }
        .filter-row input:focus,
        .filter-row select:focus { outline: none; border-color: #6f42c1; }

        .btn-run   { padding: 9px 22px; background: #6f42c1; color: #fff;
                     border: none; border-radius: 6px; font-size: 14px;
                     cursor: pointer; align-self: flex-end; }
        .btn-run:hover { background: #5a32a3; }

        /* results table */
        table      { width: 100%; border-collapse: collapse; }
        th         { background: #1a1a2e; color: #fff; padding: 11px 13px;
                     text-align: left; font-size: 13px; }
        td         { padding: 10px 13px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td      { background: #f9f9ff; }

        .rank      { font-weight: bold; color: #6f42c1; font-size: 15px; }

        .badge        { display: inline-block; padding: 3px 10px; border-radius: 12px;
                        font-size: 12px; font-weight: bold; }
        .badge-pub    { background: #d4edda; color: #155724; }
        .badge-draft  { background: #fff3cd; color: #856404; }

        .no-results { text-align: center; color: #888; padding: 30px;
                      font-style: italic; }

        /* summary row */
        .summary   { font-size: 13px; color: #555; margin-bottom: 12px; }
        .summary b { color: #1a1a2e; }
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
    <h2>Reports</h2>
    <p class="subtitle">Generate reports using the filters below. Results are pulled live from the database.</p>

    <!-- report 1: top rated movies in a date range -->
    <div class="report-card">
        <h3>📊 Report 1 — Most Popular Movies</h3>
        <p class="desc">Shows the top rated published movies within a selected date range, ordered by average rating.</p>

        <form method="get">
            <input type="hidden" name="run_r1" value="1">
            <!-- carry report 2 state so switching tabs does not reset it -->
            <?php if ($r2Run): ?>
                <input type="hidden" name="run_r2" value="1">
                <input type="hidden" name="r2_creator" value="<?php echo $r2Creator; ?>">
            <?php endif; ?>

            <div class="filter-row">
                <div class="fg">
                    <label for="r1_from">From date</label>
                    <input type="date" id="r1_from" name="r1_from"
                           value="<?php echo htmlspecialchars($r1From); ?>">
                </div>
                <div class="fg">
                    <label for="r1_to">To date</label>
                    <input type="date" id="r1_to" name="r1_to"
                           value="<?php echo htmlspecialchars($r1To); ?>">
                </div>
                <div class="fg">
                    <label for="r1_limit">Show top</label>
                    <select id="r1_limit" name="r1_limit">
                        <?php foreach ([5, 10, 20, 50] as $n): ?>
                            <option value="<?php echo $n; ?>"
                                <?php echo $r1Limit == $n ? 'selected' : ''; ?>>
                                <?php echo $n; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-run">Generate Report</button>
            </div>
        </form>

        <?php if ($r1Run): ?>
            <?php if (empty($r1Results)): ?>
                <p class="no-results">No published movies found in this date range.</p>
            <?php else: ?>
                <p class="summary">
                    Showing <b><?php echo count($r1Results); ?></b> movie(s)
                    published between <b><?php echo htmlspecialchars($r1From); ?></b>
                    and <b><?php echo htmlspecialchars($r1To); ?></b>.
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Creator</th>
                            <th>Year</th>
                            <th>Avg Rating</th>
                            <th>Ratings</th>
                            <th>Views</th>
                            <th>Published On</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($r1Results as $i => $row): ?>
                        <tr>
                            <td class="rank"><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['creator'] ?? '—'); ?></td>
                            <td><?php echo $row['release_year'] ?? '—'; ?></td>
                            <td><?php echo number_format($row['avg_rating'], 2); ?> ⭐</td>
                            <td><?php echo (int)$row['rating_count']; ?></td>
                            <td><?php echo (int)$row['view_count']; ?></td>
                            <td><?php echo htmlspecialchars($row['published_on'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- report 2: all movies by a specific creator -->
    <div class="report-card">
        <h3>🎬 Report 2 — Movies by Creator</h3>
        <p class="desc">Shows all movies (including drafts) submitted by a selected creator.</p>

        <form method="get">
            <input type="hidden" name="run_r2" value="1">
            <!-- carry report 1 state -->
            <?php if ($r1Run): ?>
                <input type="hidden" name="run_r1" value="1">
                <input type="hidden" name="r1_from"  value="<?php echo htmlspecialchars($r1From); ?>">
                <input type="hidden" name="r1_to"    value="<?php echo htmlspecialchars($r1To); ?>">
                <input type="hidden" name="r1_limit" value="<?php echo $r1Limit; ?>">
            <?php endif; ?>

            <div class="filter-row">
                <div class="fg">
                    <label for="r2_creator">Select creator</label>
                    <select id="r2_creator" name="r2_creator">
                        <option value="">— choose a creator —</option>
                        <?php foreach ($creators as $c): ?>
                            <option value="<?php echo $c['user_id']; ?>"
                                <?php echo $r2Creator == $c['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-run">Generate Report</button>
            </div>
        </form>

        <?php if ($r2Run): ?>
            <?php if ($r2Creator <= 0): ?>
                <p class="no-results">Please select a creator.</p>
            <?php elseif (empty($r2Results)): ?>
                <p class="no-results">No movies found for this creator.</p>
            <?php else: ?>
                <?php
                    // find the selected creator username for the summary line
                    $creatorName = '';
                    foreach ($creators as $c) {
                        if ($c['user_id'] == $r2Creator) {
                            $creatorName = $c['username'];
                            break;
                        }
                    }
                ?>
                <p class="summary">
                    Showing <b><?php echo count($r2Results); ?></b> movie(s)
                    by <b><?php echo htmlspecialchars($creatorName); ?></b>.
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Avg Rating</th>
                            <th>Ratings</th>
                            <th>Views</th>
                            <th>Published On</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($r2Results as $i => $row): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td>
                                <?php if ($row['is_published']): ?>
                                    <span class="badge badge-pub">Published</span>
                                <?php else: ?>
                                    <span class="badge badge-draft">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($row['avg_rating'], 2); ?> ⭐</td>
                            <td><?php echo (int)$row['rating_count']; ?></td>
                            <td><?php echo (int)$row['view_count']; ?></td>
                            <td><?php echo $row['published_on'] ? htmlspecialchars($row['published_on']) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
