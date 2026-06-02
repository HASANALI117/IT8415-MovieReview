<?php
// admin_users.php
// Admin Panel: view and manage all registered users

session_start();
require_once 'DBconn.php';

// access control, admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management – Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body       { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header     { background: #1a1a2e; color: #fff; padding: 16px 24px;
                     display: flex; justify-content: space-between; align-items: center; }
        header a   { color: #e0b0ff; text-decoration: none; margin-left: 16px; }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px 60px; }
        h2         { color: #1a1a2e; margin-bottom: 4px; }
        .subtitle  { color: #666; font-size: 14px; margin-bottom: 24px; }

        /* message */
        .msg-success { background: #d4edda; color: #155724; padding: 12px 16px;
                       border-radius: 6px; margin-bottom: 20px; }
        .msg-warn    { background: #fff3cd; color: #856404; padding: 12px 16px;
                       border-radius: 6px; margin-bottom: 20px; }

        /* filters */
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
        tr.inactive td   { opacity: 0.6; }

        /* role badge */
        .badge        { display: inline-block; padding: 3px 10px; border-radius: 12px;
                        font-size: 12px; font-weight: bold; }
        .badge-admin  { background: #f3e5f5; color: #7b1fa2; }
        .badge-creator{ background: #e3f2fd; color: #1565c0; }
        .badge-viewer { background: #e8f5e9; color: #2e7d32; }

        /* status badge */
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }

        /* you badge */
        .you-tag { background: #fff3cd; color: #856404; font-size: 11px;
                   padding: 2px 7px; border-radius: 10px; margin-left: 6px; }

        /* action buttons */
        .btn          { display: inline-block; padding: 6px 12px; border-radius: 5px;
                        font-size: 12px; cursor: pointer; border: none; margin-right: 4px; }
        .btn-deact    { background: #fd7e14; color: #fff; }
        .btn-act      { background: #28a745; color: #fff; }

        /* inline role form */
        .role-form    { display: inline-flex; gap: 6px; align-items: center; }
        .role-form select { padding: 5px 8px; border-radius: 5px; border: 1px solid #ccc;
                            font-size: 12px; }
        .btn-role     { background: #6f42c1; color: #fff; padding: 5px 10px; }

        .no-results { text-align: center; color: #888; padding: 50px;
                      font-style: italic; }

        /* pagination */
        .pagination  { display: flex; gap: 6px; justify-content: center; margin-top: 24px; }
        .page-btn    { padding: 7px 13px; border-radius: 5px; border: 1px solid #ccc;
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
    <h2>User Management</h2>
    <p class="subtitle">View, activate, deactivate, and change roles for all users.</p>

    <?php if ($message): ?>
        <div class="<?php echo str_contains($message, 'cannot') ? 'msg-warn' : 'msg-success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- filters bar -->
    <form method="get" style="margin:0">
        <div class="filters">
            <input type="text" name="search"
                   placeholder="Search by username or email..."
                   value="<?php echo htmlspecialchars($search); ?>">

            <a href="admin_users.php"
               class="filter-btn <?php echo $roleFilter === 'all'     ? 'active' : ''; ?>">
                All
            </a>
            <a href="admin_users.php?role=viewer&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $roleFilter === 'viewer'  ? 'active' : ''; ?>">
                Viewers
            </a>
            <a href="admin_users.php?role=creator&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $roleFilter === 'creator' ? 'active' : ''; ?>">
                Creators
            </a>
            <a href="admin_users.php?role=admin&search=<?php echo urlencode($search); ?>"
               class="filter-btn <?php echo $roleFilter === 'admin'   ? 'active' : ''; ?>">
                Admins
            </a>

            <button type="submit" class="filter-btn">Search</button>

            <span class="total-count">
                <?php echo $totalItems; ?> user(s) found
            </span>
        </div>
    </form>

    <?php if (empty($users)): ?>
        <p class="no-results">No users found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Movies</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $isSelf = ($u['user_id'] === $_SESSION['user_id']);
            ?>
                <tr class="<?php echo !$u['is_active'] ? 'inactive' : ''; ?>">
                    <td><?php echo $u['user_id']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($u['username']); ?>
                        <?php if ($isSelf): ?>
                            <span class="you-tag">You</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $u['role']; ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo getMovieCountByUser($u['user_id']); ?></td>
                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($isSelf): ?>
                            <span style="color:#999;font-size:12px">—</span>
                        <?php else: ?>
                            <!-- activate / deactivate -->
                            <?php if ($u['is_active']): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="user_id"
                                           value="<?php echo $u['user_id']; ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button class="btn btn-deact"
                                            onclick="return confirm('Deactivate this user?')">
                                        Deactivate
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="user_id"
                                           value="<?php echo $u['user_id']; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button class="btn btn-act">Activate</button>
                                </form>
                            <?php endif; ?>

                            <!-- change role inline -->
                            <form method="post" class="role-form" style="display:inline-flex">
                                <input type="hidden" name="user_id"
                                       value="<?php echo $u['user_id']; ?>">
                                <input type="hidden" name="action" value="set_role">
                                <select name="new_role">
                                    <option value="viewer"
                                        <?php echo $u['role'] === 'viewer'  ? 'selected' : ''; ?>>
                                        Viewer
                                    </option>
                                    <option value="creator"
                                        <?php echo $u['role'] === 'creator' ? 'selected' : ''; ?>>
                                        Creator
                                    </option>
                                    <option value="admin"
                                        <?php echo $u['role'] === 'admin'   ? 'selected' : ''; ?>>
                                        Admin
                                    </option>
                                </select>
                                <button class="btn btn-role"
                                        onclick="return confirm('Change this user\'s role?')">
                                    Set
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?php echo $p;
                                         echo $search    ? '&search='  . urlencode($search)    : '';
                                         echo $roleFilter !== 'all' ? '&role=' . $roleFilter   : ''; ?>"
                       class="page-btn <?php echo $p === $currentPage ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>
