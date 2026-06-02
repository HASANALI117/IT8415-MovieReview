<?php

include 'Header.php';
?>

<h1>User Registration</h1>
<div id="stylized" class="myform">
  <form action="register.php" method="post" name="reg_form">
     <fieldset>
            <label><b>Enter Username</b></label>
                <input type="text" name="Username" id="Username" size="20" value="" onblur="isValid(this);" />
                <label id="UsernameErr" class="err"></label>
            <label><b>Enter Email</b></label>
                <input type="text" name="Email" id="Email" size="50" value="" onblur="isValid(this);" />
                <label id="EmailErr" class="err"></label>
            <label><b>Enter Password</b></label>
                <input type="password" name="Password" id="Password" size="10" value="" onblur="isValid(this);" />
                <label id="PasswordErr" class="err"></label>
            <div align="center">
                  <input type="submit" id="sub" value="Register" disabled />
            </div>
            <input type="hidden" name="submitted" value="1" />
     </fieldset>
  </form>
  <div class="spacer"></div>

<script>
window.onload = init;

function init()
{
    var Username = document.getElementById('Username');
    Username.onkeyup = handleKeyPress;

    var Email = document.getElementById('Email');
    Email.onkeyup = handleKeyPress;

    var Password = document.getElementById('Password');
    Password.onkeyup = handleKeyPress;
}

function handleKeyPress(eventObj)
{
    var fld = eventObj.target;
    var key = eventObj.key;
    if(key != 'Tab')
        isValid(fld);
}

function isValid(obj)
{
    var errField = obj.id + 'Err';
    var valid = false;

    var value = obj.value.trim();

    if(value == '')
    {
        obj.style.backgroundColor = "yellow";
        document.getElementById(errField).innerHTML = obj.id + ' field may not be blank';
        document.getElementById('sub').disabled = true;
    }
    else
    {
        obj.style.backgroundColor = "#FFFFFF";
        document.getElementById(errField).innerHTML = '';
        valid = true;
        enableButton();
    }

    return valid;
}

function enableButton()
{
    if(document.getElementById('Username').value != '' &&
       document.getElementById('Email').value != '' &&
       document.getElementById('Password').value != '')
    {
        document.getElementById('sub').disabled = false;
    }
}
</script>

<?php
if (isset($_POST['submitted'])) {
    require_once __DIR__ . '/../../src/User.php';

    $user = new User();
    $user->username = trim($_POST['Username']);
    $user->email = trim($_POST['Email']);
    $user->password = trim($_POST['Password']);
    $user->role = 'viewer';

    $errors = $user->isValid();

    if (empty($errors))
    {
        if ($user->save()) {
            echo "<h1> Thankyou </h1><p>$user->username you are now registered</p>".'<a href="login.php"> go to login';
        }
    }
    else
    {
        echo '<p class="error"> Error </p>';

        foreach ($errors as $msg)
            echo " - $msg<br /> ";
    }
}
?>
</div>
<?php
include 'Footer.php';
?>
