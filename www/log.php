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
<h3>Device Notes</h3>
<?php
$log = get_value_from_users("log", $db);
print "<table>";
    print "<tr><th>Coordinates</th><th>Place</th><th></th></tr>";
    if ($log != '') {
   $log_array = explode(":", $log);
   sort ($log_array);
   $current_button1="Z";
   $current_button2="Z";
   $current_button3="Z";
   foreach ($log_array as $entry) {
      $entry_array = explode(',', $entry);
      $button1 = substr($entry_array[0], 1);
      $button2 = $entry_array[1];
      $button3 = substr($entry_array[2], 0, -1);
      $same_as_previous = 0;
      $text = '';

      if ($current_button1 == $button1 && $current_button2 == $button2 && $current_button3 == $button3) {
         $same_as_previous = 1;
      } else {
          $current_button1 = $button1;
          $current_button2 = $button2;
          $current_button3 = $button3;
      } 
      $location_id = get_location_from_coords($button1, $button2, $button3, $db);

      $text = get_value_for_location_id("text", $location_id, $db);

       if ($current_button1 != 'Z') {
           print "<tr><td>$current_button1, $current_button2, $current_button3</td>";
           print "<td>$text</td>";
           $era = get_value_for_location_id("era", $location_id, $db);
           print "<td>$era</td></tr>";
       }
   }
}
print "</table>";

print "<h3>Clues</h3>";
$clues = get_value_from_users("locationclues", $db);
if (!is_null($clues)) {
   $clue_array = explode(",", $clues);
   print "<ul>";
   foreach ($clue_array as $clue_id) {
   	   $clue = get_value_for_location_id("clue", $clue_id, $db);
       $present_day = get_value_for_location_id("present_day", $clue_id, $db);
       if ($present_day != 1) {
           $d1 = get_value_for_location_id("tm_coord_1", $clue_id, $db);
           $d2 = get_value_for_location_id("tm_coord_2", $clue_id, $db);
           $d3 = get_value_for_location_id("tm_coord_3", $clue_id, $db);
           print "<li>$clue ($d1, $d2, $d3)</li>";
       } else {
           $d1 = get_value_for_location_id("name", $clue_id, $db);
           print "<li>$clue (Present Day: $d1)</li>";
       }
   }
   print "</ul>";
}


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
