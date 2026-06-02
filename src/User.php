<?php
class User {
    public $userId = null;
    public $username;
    public $email;
    public $password;
    public $role;

    public $errorMsg;
    public $dbc = null;

    private $aesKey = 'your_secret_key';

    public function __construct() {
        $this->getDBConnection();
    }

    private function getDBConnection() {
        require_once __DIR__ . '/Database.php';
        try {
            if ($this->dbc == null) {
                $db = new Database();
                $this->dbc = $db->getConnection();
                $this->dbc->set_charset("utf8mb4");
            }
            return $this->dbc;
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage();
            return null;
        }
    }

    public function get($userId) {
        if ($this->getDBConnection()) {
            $q = "SELECT user_id, username, email,
                         AES_DECRYPT(password_hash, ?) AS password_hash,
                         role
                  FROM dbProj_users WHERE user_id=?";
            $stmt = $this->dbc->prepare($q);
            $stmt->bind_param('si', $this->aesKey, $userId);
            if ($stmt->execute()) {
                $stmt->store_result();
                $stmt->bind_result(
                    $this->userId,
                    $this->username,
                    $this->email,
                    $this->password,
                    $this->role
                );
                $stmt->fetch();
                $stmt->close();
            } else {
                $this->displayError($q);
            }
        }
        return false;
    }

    public function save() {
        if ($this->getDBConnection()) {
            $this->username = $this->sanitizeString($this->username);
            $this->email    = $this->sanitizeString($this->email);
            $this->role     = $this->sanitizeString($this->role);

            if ($this->userId == null) {
                $q = "INSERT INTO dbProj_users(username, email, password_hash, role)
                      VALUES(?,?,AES_ENCRYPT(?, ?),?)";
                $stmt = $this->dbc->prepare($q);
                $stmt->bind_param(
                    'sssss',
                    $this->username,
                    $this->email,
                    $this->password,
                    $this->aesKey,
                    $this->role
                );
            } else {
                $q = "UPDATE dbProj_users
                      SET username=?, email=?,
                          password_hash=AES_ENCRYPT(?, ?), role=?
                      WHERE user_id=?";
                $stmt = $this->dbc->prepare($q);
                $stmt->bind_param(
                    'sssssi',
                    $this->username,
                    $this->email,
                    $this->password,
                    $this->aesKey,
                    $this->role,
                    $this->userId
                );
            }

            if ($stmt && $stmt->execute()) {
                if ($this->userId === null) {
                    $this->userId = $this->dbc->insert_id;   // expose new id for auto-login
                }
                return true;
            } else {
                $this->displayError($q);
                return false;
            }
        }
        return false;
    }

    function sanitizeString($var) {
        $var = strip_tags($var);
        $var = htmlentities($var);
        $var = stripslashes($var);
        return mysqli_real_escape_string($this->dbc, $var);
    }

    public function isValid() {
        $errors = array();
        if (empty($this->username)) $errors[] = 'You must enter username';
        if (empty($this->email))    $errors[] = 'You must enter email';
        else if (!$this->validEmail()) $errors[] = 'This email address is already registered';
        if (empty($this->password)) $errors[] = 'You must enter password';
        return $errors;
    }

    public function validEmail() {
        if ($this->getDBConnection()) {
            $q = "SELECT user_id FROM dbProj_users WHERE email=?";
            $stmt = $this->dbc->prepare($q);
            $stmt->bind_param('s', $this->email);
            $stmt->execute();
            $stmt->bind_result($id);
            while ($stmt->fetch()) {
                if ($id != $this->userId) return false;
            }
            $stmt->close();
        }
        return true;
    }

    private function displayError($q) {
        echo '<p class="error">A database error occurred - contact the admin</p>';
        echo '<p class="error">' . $q . '</p>';
        echo '<p class="error">' . mysqli_error($this->dbc) . '</p>';
    }
}
?>
