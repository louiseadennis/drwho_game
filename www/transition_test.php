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
                
                if ($action_name == "Travel") {
                    $char_id_array = characters_at_location(get_location($db), $db);
                    $to_transmat = 1;
                    foreach ($char_id_array as $char_id) {
                        $is_locked_up = is_locked_up($char_id, $db);
                        $char_name = get_value_for_char_id("name", $char_id, $db);
                        $uchar = ucfirst($char_name);
                        if ($is_locked_up) {
                            print "$uchar is locked up and can't travel<br>";
                        } else{
                            print "<label><input type=checkbox name=\"person$to_transmat\" value=$char_id checked><labelspan>$uchar</labelspan></label><br>";
                            $to_transmat++;
                        }
                    }
                    for ($i = $to_transmat; $to_transmat<5; $to_transmat++) {
                        print "<input type=\"hidden\" name=\"person$to_transmat\" value=\"\">";
                    }
                    
                    $transition_id = $row["transition_id"];
                    $sql = "SELECT new_location from story_transitions where transition_id = '{$transition_id}'";
                    $location = select_sql_column($sql, "new_location", $db);
                    print "<input type=\"hidden\" name=\"location\" value=\"$location\">";
                }
                
                print "<input type=\"hidden\" name=\"transition\" value=\"$row[transition_id]\">";
                print "<input type=\"submit\" value=\"Make transition\"></form>";
                print "</li>";
            }
            print("</ul>");
        }
        
        $location_id = get_location($db);
        $story_events = get_location_events($story_id, $location_id, $db);
        while ($row = $story_events->fetch_assoc()) {
            $event = $row["story_number_id"];
            $sql = "SELECT text FROM story_events WHERE story_id = '{$story_id}' and story_number_id = '{$event}'";
            $event_name = select_sql_column($sql, "text", $db);
            print "<li>Switch to: $event_name";
            print "<form method=\"POST\" action=\"main.php\">";
            print "<input type=\"hidden\" name=\"go_to_event\" value=\"$event\">";
            print "<input type=\"submit\" value=\"Go To Event\"></form>";
            print "</li>";
        }
        print("</ul>");
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
