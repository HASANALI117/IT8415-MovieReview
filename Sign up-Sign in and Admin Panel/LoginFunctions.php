<?php

function absolute_url($page = 'index.php')
{
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $url = rtrim($url, '/\\');
    $url .= '/' . $page;
    return $url;
}

function checkLogin($email = '', $password = '')
{
    $errors = array();

    $email = trim($email);
    $password = trim($password);

    if (empty($email)) {
        $errors[] = 'You must enter an email';
    }

    if (empty($password)) {
        $errors[] = 'You must enter a password';
    }

    if (empty($errors)) {
        include 'mysqli_connect.php';

        $db = new Database();
        $dbc = $db->getConnection();
        $dbc->set_charset("utf8mb4");
        $aesKey = 'your_secret_key';

        $q = "SELECT user_id, email, username, role,
                     CAST(AES_DECRYPT(password_hash, ?) AS CHAR) AS password_hash
              FROM dbProj_users
              WHERE email = ?";

        $stmt = $dbc->prepare($q);
        $stmt->bind_param('ss', $aesKey, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $stmt->bind_result($userId, $dbEmail, $username, $role, $dbPassword);
            $stmt->fetch();

            if ($password === $dbPassword) {

                $row = array(
                    'UserId'   => $userId,
                    'Email'    => $dbEmail,
                    'Username' => $username,
                    'Role'     => $role,
                    'Pwd'      => $dbPassword
                );

                return array(true, $row);

            } else {
                $errors[] = 'Passwords do not match';
            }

        } else {
            $errors[] = 'No account found with that email';
        }

        $stmt->close();
    }

    return array(false, $errors);
}

?>
