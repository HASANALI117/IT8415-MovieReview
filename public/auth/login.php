<?php
require_once __DIR__ . '/../../includes/session.php';

$errors = [];

// Process login BEFORE any output so the redirect header works.
if (isset($_POST['submitted'])) {
    require_once __DIR__ . '/../../src/Auth.php';

    list($check, $data) = checkLogin($_POST['Email'], $_POST['Password']);

    if ($check) {
        // Single app-wide session convention (lowercase): every guard across
        // admin/*, creator/*, and the M3 pages checks these exact keys.
        $_SESSION['user_id']  = $data['UserId'];
        $_SESSION['username'] = $data['Username'];
        $_SESSION['role']     = $data['Role'];

        header('Location: ' . app_url('index.php'));
        exit();
    } else {
        $errors = $data;
    }
}

$page_title = 'Login — MovieReview';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="auth-sub">Log in to rate and review movies.</p>

      <?php if (!empty($errors)): ?>
        <div class="flash flash--error">
          <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
        </div>
      <?php endif; ?>

      <form action="/auth/login.php" method="post" id="login_form" name="login_form">
        <div class="auth-field">
          <label class="form-label" for="Email">Email</label>
          <input type="text" name="Email" id="Email" class="form-control" value="" onblur="isValid(this);">
          <label id="EmailErr" class="err-label"></label>
        </div>

        <div class="auth-field">
          <label class="form-label" for="Password">Password</label>
          <input type="password" name="Password" id="Password" class="form-control" onblur="isValid(this);">
          <label id="PasswordErr" class="err-label"></label>
        </div>

        <button type="submit" id="sub" name="submit" value="Login" class="glass-btn glass-btn--accent" style="width:100%" disabled>Log in</button>
        <input type="hidden" name="submitted" value="TRUE">
      </form>

      <div class="auth-foot">No account yet? <a href="/auth/register.php">Register here</a></div>
    </div>
  </div>

<script>
window.onload = init;

function init() {
    document.getElementById('Email').onkeyup = handleKeyPress;
    document.getElementById('Password').onkeyup = handleKeyPress;
}

function handleKeyPress(eventObj) {
    if (eventObj.key != 'Tab') isValid(eventObj.target);
}

function isValid(obj) {
    var errField = obj.id + 'Err';
    if (obj.value.trim() == '') {
        obj.classList.add('error-field');
        document.getElementById(errField).innerHTML = obj.id + ' field may not be blank';
        document.getElementById('sub').disabled = true;
        return false;
    }
    obj.classList.remove('error-field');
    document.getElementById(errField).innerHTML = '';
    enableButton();
    return true;
}

function enableButton() {
    if (document.getElementById('Email').value != '' &&
        document.getElementById('Password').value != '') {
        document.getElementById('sub').disabled = false;
    }
}
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
