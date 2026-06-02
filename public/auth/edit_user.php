<?php

$page_title = 'Edit User';

include 'Header.php';

echo '<h1>Edit User</h1>';

require_once __DIR__ . '/../../src/Database.php';

$db = new Database();
$dbc = $db->getConnection();
$id=0;

if( isset($_GET['id']) )
{
    $id=$_GET['id'];
}
elseif(isset($_POST['id']))
{
    $id=$_POST['id'];
}
else
{
     echo '<p class="error"> Error has occured</p>';

     include 'Footer.php';

     exit();
}

require_once __DIR__ . '/../../src/User.php';

$user = new User();

$user->get($id);

if(isset($_POST['submitted']))
{
  $user->username = $_POST['username'];
  $user->email = $_POST['email'];
  $user->role = $user->sanitizeString($_POST['role']);

  $errors = $user->isValid();

  if(!empty($errors))
  {
      echo '<p class="error"> The following errors occurred: <br />';

      foreach($errors as $err)
      {
          echo "$err <br />";
      }

      echo '</p>';
  }
  else
  {
    if($user->save())
        echo('<h2>User details saved to DB</h2>');
  }
}
{
    echo '<form action="edit_user.php" method="post">
        <br />
        <h3>Edit User: '.$user->username.'</h3>
        <p><br />
           <p>Username      <input type="text" name="username" value="'.$user->username .'" /></p>
           <p>Email Address <input type="text" name="email" value="'.$user->email .'"/></p>
           <p>Role          <input type="text" name="role" value="'.$user->role .'"/></p><br/>
        </p>
        <p><input type="submit" name="submit" value="update" /></p>

         <input type ="hidden" name="submitted" value="TRUE">
         <input type ="hidden" name="id" value="' . $id . '"/>
         </form>';
}

include 'Footer.php';
?>
