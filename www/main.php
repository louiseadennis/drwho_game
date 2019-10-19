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
//    print $last_action;


$travel_type = mysqlclean($_POST, "travel_type", 10, $db);
// $location_id = mysqlclean($_POST, "location", 10, $db);
$start_story = mysqlclean($_POST, "start_story", 10, $db);
$quit_story = mysqlclean($_POST, "quit_story", 10, $db);

// Handle Story Events
if ($start_story != "") {
    begin_story($start_story, $db);
} else if ($quit_story != "") {
    quit_story($quit_story, $db);
}
    
// Handle Travel
if ($last_action == "travel") {
    $location_id = get_location($db);
    update_users("prev_location", $location_id, $db);
   // resolve_events($db);
    $travellers = [];
    
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

    if (!empty($travellers) || $travel_type = "pov_switch") {
       
        if ($travel_type == "transmat" || $travel_type == "pov_switch") {
            if (! empty($travellers) || $travel_type == "pov_switch") {
                $location_id = mysqlclean($_POST, "location", 10, $db);
            }
        
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
