<?php 

require_once('./config/accesscontrol.php');

// Set up/check session and get database password etc.
require_once('./config/MySQL.php');
require_once('utilities.php');
session_start();
sessionAuthenticate(default_url());

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);

$uname = $_SESSION["loginUsername"];
$location = get_location($db);
?>
<html>
<head>
<title>Dr Who Game - Story Log</title>

<link rel="stylesheet" href="./styles/default.css" type="text/css">
</head>
<body>
<?php
    print_header_info_pages($db);
    ?>
<div class="main">
<h2>Log Book</h2>
<?php
$log = get_value_from_users("story_id_list", $db);
// print "<table>";
if ($log != '') {
    $log_array = explode(",", $log);
    sort ($log_array);
    foreach ($log_array as $entry) {
        $badge = get_value_for_story_id("badge", $entry, $db);
        $text = get_value_for_story_id("title", $entry, $db);
        print "<div class=story>";
        print "<img src=assets/$badge><br>";
        // print "<td>$text</td>";
        print "<form method=\"POST\" action=\"story.php\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$entry\">";
        print "<input type=\"submit\" value=\"View Full Logs\">";
        print "</form>";
        print "</div>";
        //print "</tr>";
     }
}
//print "</table>";


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
