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
    
$last_action = mysqlclean($_POST, "last_action", 15, $db);
$transition = mysqlclean($_POST, "transition", 15, $db);
$event = mysqlclean($_POST, "go_to_event", 15, $db);
//    print $last_action;


$travel_type = mysqlclean($_POST, "travel_type", 10, $db);
// $location_id = mysqlclean($_POST, "location", 10, $db);
$start_story = mysqlclean($_POST, "start_story", 10, $db);
$quit_story = mysqlclean($_POST, "quit_story", 10, $db);
$forced_travel = 0;
$just_started = 0;

// Handle Story Events
if ($start_story != "") {
    begin_story($start_story, $db);
    $just_started = 1;
} else if ($quit_story != "") {
    quit_story($quit_story, $db);
}

//    print($transition);
$old_location = get_location($db);

if ($transition != "") {
    make_transition($transition, $db);
    $sql = "SELECT action_id from story_transitions where transition_id = '{$transition}'";
    $action_id = select_sql_column($sql, "action_id", $db);
    
    $sql = "SELECT force_travel from story_transitions where transition_id = '{$transition}'";
    $forced_travel = select_sql_column($sql, "force_travel", $db);
    //print("hey");
    //print($forced_travel);
    if ($action_id == 100 || $forced_travel) {
        $last_action = 'travel';
        $sql = "SELECT transition_label from story_transitions where transition_id = '{$transition}'";
        $travel_type = select_sql_column($sql, "transition_label", $db);
        //print($travel_type);
    }
} elseif (having_adventure($db) && $travel_type != 'pov_switch'  && !$just_started) {
    story_transition($last_action, $db);
    $transition = get_value_from_users("last_transition", $db);
    $sql = "SELECT action_id from story_transitions where transition_id = '{$transition}'";
    $action_id = select_sql_column($sql, "action_id", $db);
    
    $sql = "SELECT force_travel from story_transitions where transition_id = '{$transition}'";
    $forced_travel = select_sql_column($sql, "force_travel", $db);
    //print("hey");
    //print($forced_travel);
    if ($forced_travel) {
        // $last_action = 'travel';
        $sql = "SELECT transition_label from story_transitions where transition_id = '{$transition}'";
        $travel_type = select_sql_column($sql, "transition_label", $db);
        //print($travel_type);
        $sql = "SELECT old_location from story_transitions where transition_id = '{$transition}'";
        $old_location = select_sql_column($sql, "old_location", $db);
    }
    
    $sql = "SELECT lost_fight from story_transitions where transition_id = '{$transition}'";
    $lost_fight = select_sql_column($sql, "lost_fight", $db);
    if ($lost_fight) {
        lost_fight($db);
    }

}
    
if ($event != "") {
    go_to_event($event, $db);
}
    
foreach (characters_at_location($old_location, $db) as $char_id) {
    update_character($char_id, "prev_location", $old_location, $db);
}
    
// Handle Travel
if ($last_action == "travel" || $forced_travel) {
    $location_id = get_location($db);
    update_users("prev_location", $location_id, $db);
    $prev_location = $location_id;
    // resolve_events($db);
    $travellers = [];
    
    if ($forced_travel) {
        $travellers = characters_at_location($old_location, $db);
    } else {
    
        $traveller1 = mysqlclean($_POST, "person1", 10, $db);
        if ($traveller1 != "") {
            array_push($travellers, $traveller1);
        }
        $traveller2 = mysqlclean($_POST, "person2", 10, $db);
        if ($traveller2 != "") {
            array_push($travellers, $traveller2);
        }
        $traveller3 = mysqlclean($_POST, "person3", 10, $db);
        if ($traveller3 != "") {
            array_push($travellers, $traveller3);
        }
        $traveller4 = mysqlclean($_POST, "person4", 10, $db);
        if ($traveller4 != "") {
            array_push($travellers, $traveller4);
        }
    }

    if (!empty($travellers) || $travel_type == "pov_switch") {
       
        if ($travel_type == "transmat" || $travel_type == "pov_switch") {
            $location_id = mysqlclean($_POST, "location", 10, $db);
        }
        
        if ($forced_travel) {
            $sql = "SELECT new_location from story_transitions where transition_id = '{$transition}'";
            $location_id = select_sql_column($sql, "new_location", $db);
        }
    
        if ($travel_type == "tardis") {
            if (! empty($travellers)) {
                $dial1 = mysqlclean($_POST, "dial1", 10, $db);
                $dial2 = mysqlclean($_POST, "dial2", 10, $db);
                $dial3 = mysqlclean($_POST, "dial3", 10, $db);
                $dial4 = mysqlclean($_POST, "dial4", 10, $db);
                $location_id = use_tardis($dial1, $dial2, $dial3, $dial4, $db);
                update_users("tardis_location", $location_id, $db);
            }
        }
    
        update_users("travel_type", $travel_type, $db);
        update_users("action_done", 0, $db);
        
        foreach (characters_at_location($location_id, $db) as $char_id) {
            update_character($char_id, "prev_location", $location_id, $db);
        }
        
        foreach($travellers as $char_id) {
            update_character($char_id, "location_id", $location_id, $db);
        }
    } else {
        $location_id = get_location($db);
    }
}

update_users("last_action", $last_action, $db);

    
// Go to next location
$user_id = get_user_id($db);

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
