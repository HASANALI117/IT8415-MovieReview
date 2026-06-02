<?php
include 'Header.php';

echo '<h1> Users </h1>';

include "mysqli_connect.php";

$db = new Database();
$dbc = $db->getConnection();

$display = 10;
$pages;

$sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'rd';

switch ($sort) {
    case 'un':
        $orderby = 'username ASC';
        break;
    case 'em':
        $orderby = 'email ASC';
        break;
    case 'ro':
        $orderby = 'role ASC';
        break;
    case 'rd':
        $orderby = 'created_at ASC';
        break;
    default:
        $orderby = 'created_at ASC';
        break;
}

if (isset($_GET['p'])) {
    $pages = (int)$_GET['p'];
} else {
    $q = "SELECT COUNT(user_id) FROM dbProj_users";
    $r = mysqli_query($dbc, $q);
    $row = mysqli_fetch_array($r, MYSQLI_NUM);
    $records = $row[0];

    if ($records > $display)
        $pages = ceil($records / $display);
    else
        $pages = 1;
}

if (isset($_GET['s']))
    $start = (int)$_GET['s'];
else
    $start = 0;

$q = "SELECT user_id, username, email, role, created_at FROM dbProj_users ORDER BY $orderby LIMIT $start, $display";
$r = mysqli_query($dbc, $q);

if ($r) {
    echo '<br />';

    echo '<table align="center" cellspacing="2" cellpadding="4" width="75%">';
    echo '<tr bgcolor="#87CEEB">
            <td><b>Edit</b></td>
            <td><b><a href="view_users.php?sort=un">Username</a></b></td>
            <td><b><a href="view_users.php?sort=em">Email</a></b></td>
            <td><b><a href="view_users.php?sort=ro">Role</a></b></td>
            <td><b><a href="view_users.php?sort=rd">Registered</a></b></td>
          </tr>';

    $bg = '#eeeeee';

    while ($row = mysqli_fetch_array($r)) {
        $bg = ($bg == '#eeeeee' ? '#ffffff' : '#eeeeee');

        echo '<tr bgcolor="' . $bg . '">
                <td><a href="edit_user.php?id=' . urlencode($row[0]) . '">Edit</a></td>
                <td>' . htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8') . '</td>
              </tr>';
    }

    echo '</table>';
} else {
    echo '<p class="error">' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="error"> Oh dear. There was an error</p>';
    echo '<p class="error">' . htmlspecialchars(mysqli_error($dbc), ENT_QUOTES, 'UTF-8') . '</p>';
}

mysqli_free_result($r);

if ($pages > 1) {
    echo '<br /><p>';

    $currentpage = ($start / $display) + 1;

    if ($currentpage != 1) {
        echo '<a href="view_users.php?s=' . ($start - $display) . '&p=' . $pages . '">&nbspPrevious&nbsp</a>';
    }

    for ($i = 1; $i <= $pages; $i++) {
        if ($i != $currentpage) {
            echo '<a href="view_users.php?s=' . (($display * ($i - 1))) . '&p=' . $pages . '">&nbsp' . $i . '&nbsp</a>';
        }
    }

    if ($currentpage != $pages) {
        echo '<a href="view_users.php?s=' . ($start + $display) . '&p=' . $pages . '">&nbspNext&nbsp</a>';
    }

    echo '</p>';
}

include 'Footer.php';
?>
