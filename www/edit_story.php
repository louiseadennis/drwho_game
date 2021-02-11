<?php

require_once('./config/accesscontrol.php');

// Set up/check session and get database password etc.
require_once('./config/MySQL.php');
require_once('utilities.php');
session_start();
sessionAuthenticate(default_url());

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
if ($db -> connect_errno > 0) {
   die('Unable to connect to database [' . $mysql_host . $mysql_user .  $mysql_password . $mysql_database . $db->connect_error . ']');
   }
    
$story_id = mysqlclean($_POST, "story_id", 15, $db);
$story = get_value_for_story_id("title", $story_id, $db);
$task = mysqlclean($_POST, "task", 3000, $db);
    
if ($task == "delete_event") {
    $story_number_id = mysqlclean($_POST, "story_number_id", 15, $db);
    //print "deleting event $story_number_id";
    
    $sql = "DELETE FROM story_events WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
    if (!$result = $db->query($sql))
        showerror($db);
    $sql = "DELETE FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id}'";
    if (!$result = $db->query($sql))
        showerror($db);
}
    
if ($task == "add_event") {
    $location_id = mysqlclean($_POST, "location_id", 15, $db);
    $story_number_id = mysqlclean($_POST, "story_number_id", 15, $db);
    $sql = "INSERT story_events (story_id, story_number_id, text, event_location, success_fail, description, ally_id_list, critter_id_list, modifier_id_list, story_modifier_id_list) VALUES ('{$story_id}', '{$story_number_id}', 'No Description', '{$location_id}', 1, 'No Description', '', '', '', '')";
    if (!$result = $db->query($sql))
            showerror($db);
    }
    
if ($task == "make_initial") {
    $story_number_id = mysqlclean($_POST, "story_number_id", 15, $db);
    $location_id = mysqlclean($_POST, "location_id", 15, $db);
    // $old_initial = mysqlclean($_POST, "old_initial", 15, $db);
    $sql = "UPDATE story_locations SET default_initial = '{$story_number_id}' WHERE  story_id = '{$story_id}' AND location_id = '{$location_id}'";
    if (!$result = $db->query($sql))
            showerror($db);
    }

    
?>
<html>
<head>
<title>Dr Who Game - Edit
<?php
    echo $story;
?>
</title>

<link rel="stylesheet" href="./styles/default.css?v=1" type="text/css">
</head>
<body>
<?php
    print_header_info_pages($db);
    ?>



<div class=main style="padding:1em">

<?php
    print "<h1>$story</h1>";

    $automaton = new story_automaton($story_id, $db);
    $sql = "SELECT locations from stories where story_id='{$story_id}'";
    $story_locations = select_sql_column($sql, "locations", $db);
    $location_array = explode(",", $story_locations);
    $event_count = 0;
    foreach ($location_array as $location) {
        $sql = "SELECT name from locations where location_id='{$location}'";
        $name = select_sql_column($sql, "name", $db);
        print "<h3>$name</h3>";
        
        $initial_event = get_initial_event($story_id, $location, $db);
        // $not_presentinitial_event = get_not_present_initial_event($story_id, $location, $db);
        
        $sql = "SELECT story_number_id, text FROM story_events where story_id = '{$story_id}' AND event_location = '{$location}'";
        if (!$result = $db->query($sql))
            showerror($db);
        while ($row=$result->fetch_assoc()) {
            $event = $row["story_number_id"];
            if ($event > $event_count) {
                $event_count = $event;
            }
            $text = $row["text"];
            print "<form method=\"POST\" action=\"edit_event.php\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$event\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"submit\" value=\"Edit $event ($text)\">";
            if (is_null($automaton->get_event_print($event))) {
                print ("<span style=\"color:red\">NOT IN AUTOMATON!</span>");
            } elseif (($automaton->get_event($event)->incomplete() or $automaton->get_event($event)->unhandled_action() or
                $automaton->get_event($event)->other_transition_issue) and !$automaton->get_event($event)->end_state) {
                 print ("<span style=\"color:red\">INCOMPLETE!</span>");
            }
            
            if ($automaton->get_event($event)->end_state) {
                print " <b>End State</b>";
            }
            
            if ($event == $initial_event) {
                print " <b>Initial Event</b>";
                print "</form>";
            } else {
                print "</form>";
                print "<form method=\"POST\" action=\"edit_story.php\"\>";
                print "<input type=\"hidden\" name=\"story_number_id\" value=\"$event\">";
                print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
                print "<input type=\"hidden\" name=\"location_id\" value=\"$location\">";
                print "<input type=\"hidden\" name=\"old_initial\" value=\"$initial_event\">";
                print "<input type=\"hidden\" name=\"task\" value=\"make_initial\">";
                print "<input type=\"submit\" value=\"Make Initial Event\">";
                print "</form>";
            }
            
            
            print "<p><form method=\"POST\" action=\"edit_story.php\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$event\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"delete_event\">";
            print "<input type=\"submit\" style=\"color:red;float:right\" value=\"Delete $event ($text)\">";
            print "</form></p><p style=\"clear:both\"><hr><p>";

         }
        
        $new_story_number = $event_count + 1;
        print "<form method=\"POST\" action=\"edit_story.php\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
        print "<input type=\"hidden\" name=\"location_id\" value=\"$location\">";
        print "<input type=\"hidden\" name=\"story_number_id\" value=\"$new_story_number\">";
        print "<input type=\"hidden\" name=\"task\" value=\"add_event\">";
        print "<input type=\"submit\" value=\"New Event\">";
        print "</form>";
    }
    
    print "<form method=\"POST\" action=\"new_location.php\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"submit\" value=\"New Location\">";
    print "</form>";
    
    print "<h1>Story Automaton</h1>";
    
    $automaton->print_automaton();
    
    ?>

<form method="POST" action="main.php">
<input type="hidden" name="location_id" value="
<?php
    echo get_location($db);
?>
">
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>

