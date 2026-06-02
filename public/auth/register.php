<?php
require_once __DIR__ . '/../../includes/session.php';

$errors     = [];
$registered = false;
$reg_name   = '';

// Process registration before output.
if (isset($_POST['submitted'])) {
    require_once __DIR__ . '/../../src/User.php';

    $user = new User();
    $user->username = trim($_POST['Username']);
    $user->email    = trim($_POST['Email']);
    $user->password = trim($_POST['Password']);
    $user->role     = 'viewer';

    $errors = $user->isValid();

    if (empty($errors) && $user->save()) {
        $registered = true;
        $reg_name   = $user->username;
    }
}

$page_title = 'Register — MovieReview';
require __DIR__ . '/../../includes/header.php';
?>

  <div class="auth-wrap">
    <div class="auth-card">

      <?php if ($registered): ?>
        <h1>Thank you</h1>
        <p class="auth-sub"><strong><?= htmlspecialchars($reg_name) ?></strong>, you are now registered.</p>
        <a href="/auth/login.php" class="glass-btn glass-btn--accent" style="width:100%">Go to login</a>
      <?php else: ?>
        <h1>Create account</h1>
        <p class="auth-sub">Join to rate and review movies.</p>

        <?php if (!empty($errors)): ?>
          <div class="flash flash--error">
            <?php foreach ($errors as $msg) echo htmlspecialchars($msg) . '<br>'; ?>
          </div>
        <?php endif; ?>

        <form action="/auth/register.php" method="post" name="reg_form">
          <div class="auth-field">
            <label class="form-label" for="Username">Username</label>
            <input type="text" name="Username" id="Username" class="form-control" value="" onblur="isValid(this);">
            <label id="UsernameErr" class="err-label"></label>
          </div>
          <div class="auth-field">
            <label class="form-label" for="Email">Email</label>
            <input type="text" name="Email" id="Email" class="form-control" value="" onblur="isValid(this);">
            <label id="EmailErr" class="err-label"></label>
          </div>
          <div class="auth-field">
            <label class="form-label" for="Password">Password</label>
            <input type="password" name="Password" id="Password" class="form-control" value="" onblur="isValid(this);">
            <label id="PasswordErr" class="err-label"></label>
          </div>
          <button type="submit" id="sub" class="glass-btn glass-btn--accent" style="width:100%" disabled>Register</button>
          <input type="hidden" name="submitted" value="1">
        </form>

        <div class="auth-foot">Already have an account? <a href="/auth/login.php">Log in</a></div>
      <?php endif; ?>

    </div>
  </div>

<?php if (!$registered): ?>
<script>
window.onload = init;

function init() {
    document.getElementById('Username').onkeyup = handleKeyPress;
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
    if (document.getElementById('Username').value != '' &&
        document.getElementById('Email').value != '' &&
        document.getElementById('Password').value != '') {
        document.getElementById('sub').disabled = false;
    }
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
