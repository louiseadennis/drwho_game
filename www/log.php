<?php 

require_once('./config/accesscontrol.php');

// Set up/check session and get database password etc.
require_once('./config/MySQL.php');
require_once('utilities.php');
session_start();
sessionAuthenticate();

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);

$uname = $_SESSION["loginUsername"];
$location = get_location($db);
?>
<html>
<head>
<title>Dr Who Game - Log Book</title>

<link rel="stylesheet" href="./styles/default.css" type="text/css">
</head>
<body>
<div class="main">
<p><form method="POST" action="main.php">
<input type="hidden" name="location_id" value="
<?php
echo $location
?>
">
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game">
</form>
</p>
<h2>Log Book</h2>
<?php
$log = get_value_from_users("log", $db);
print "<table>";
print "<tr><th>Coordinates</th><th>Place</th><th></th></tr>";
if ($log != '') {
    $log_array = explode(":", $log);
    sort ($log_array);
    foreach ($log_array as $entry) {
        $entry_array = explode(',', $entry);
        // removing the (
        $button1 = substr($entry_array[0], 1);
        $button2 = $entry_array[1];
        $button3 = $entry_array[2];
        // removing the )
        $button4 = substr($entry_array[3], 0, -1);;
        $location_id = get_location_from_coords($button1, $button2, $button3, $button4, $db);

        $text = get_value_for_location_id("text", $location_id, $db);

        print "<tr><td>$button1, $button2, $button3, $button4</td>";
        print "<td>$text</td>";
        $log_string = "location" . $location_id . "_log.png";
        print "<td><img src=assets/locations/$log_string></td>";
    }
}
print "</table>";


?>
<p><form method="POST" action="main.php">
<input type="hidden" name="location_id" value="
<?php
echo $location
?>
">
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game">
</form>
</p>
</div>
</body>
</head>
</html>
