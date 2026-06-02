<?php
require_once __DIR__ . '/../../includes/session.php';

// admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /index.php');
    exit();
}

require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/User.php';

$id = 0;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

$page_title = 'Edit User — Admin';
$active_nav = 'admin';
$container_class = 'app-container--narrow';
require __DIR__ . '/../../includes/header.php';

if ($id <= 0) {
    echo '<div class="flash flash--error">An error has occurred — no user specified.</div>';
    require __DIR__ . '/../../includes/footer.php';
    exit();
}

$user = new User();
$user->get($id);

$errors = [];
$saved  = false;

if (isset($_POST['submitted'])) {
    $user->username = $_POST['username'];
    $user->email    = $_POST['email'];
    $user->role     = $user->sanitizeString($_POST['role']);

    $errors = $user->isValid();
    if (empty($errors) && $user->save()) {
        $saved = true;
    }
}
?>

  <div class="page-heading">
    <h1>Edit User</h1>
    <p>Update username, email, or role.</p>
  </div>

  <?php if ($saved): ?>
    <div class="flash flash--ok">User details saved.</div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="flash flash--error">
      <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
    </div>
  <?php endif; ?>

  <form action="/auth/edit_user.php" method="post" class="glass-panel form-card">
    <h3>Editing: <?= htmlspecialchars($user->username) ?></h3>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user->username) ?>">
    </div>
    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="text" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email) ?>">
    </div>
    <div class="form-group">
      <label for="role">Role</label>
      <select id="role" name="role" class="form-select">
        <?php foreach (['viewer', 'creator', 'admin'] as $r): ?>
          <option value="<?= $r ?>" <?= $user->role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;gap:12px">
      <button type="submit" name="submit" value="update" class="glass-btn glass-btn--accent">Update</button>
      <a href="/auth/view_users.php" class="glass-btn">Back</a>
    </div>

    <input type="hidden" name="submitted" value="TRUE">
    <input type="hidden" name="id" value="<?= $id ?>">
  </form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
