<?php
require_once __DIR__ . '/../../includes/session.php';

// admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . app_url('index.php'));
    exit();
}

require_once __DIR__ . '/../../src/Database.php';

$db  = new Database();
$dbc = $db->getConnection();

$display = 10;

$sort = $_GET['sort'] ?? 'rd';
switch ($sort) {
    case 'un': $orderby = 'username ASC';   break;
    case 'em': $orderby = 'email ASC';      break;
    case 'ro': $orderby = 'role ASC';       break;
    case 'rd':
    default:   $orderby = 'created_at ASC'; break;
}

if (isset($_GET['p'])) {
    $pages = (int)$_GET['p'];
} else {
    $q   = "SELECT COUNT(user_id) FROM dbProj_users";
    $r   = mysqli_query($dbc, $q);
    $row = mysqli_fetch_array($r, MYSQLI_NUM);
    $records = $row[0];
    $pages   = ($records > $display) ? (int)ceil($records / $display) : 1;
}

$start = isset($_GET['s']) ? (int)$_GET['s'] : 0;

$q = "SELECT user_id, username, email, role, created_at FROM dbProj_users ORDER BY $orderby LIMIT $start, $display";
$r = mysqli_query($dbc, $q);

$page_title = 'Users — Admin';
$active_nav = 'admin';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="page-heading">
    <h1>Users</h1>
    <p>All registered users. Click a column to sort.</p>
  </div>

  <?php if ($r): ?>
    <div class="glass-table-wrap">
      <table class="glass-table">
        <thead>
          <tr>
            <th>Edit</th>
            <th><a href="/auth/view_users.php?sort=un">Username</a></th>
            <th><a href="/auth/view_users.php?sort=em">Email</a></th>
            <th><a href="/auth/view_users.php?sort=ro">Role</a></th>
            <th><a href="/auth/view_users.php?sort=rd">Registered</a></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_array($r)): ?>
            <tr>
              <td><a href="/auth/edit_user.php?id=<?= urlencode($row[0]) ?>" class="glass-btn glass-btn--sm">Edit</a></td>
              <td><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge-pill" style="background:var(--accent-soft);color:var(--accent-dk)"><?= htmlspecialchars(ucfirst($row[3]), ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php mysqli_free_result($r); ?>

    <?php if ($pages > 1):
        $currentpage = ($start / $display) + 1; ?>
      <nav class="pagination-row">
        <?php if ($currentpage != 1): ?>
          <a class="page-link-pill" href="/auth/view_users.php?s=<?= $start - $display ?>&p=<?= $pages ?>">Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a class="page-link-pill <?= $i == $currentpage ? 'active' : '' ?>" href="/auth/view_users.php?s=<?= $display * ($i - 1) ?>&p=<?= $pages ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($currentpage != $pages): ?>
          <a class="page-link-pill" href="/auth/view_users.php?s=<?= $start + $display ?>&p=<?= $pages ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="flash flash--error">There was an error loading users: <?= htmlspecialchars(mysqli_error($dbc), ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
