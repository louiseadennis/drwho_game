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
    $task = mysqlclean($_POST, "task", 20, $db);

    if ($task == "new_transition") {
        $event_id=mysqlclean($_POST, "event_id", 3000, $db);
        $location_id=mysqlclean($_POST, "location_id", 3000, $db);
        $action_id=mysqlclean($_POST, "action_id", 3000, $db);
        $sql = "INSERT INTO story_transitions (location_id, event_id, story_id, action_id, transition_label, modifiers, probability, outcome, outcome_text, random_character_input, old_location, new_location, force_travel, lost_fight) VALUES  ('{$location_id}', '{$event_id}', '{$story_id}', '{$action_id}', 'no label', '', 100, '{$event_id}', 'no outcome text', 0, 0, 0, 0, 0)";
        if (!$result = $db->query($sql))
            showerror($db);
        
        $sql = "SELECT transition_id FROM story_transitions where story_id = '{$story_id}' AND transition_label = 'no label'";
        if (!$result = $db->query($sql))
            showerror($db);
        $transition_id = select_sql_column($sql, "transition_id", $db);
    } else {
        $transition_id = mysqlclean($_POST, "transition_id", 15, $db);
    }
    
    if ($task == "change_outcome") {
        $new_description=mysqlclean($_POST, "new_outcome_text", 3000, $db);
        $sql = "UPDATE story_transitions SET outcome_text='{$new_description}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    } elseif ($task == "change_label") {
        $new_label=mysqlclean($_POST, "label", 3000, $db);
         $sql = "UPDATE story_transitions SET transition_label='{$new_label}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
         if (!$result = $db->query($sql))
             showerror($db);
    } elseif ($task == "change_new_loc") {
        $sql = "SELECT location_id FROM story_transitions where story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        $location_id = select_sql_column($sql, "location_id", $db);
        $new_new_location=mysqlclean($_POST, "new_location", 3000, $db);
        if ($new_new_location == $location_id) {
            $new_new_location = 0;
        }
        $sql = "UPDATE story_transitions set new_location='{$new_new_location}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    } elseif ($task == "change_event") {
        $new_outcome = mysqlclean($_POST, "outcome", 3000, $db);
        $sql = "UPDATE story_transitions set outcome='{$new_outcome}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    } elseif ($task == "change_prob") {
        $new_probability=mysqlclean($_POST, "new_probability", 3000, $db);
        $sql = "UPDATE story_transitions SET probability='{$new_probability}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
       if (!$result = $db->query($sql))
            showerror($db);

    } elseif ($task == "remove_mod") {
        $old_modifier = mysqlclean($_POST, "old_modifier", 3000, $db);
        $sql = "SELECT modifiers FROM story_transitions where story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        $modifiers = select_sql_column($sql, "modifiers", $db);
        $modifier_array = explode(",", $modifiers);
        $new_modifiers = array();
        foreach ($modifier_array as $modifier) {
            if ($modifier != $old_modifier) {
                array_push($new_modifiers, $modifier);
            }
        }
        $new_modifier_list = join(",", $new_modifiers);
        $sql = "UPDATE story_transitions SET modifiers='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    } elseif ($task == "add_mod") {
        $new_modifier = mysqlclean($_POST, "new_modifier", 3000, $db);
        $sql = "SELECT modifiers FROM story_transitions where story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        $modifiers = select_sql_column($sql, "modifiers", $db);
        $modifier_array = explode(",", $modifiers);
        
        $new_modifiers = array();
        $sql = "SELECT * FROM transition_modifiers";
        if (!$result = $db->query($sql))
            showerror($db);
        while ($row=$result->fetch_assoc()) {
            $name = $row["text"];
            if (in_array($name, $modifier_array)) {
                array_push($new_modifiers, $name);
            } elseif ($name == $new_modifier) {
                array_push($new_modifiers, $name);
            }
        }
        
        $new_modifier_list = join(",", $new_modifiers);
        $sql = "UPDATE story_transitions SET modifiers='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    } elseif ($task == "add_who_affected") {
        $new_affected = mysqlclean($_POST, "who_effect", 3000, $db);
        $sql = "UPDATE story_transitions SET random_character_input='{$new_affected}' WHERE story_id = '{$story_id}' AND transition_id = '{$transition_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    }
    
    $sql = "SELECT * FROM story_transitions where story_id = '{$story_id}' AND transition_id = '{$transition_id}'";

    $text = select_sql_column($sql, "outcome_text", $db);
    $transition_label = select_sql_column($sql, "transition_label", $db);
    $event_id = select_sql_column($sql, "event_id", $db);
    $new_location = select_sql_column($sql, "new_location", $db);
    $outcome = select_sql_column($sql, "outcome", $db);
    $probability = select_sql_column($sql, "probability", $db);
    $modifiers = select_sql_column($sql, "modifiers", $db);
    $action_id = select_sql_column($sql, "action_id", $db);
    $person_affected = select_sql_column($sql, "random_character_input", $db);
    
    $sql = "SELECT text from story_events where story_id = '{$story_id}' AND story_number_id = '{$outcome}'";
    $outcome_event_text = select_sql_column($sql, "text", $db);

    
//    if ($task == "change_title") {
  //      $new_text = mysqlclean($_POST, "event_text", 100, $db);
    //    $sql = "UPDATE story_events SET text='{$new_text}' WHERE story_id = //'{$story_id}' AND story_number_id = '{$story_number_id}'";
//        if (!$result = $db->query($sql))
  //          showerror($db);
    //    $text = $new_text;
    // }
    
?>
<html>
<head>
<title>Dr Who Game - Edit Transition
<?php
    echo $text;
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
    print "<h1>$transition_id: $text for $action_id</h1>";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_outcome\">";
    print "<input type=\"text\" name=\"new_outcome_text\" size=100 value=\"$text\">";
    print "<input type=\"submit\" value=\"Change Outcome Text\">";
    print "</form>";
    
    print "<p><b>Transition Label:</b> ";
    print $transition_label;
    
    $sql = "SELECT transition_label FROM story_transitions where story_id = '{$story_id}'";
    $label_array = sql_return_to_array($sql, "transition_label", $db);
    print "<br>";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_label\">";
    print "&nbsp <select id=\"label\" name=\"label\">";
    foreach ($label_array as $label) {
        print "<option value=\"$label\">$label</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Change to Existing Label\">";
    print "</form>";
    print "<br>";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_label\">";
    print "&nbsp <input type=\"text\" name=\"label\" size=25 value=\"$transition_label\">";
    print "<input type=\"submit\" value=\"New Label\">";
    print "</form>";

    print "</p>";
    
    
    print "<p><b>Location after transition:</b>";
    if ($new_location != 0) {
        print "New location: ";
        $new_location_name = get_value_for_location_id("name", $new_location, $db);
        print "$new_location_name ($new_location)</p>";
    } else {
        print "Location remains the same.</p>";
    }
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_new_loc\">";
    print "&nbsp <select id=\"new_location\" name=\"new_location\">";
    
    $sql = "SELECT locations from stories where story_id='{$story_id}'";
    $story_locations = select_sql_column($sql, "locations", $db);
    $location_array = explode(",", $story_locations);
    foreach ($location_array as $location) {
        $location_name = get_value_for_location_id("name", $location, $db);
        print "<option value=\"$location\">$location  ($location_name)</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Change New Location\">";
    print "</form>";

    # $automaton = new story_automaton($story_id, $db);
    
    print "</p><p><b>Outcome:</b>";
    print " $outcome ( $outcome_event_text )";
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_event\">";
    print "&nbsp <select id=\"outcome\" name=\"outcome\">";
    
    $sql = "SELECT story_number_id from story_events where story_id='{$story_id}'";
    $story_events = sql_return_to_array($sql, "story_number_id", $db);
     foreach ($story_events as $event) {
        $sql = "SELECT text from story_events where story_number_id = '{$event}' and story_id = '{$story_id}'";
        $event_name = select_sql_column($sql, "text", $db);
        print "<option value=\"$event\">$event  ($event_name)</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Change Outcome\">";
    print "</form>";
    
    $total_prob = probability_sum($story_id, $modifiers, $action_id, $event_id, $db);

    $font_color = "black";
    if ($total_prob != 100) {
        $font_color = "red";
    }
        
    print "</p><p style=\"color:$font_color\"><b>Probability:</b>";
    print " $probability";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_prob\">";
    print "<input type=\"probability\" name=\"new_probability\" size=3 value=\"$probability\">";
    print "<input type=\"submit\" value=\"Change Probability\">";
    print "</form>";

    $outcome_list = probability_outcomes($story_id, $modifiers, $action_id, $event_id, $db);
    print "<ul style=\"color:$font_color\">";
    print $outcome_list;
    print "</ul></p>";
    

    print "</p><p><b>Modifiers:</b>";
    $modifier_array = explode(",", $modifiers);
    print "<ul>";
    foreach ($modifier_array as $modifier) {
        if ($modifier != "") {
                print "<form method=\"POST\">";
            print "<li>$modifier";
             print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
             print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
             print "<input type=\"hidden\" name=\"task\" value=\"remove_mod\">";
            print "<input type=\"hidden\" name=\"old_modifier\" value=\"$modifier\">";
            print "<input type=\"submit\" value=\"Remove Modifier\">";
            print "</li>";
            print "</form>";
        }
    }
    print "</ul>";
    
    $sql = "SELECT * from transition_modifiers";
    $modifiers = sql_return_to_array($sql, "text", $db);
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_mod\">";
    print "<select id=\"new_modifier\" name=\"new_modifier\">";
    foreach ($modifiers as $modifier) {
        print "<option value=\"$modifier\">$modifier</option>";
    }
    print "<input type=\"submit\" value=\"Add Modifier\">";
    print "</form></p>";
    
    print "</p><p><b>Who Does the Transition Affect?: $person_affected</b>";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_who_affected\">";
    $checked = "";
    if ($person_affected == "0") {
        $checked = "checked";
    }
    print "<input type=\"radio\" id=\"nobody\" name=\"who_effect\" $checked value=\"0\"><label for=\"nobody\">No One</label><br>";
    $checked = "";
    if ($person_affected == "1") {
         $checked = "checked";
    }
    print "<input type=\"radio\" id=\"random\" name=\"who_effect\" $checked value=\"1\"><label for=\"random\">Random Character</label><br>";
    $checked = "";
    if ($person_affected == "2") {
         $checked = "checked";
    }
    if (in_array("sonic_screwdriver", $modifier_array)) {
        print "<input type=\"radio\" id=\"random\" name=\"who_effect\" $checked value=\"2\"><label for=\"random\">Character with Sonic</label><br>";

    }
    if (in_array("doctor present", $modifier_array)) {
        print "<input type=\"radio\" id=\"random\" name=\"who_effect\" $checked value=\"3\"><label for=\"random\">The Doctor</label><br>";

    }
    print "<input type=\"submit\" value=\"Submit\">";
    print "</form>";

    print "<hr>";
    // ----------------------------------------------------------------------------------------------------------------
    
    print "<form method=\"POST\" action=\"edit_event.php\">";
     print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
     print "<input type=\"hidden\" name=\"story_number_id\" value=\"$event_id\">";
     print "<input type=\"submit\" value=\"Edit Event\">";
     print "</form>";

    
    print "<form method=\"POST\" action=\"edit_story.php\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"submit\" value=\"Edit Story\">";
    print "</form>";
    
    

?>
<form method="POST" action="main.php">
<input type="hidden" name="location_id" value="
<?php
    echo get_location($db);
?>
">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>

