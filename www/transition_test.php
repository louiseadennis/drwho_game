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
<title>Dr Who Game -
<?php
    echo $uname;
?>
 Transitions Test Rig</title>

<link rel="stylesheet" href="./styles/default.css?v=1" type="text/css">
</head>
<body>
<?php
    print_header_info_pages($db);
    ?>


<div class=main style="padding:1em">

<h2>Possible Transitions</h2>

<?php
  
    $event = get_current_event($db);
    $story_id = get_value_from_users("story", $db);
    
    if ($event != 0) {
        $sql = "SELECT transition_id, probability, outcome, action_id from story_transitions where event_id = '{$event}' and story_id = '{$story_id}'";
        
        if (!$result = $db->query($sql)) {
            // This event has no transitions
            print("<p>No Transitions from this Event</p>");
        } else {
            print("<ul>");
            while ($row=$result->fetch_assoc()) {
                $next_event = $row["outcome"];
                $sql = "SELECT text FROM story_events WHERE story_id = '{$story_id}' and story_number_id = '{$next_event}'";
                $event_name = select_sql_column($sql, "text", $db);
                
                $action_id = $row["action_id"];
                
                $action_name = "Synchronised Action.";
                if ($action_id > 0) {
                    if ($action_id < 7) {
                        $sql = "SELECT name FROM actions WHERE action_id = '$action_id'";
                        $action_name = select_sql_column($sql, "name", $db);
                    } else {
                        $action_name = "Travel";
                    }
                }
                
                print "<li>Transition to: $event_name with probability $row[probability] using $action_name";
                print "<form method=\"POST\" action=\"main.php\">";
                print "<input type=\"hidden\" name=\"transition\" value=\"$row[transition_id]\">";
                print "<input type=\"submit\" value=\"Make transition\"></form>";
                print "</li>";
            }
            print("</ul>");
        }

    }
    
    
?>

<form method="POST" action="main.php">
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>
