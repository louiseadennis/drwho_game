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

// Player performs an action at a location which should trigger a transition
$last_action = mysqlclean($_POST, "last_action", 15, $db);
$travel_type = mysqlclean($_POST, "travel_type", 10, $db);

// Stopping and starting a story
$start_story = mysqlclean($_POST, "start_story", 10, $db);
$quit_story = mysqlclean($_POST, "quit_story", 10, $db);

// Debugging info
$event = mysqlclean($_POST, "go_to_event", 15, $db);
$transition = mysqlclean($_POST, "transition", 15, $db);

$forced_travel = 0;
$just_started = 0;

// Handle Stop/Start Story Events
if ($start_story != "") {
    begin_story($start_story, $db);
    $just_started = 1;
} else if ($quit_story != "") {
    quit_story($quit_story, $db);
}

$starting_location = get_location($db);

if ($transition != "") {
    make_transition($transition, $db);
    
    $sql = "SELECT action_id, force_travel, travel_type from story_transitions where transition_id = '{$transition}'";
    $action_id = select_sql_column($sql, "action_id", $db);    
    $forced_travel = select_sql_column($sql, "force_travel", $db);
    if ($action_id == 100 || $forced_travel) {
        $last_action = 'travel';
        $travel_type = select_sql_column($sql, "travel_type", $db);
    }
} elseif (having_adventure($db) && $travel_type != 'pov_switch'  && !$just_started) {
    // Make the transition apart from travel
    story_transition($last_action, $db);
    
    $transition = get_value_from_users("last_transition", $db);
    
    //  If travel is forced - particularly if characters moved from another locaiton to here.
    $sql = "SELECT force_travel from story_transitions where transition_id = '{$transition}'";
    $forced_travel = select_sql_column($sql, "force_travel", $db);
    if ($forced_travel) {
        $sql = "SELECT travel_type from story_transitions where transition_id = '{$transition}'";
        $travel_type = select_sql_column($sql, "travel_type", $db);
        $sql = "SELECT old_location from story_transitions where transition_id = '{$transition}'";
        $starting_location = select_sql_column($sql, "old_location", $db);
    }
}
    
if ($event != "") {
    go_to_event($event, $db);
}
     
// Handle Travel
if ($last_action == "travel" || $forced_travel) {
    $location = mysqlclean($_POST, "location", 10, $db);
    $traveller1 = mysqlclean($_POST, "person1", 10, $db);
    $traveller2 = mysqlclean($_POST, "person2", 10, $db);
    $traveller3 = mysqlclean($_POST, "person3", 10, $db);
    $traveller4 = mysqlclean($_POST, "person4", 10, $db);
    $dial1 = mysqlclean($_POST, "dial1", 10, $db);
    $dial2 = mysqlclean($_POST, "dial2", 10, $db);
    $dial3 = mysqlclean($_POST, "dial3", 10, $db);
    $dial4 = mysqlclean($_POST, "dial4", 10, $db);
    $travel_info = new travel_info($location, $traveller1, $traveller2, $traveller3, $traveller4, $dial1, $dial2, $dial3, $dial4);
    $location_id = travel_while_transition($db, $forced_travel, $travel_type, $travel_info, $starting_location, $transition);
}
    
update_users("last_action", $last_action, $db);
    
// Go to next location
if ($location_id=='') {
    header("Location: locations/location1.php");
    exit;
} else {
    update_users("location_id", $location_id, $db);
}

$location_string = "locations/location" . $location_id;
    
header("Location: $location_string.php");
exit;
    
?>
<html>
<head>
<title>Dr Who Game</title>

<link rel="stylesheet" href="styles/default.css" type="text/css">
</head>
<body>
<div class=main>
<p>This is the main page.  It should not appear</p>
<?php
	echo "<p>$message</p>";
	echo "<p>location:$location_id</p>";
?>
</body>
</html>
