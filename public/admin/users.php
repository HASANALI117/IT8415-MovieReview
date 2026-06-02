<?php
// admin/users.php
// Admin Panel: view and manage all registered users

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../src/Database.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$message = '';

// -------------------------------------------------------
// helper functions using prepared statements
// -------------------------------------------------------

// get all users with optional search term
function getUsers($search = '') {
    $conn = getConnection();

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare(
            "SELECT user_id, username, email, role, is_active, created_at
               FROM dbProj_users
              WHERE username LIKE ? OR email LIKE ?
              ORDER BY created_at DESC"
        );
        $stmt->bind_param("ss", $like, $like);
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, username, email, role, is_active, created_at
               FROM dbProj_users
              ORDER BY created_at DESC"
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users  = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $users;
}

// toggle a user's is_active status
function setUserActive($user_id, $active) {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "UPDATE dbProj_users SET is_active = ? WHERE user_id = ?"
    );
    $stmt->bind_param("ii", $active, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

// change a user's role
function setUserRole($user_id, $role) {
    $allowed = ['viewer', 'creator', 'admin'];
    if (!in_array($role, $allowed)) return false;

    $conn = getConnection();
    $stmt = $conn->prepare(
        "UPDATE dbProj_users SET role = ? WHERE user_id = ?"
    );
    $stmt->bind_param("si", $role, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

// get movie count per user for display
function getMovieCountByUser($user_id) {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM dbProj_movies WHERE created_by = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    return (int)$cnt;
}

// handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // prevent admin from acting on their own account
    if ($uid === $_SESSION['user_id']) {
        $message = 'You cannot modify your own account from here.';
    } elseif ($uid > 0) {
        if ($action === 'deactivate') {
            setUserActive($uid, 0);
            $message = 'User deactivated.';
        } elseif ($action === 'activate') {
            setUserActive($uid, 1);
            $message = 'User activated.';
        } elseif ($action === 'set_role') {
            $newRole = $_POST['new_role'] ?? '';
            setUserRole($uid, $newRole);
            $message = 'User role updated.';
        }
    }
}

// search and filter
$search      = trim($_GET['search'] ?? '');
$roleFilter  = $_GET['role']        ?? 'all';

$users = getUsers($search);

// apply role filter after fetching
if ($roleFilter !== 'all') {
    $users = array_filter($users, fn($u) => $u['role'] === $roleFilter);
}

// pagination, 10 users per page
$perPage     = 10;
$totalItems  = count($users);
$totalPages  = max(1, ceil($totalItems / $perPage));
$currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$offset      = ($currentPage - 1) * $perPage;
$users       = array_slice(array_values($users), $offset, $perPage);

$page_title = 'User Management — Admin Panel';
$active_nav = 'admin';
$admin_tab  = 'users';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin_nav.php';

$role_badge = ['admin' => 'badge-pill--danger', 'creator' => '', 'viewer' => 'badge-pill--ok'];
?>

  <div class="page-heading">
    <h1>User Management</h1>
    <p>View, activate, deactivate, and change roles for all users.</p>
  </div>

  <?php if ($message): ?>
    <div class="flash flash--<?= str_contains($message, 'cannot') ? 'error' : 'ok' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- filters bar -->
  <form method="get" class="filter-bar" action="/admin/users.php">
    <input type="text" name="search" class="form-control" placeholder="Search by username or email…"
           value="<?= htmlspecialchars($search) ?>">
    <a href="/admin/users.php" class="glass-btn glass-btn--sm <?= $roleFilter === 'all' ? 'glass-btn--accent' : '' ?>">All</a>
    <a href="/admin/users.php?role=viewer&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $roleFilter === 'viewer' ? 'glass-btn--accent' : '' ?>">Viewers</a>
    <a href="/admin/users.php?role=creator&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $roleFilter === 'creator' ? 'glass-btn--accent' : '' ?>">Creators</a>
    <a href="/admin/users.php?role=admin&search=<?= urlencode($search) ?>" class="glass-btn glass-btn--sm <?= $roleFilter === 'admin' ? 'glass-btn--accent' : '' ?>">Admins</a>
    <button type="submit" class="glass-btn glass-btn--sm glass-btn--accent">Search</button>
    <span style="margin-left:auto;align-self:center;color:var(--ink-soft);font-size:.85rem">
      <?= $totalItems ?> user(s) found
    </span>
  </form>

  <?php if (empty($users)): ?>
    <div class="empty-state"><h3>No users found.</h3></div>
  <?php else: ?>
    <div class="glass-table-wrap">
      <table class="glass-table">
        <thead>
          <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Movies</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u):
            $isSelf = ($u['user_id'] === $_SESSION['user_id']);
        ?>
          <tr style="<?= !$u['is_active'] ? 'opacity:.6' : '' ?>">
            <td><?= $u['user_id'] ?></td>
            <td>
              <?= htmlspecialchars($u['username']) ?>
              <?php if ($isSelf): ?><span class="badge-pill badge-pill--draft">You</span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge-pill <?= $role_badge[$u['role']] ?? '' ?>" <?= ($role_badge[$u['role']] ?? '') === '' ? 'style="background:var(--accent-soft);color:var(--accent-dk)"' : '' ?>><?= ucfirst($u['role']) ?></span></td>
            <td>
              <?php if ($u['is_active']): ?>
                <span class="badge-pill badge-pill--ok">Active</span>
              <?php else: ?>
                <span class="badge-pill badge-pill--danger">Inactive</span>
              <?php endif; ?>
            </td>
            <td><?= getMovieCountByUser($u['user_id']) ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td style="white-space:nowrap">
              <?php if ($isSelf): ?>
                <span style="color:var(--ink-faint);font-size:12px">—</span>
              <?php else: ?>
                <?php if ($u['is_active']): ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button class="glass-btn glass-btn--sm" onclick="return confirm('Deactivate this user?')">Deactivate</button>
                  </form>
                <?php else: ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action" value="activate">
                    <button class="glass-btn glass-btn--sm">Activate</button>
                  </form>
                <?php endif; ?>

                <form method="post" style="display:inline-flex;gap:6px;align-items:center">
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                  <input type="hidden" name="action" value="set_role">
                  <select name="new_role" class="glass-select" style="width:auto;padding:.3rem .5rem;font-size:.8rem">
                    <option value="viewer"  <?= $u['role'] === 'viewer'  ? 'selected' : '' ?>>Viewer</option>
                    <option value="creator" <?= $u['role'] === 'creator' ? 'selected' : '' ?>>Creator</option>
                    <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                  </select>
                  <button class="glass-btn glass-btn--sm glass-btn--accent" onclick="return confirm('Change this user\'s role?')">Set</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="pagination-row">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $roleFilter !== 'all' ? '&role=' . $roleFilter : '' ?>"
             class="page-link-pill <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
