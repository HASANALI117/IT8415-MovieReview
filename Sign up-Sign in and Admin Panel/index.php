<?php
include "Header.php";

$page_title = 'Login';

echo '<div id="content"><h1>Login</h1></div>';

echo '<div id="stylized" class="myform">
       <form action="index.php" method="post" id="login_form" name="login_form">
           <label>Email
           <span class="small">enter your email address</span>
           </label>
           <input type="text" name="Email" id="Email" value="" onblur="isValid(this);" />
           <label id="EmailErr" class="err"></label>

           <label>Password
           <span class="small">enter your password</span>
           </label>
           <input type="password" name="Password" id="Password" onblur="isValid(this);" />
           <label id="PasswordErr" class="err"></label>

           <button type="submit" id="sub" name="submit" value="Login" disabled>Log in</button>
           <input type ="hidden" name="submitted" value="TRUE">
           <p><a href="register.php">or register here</a></p>
         </form>';

if (isset($_POST['submitted'])) {
    require_once('LoginFunctions.php');

    list($check, $data) = checkLogin($_POST['Email'], $_POST['Password']);

    if ($check) {
        $_SESSION['Username'] = $data['Username'];
        $_SESSION['Role'] = $data['Role'];
        $_SESSION['Id'] = $data['UserId'];
        $_SESSION['Pwd'] = $data['Pwd'];

        $url = absolute_url('home.php');
        header("Location: $url");
        exit();
    } else {
        $errors = $data;
    }
}

echo'<div class="spacer"></div>';

if (!empty($errors)) {
    echo '<br/> <p class="error">The following errors occurred: <br />';

    foreach ($errors as $err) {
        echo "$err <br />";
    }

    echo '</p>';
}

echo '</div>';
?>

<script>
window.onload = init;

function init()
{
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
    if(document.getElementById('Email').value != '' &&
       document.getElementById('Password').value != '')
    {
        document.getElementById('sub').disabled = false;
    }
}
</script>

<?php
include "Footer.php";
?>
