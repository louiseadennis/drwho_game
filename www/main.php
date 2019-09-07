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
    
$last_action = mysqlclean($_POST, "last_action", 10, $db);
//    print $last_action;


$travel_type = mysqlclean($_POST, "travel_type", 10, $db);
$location_id = mysqlclean($_POST, "location", 10, $db);
$start_story = mysqlclean($_POST, "start_story", 10, $db);
    
    if ($start_story != "") {
        update_users("story", $start_story, $db);
    }

if ($last_action == "item" || $last_action == "wait" ) {
    $current_location = get_location($db);
    $action_required = get_value_for_location_id("action_required", $current_location, $db);
    $action_done = get_value_from_users("action_done", $db);
    $item_used = mysqlclean($_POST, "item_used", 10, $db);
    if ($action_required != '' && !$action_done) {
        $item_name = get_value_for_equip_id("name", $item_used, $db);
        if ($item_name !== $action_required) {
            if ($action_required == '?') {
            }
        }
    } else {
        update_users("action_done", 1, $db);
    }
}

if ($last_action == "travel") {
   // resolve_events($db);

    if ($travel_type == "vehicle") {
    }
    if ($travel_type == "transmat") {
        $location_id = mysqlclean($_POST, "location", 10, $db);
        
    }
    
    if ($travel_type == "tardis") {
        $dial1 = mysqlclean($_POST, "dial1", 10, $db);
        $dial2 = mysqlclean($_POST, "dial2", 10, $db);
        $dial3 = mysqlclean($_POST, "dial3", 10, $db);
        $dial4 = mysqlclean($_POST, "dial4", 10, $db);
        $location_id = use_tardis($dial1, $dial2, $dial3, $dial4, $db);
        update_users("tardis_location", $location_id, $db);
    }
    
    update_users("travel_type", $travel_type, $db);
    update_users("action_done", 0, $db);
}

update_users("last_action", $last_action, $db);

    
// $hp = get_value_from_users("hp", $db);

// if ($travel_type != '' && $travel_type != "none") {
//    update_users("travel_type", $travel_type, $db);
//    update_users("action_done", 0, $db);
//} else {
    if ($last_action == "item") {
        $item_used = mysqlclean($_POST, "item_used", 10, $db);
        if ($item_used != '') {
            update_users("item_used", $item_used, $db);
        } else {
            update_users("item_used", 2, $db);
        }
    }
//}

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
