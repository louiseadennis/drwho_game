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
        
    $task = mysqlclean($_POST, "task", 25, $db);
    $story_id = mysqlclean($_POST, "story_id", 15, $db);
    $story = get_value_for_story_id("title", $story_id, $db);
    $story_number_id = mysqlclean($_POST, "story_number_id", 15, $db);
    $sql = "SELECT * FROM story_events where story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
    $event_location = select_sql_column($sql, "event_location", $db);
    // $event_id = select_sql_column($sql, "event_id", $db);
    
    if ($task == "change_title") {
        $new_text = mysqlclean($_POST, "event_text", 100, $db);
        $sql = "UPDATE story_events SET text='{$new_text}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    }
    
    if ($task == "change_des") {
        $new_description =mysqlclean($_POST, "event_description", 3000, $db);
        $sql = "UPDATE story_events SET description='{$new_description}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    }
    
    if ($task == "add_critter") {
        $new_critter = mysqlclean($_POST, "new_critter", 1000, $db);
        if ($critter_list != "") {
            $new_critter_list = $critter_list . "," . $new_critter;
        } else {
            $new_critter_list = $new_critter;
        }
        $sql = "UPDATE story_events SET critter_id_list='{$new_critter_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
    }
    
    if ($task == "del_critter") {
        $critter_to_remove = mysqlclean($_POST, "critter", 1000, $db);
        $key = array_search($critter_to_remove, $critter_array);
        if ($key !== false) {
            unset($critter_array[$key]);
            $new_critter_list = join(",", $critter_array);
            $sql = "UPDATE story_events SET critter_id_list='{$new_critter_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
            if (!$result = $db->query($sql))
                showerror($db);
            $critter_list = $new_critter_list;
            $critter_array = explode(",", $new_critter_list);
        }
    }
    
    if ($task == "add_ally") {
        $new_ally = mysqlclean($_POST, "new_ally", 1000, $db);
        if ($ally_list != "") {
            $new_ally_list = $ally_list . "," . $new_ally;
        } else {
            $new_ally_list = $new_ally;
        }
        $sql = "UPDATE story_events SET ally_id_list='{$new_ally_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
        if (!$result = $db->query($sql))
            showerror($db);
        $ally_list = $new_ally_list;
        $ally_array = explode(",", $new_ally_list);
    }
    
    if ($task == "del_ally") {
        $ally_to_remove = mysqlclean($_POST, "ally", 1000, $db);
        $key = array_search($ally_to_remove, $ally_array);
        if ($key !== false) {
            unset($ally_array[$key]);
            $new_ally_list = join(",", $ally_array);
            $sql = "UPDATE story_events SET ally_id_list='{$new_ally_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
            if (!$result = $db->query($sql))
                showerror($db);
            $ally_list = $new_ally_list;
            $ally_array = explode(",", $new_ally_list);
        }
    }
    
    if ($task == "add_modifier") {
         $new_modifier = mysqlclean($_POST, "new_modifier", 1000, $db);
         if ($modifier_list != "") {
             $new_modifier_list = $modifier_list . "," . $new_modifier;
         } else {
             $new_modifier_list = $new_modifier;
         }
         $sql = "UPDATE story_events SET modifier_id_list='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
         if (!$result = $db->query($sql))
             showerror($db);
         $modifier_list = $new_modifier_list;
         $modifier_array = explode(",", $new_modifier_list);
     }
    
    if ($task == "del_modifier") {
        $modifier_to_remove = mysqlclean($_POST, "modifier", 1000, $db);
        $key = array_search($modifier_to_remove, $modifier_array);
        if ($key !== false) {
            unset($modifier_array[$key]);
            $new_modifier_list = join(",", $modifier_array);
            $sql = "UPDATE story_events SET modifier_id_list='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
            if (!$result = $db->query($sql))
                showerror($db);
            $modifier_list = $new_modifier_list;
            $modifier_array = explode(",", $new_modifier_list);
        }
    }
    
    if ($task == "add_story_modifier") {
         $new_modifier = mysqlclean($_POST, "new_modifier", 1000, $db);
         if ($story_modifier_list != "") {
             $new_modifier_list = $story_modifier_list . "," . $new_modifier;
         } else {
             $new_modifier_list = $new_modifier;
         }
         $sql = "UPDATE story_events SET story_modifier_id_list='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
         if (!$result = $db->query($sql))
             showerror($db);
         $story_modifier_list = $new_modifier_list;
        $story_modifier_array = explode(",",$new_modifier_list);
     }
    
    if ($task == "del_story_modifier") {
        $modifier_to_remove = mysqlclean($_POST, "modifier", 1000, $db);
        $key = array_search($modifier_to_remove, $story_modifier_array);
        if ($key !== false) {
            unset($story_modifier_array[$key]);
            $new_modifier_list = join(",", $story_modifier_array);
            $sql = "UPDATE story_events SET story_modifier_id_list='{$new_modifier_list}' WHERE story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";
            if (!$result = $db->query($sql))
                showerror($db);
            $story_modifier_list = $new_modifier_list;
            $story_modifier_array = explode(",", $new_modifier_list);
        }
    }



    
    if ($task == "del_transition") {
        $transition_to_remove = mysqlclean($_POST, "transition_id", 1000, $db);
        $sql = "DELETE FROM story_transitions WHERE transition_id = '{$transition_to_remove}'";
        if (!$result = $db->query($sql))
            showerror($db);
    }
    
    if ($task == "new_default_transition") {
        $event_id=$story_number_id;
        $location_id=$event_location;
        $action_id=mysqlclean($_POST, "action_id", 3000, $db);
        $doctor_message = addslashes(get_value_for_action_id("default_message_doctor", $action_id, $db));
        $sql = "INSERT INTO story_transitions (location_id, event_id, story_id, action_id, transition_label, modifiers, probability, outcome, outcome_text, random_character_input, old_location, new_location, force_travel, lost_fight) VALUES  ('{$location_id}', '{$event_id}', '{$story_id}', '{$action_id}', 'null', 'doctor present', 100, '{$event_id}', '{$doctor_message}', 0, 0, 0, 0, 0)";
        if (!$result = $db->query($sql))
            showerror($db);
        $needs_name = get_value_for_action_id("needs_name", $action_id, $db);
        $message = addslashes(get_value_for_action_id("default_message_no_doctor", $action_id, $db));
        if ($needs_name) {
            $sql = "INSERT INTO story_transitions (location_id, event_id, story_id, action_id, transition_label, modifiers, probability, outcome, outcome_text, random_character_input, old_location, new_location, force_travel, lost_fight) VALUES  ('{$location_id}', '{$event_id}', '{$story_id}', '{$action_id}', 'null', '', 100, '{$event_id}', '{$message}', 1, 0, 0, 0, 0)";
        } else {
            $sql = "INSERT INTO story_transitions (location_id, event_id, story_id, action_id, transition_label, modifiers, probability, outcome, outcome_text, random_character_input, old_location, new_location, force_travel, lost_fight) VALUES  ('{$location_id}', '{$event_id}', '{$story_id}', '{$action_id}', 'null', '', 100, '{$event_id}', '{$message}', 0, 0, 0, 0, 0)";
        }
        if (!$result = $db->query($sql))
            showerror($db);
 
    }
    
      if ($task == "empty_transition") {
           $event_id=$story_number_id;
           $transition_label=mysqlclean($_POST, "transition_label", 3000, $db);
           $sql = "INSERT INTO story_transitions (location_id, event_id, story_id, action_id, transition_label, modifiers, probability, outcome, outcome_text, random_character_input, old_location, new_location, force_travel, lost_fight) VALUES  ('{$event_location}', '{$event_id}', '{$story_id}', 0, '{$transition_label}', '', 100, '{$event_id}', '', 0, 0, 0, 0, 0)";
           if (!$result = $db->query($sql))
               showerror($db);
    
       }
    
    $sql = "SELECT * FROM story_events where story_id = '{$story_id}' AND story_number_id = '{$story_number_id}'";

    $text = select_sql_column($sql, "text", $db);
    $description = select_sql_column($sql, "description", $db);
    $critter_list = select_sql_column($sql, "critter_id_list", $db);
    $ally_list = select_sql_column($sql, "ally_id_list", $db);
    $critter_array = explode(",", $critter_list);
    $ally_array = explode(",", $ally_list);
    $modifier_list = select_sql_column($sql, "modifier_id_list", $db);
    $modifier_array = explode(",", $modifier_list);
    $story_modifier_list = select_sql_column($sql, "story_modifier_id_list", $db);
    $story_modifier_array = explode(",", $story_modifier_list);

    $global_event_id = select_sql_column($sql, "global_event_id", $db);



    
    
?>
<html>
<head>
<title>Dr Who Game - Edit Event
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
    
    print "<form method=\"POST\" action=\"edit_story.php\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"submit\" value=\"Edit Story\">";
    print "</form>";

    print "<h1>$story_number_id: $text</h1>";
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_title\">";
    print "<input type=\"text\" name=\"event_text\" size=100 value=\"$text\">";
    print "<input type=\"submit\" value=\"Change Event Title\">";
    print "</form>";
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"change_des\">";
    print "<textarea name=\"event_description\" rows=10 cols=100>$description</textarea><br>";
    print "<input type=\"submit\" value=\"Change Event Description\">";
    print "</form>";
    
    print "<h2>Critters</h2>";
    if ($critter_list != '') {
        foreach ($critter_array as $critter_id) {
            $icon = get_value_for_critter_id("icon", $critter_id, $db);
            // print $icon;
            print("<img src=assets/$icon>");
            print "<form method=\"POST\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"del_critter\">";
            print "<input type=\"submit\" value=\"Delete Critter\">";
            print "<input type=\"hidden\" name=\"critter\" value=\"$critter_id\">";
            print "</form><br>";
        }
    }
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_critter\">";
    $sql = "SELECT critter_id, name, icon from critters";
    if (!$result = $db->query($sql)) {
        showerror($db);
    }
    print "<select name=\"new_critter\">";
    while ($row=$result->fetch_assoc()) {
        $name = $row["name"];
        $critter_id = $row["critter_id"];
        print "<option value=\"$critter_id\">$name</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Add Critter\">";
    print "</form>";
    
    print "<h2>Allies</h2>";
    if ($ally_list != '') {
        foreach ($ally_array as $ally_id) {
            $icon = get_value_for_ally_id("icon", $ally_id, $db);
            // print $icon;
            print("<img src=assets/$icon>");
            print "<form method=\"POST\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"del_ally\">";
            print "<input type=\"submit\" value=\"Delete Ally\">";
            print "<input type=\"hidden\" name=\"ally\" value=\"$ally_id\">";
            print "</form><br>";
        }
    }
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_ally\">";
    $sql = "SELECT ally_id, name, icon from allies";
    if (!$result = $db->query($sql)) {
        showerror($db);
    }
    print "<select name=\"new_ally\">";
    while ($row=$result->fetch_assoc()) {
        $name = $row["name"];
        $ally_id = $row["ally_id"];
        print "<option value=\"$ally_id\">$name</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Add Ally\">";
    print "</form>";

    print "<h2>Modifications to Character Status when they encounter this Event</h2>";
    print "<h3>General Modifiers</h3>";

    if ($modifier_list != '') {
        print "<ul>";
        foreach ($modifier_array as $modifier_id) {
            $text = get_value_for_event_modifier_id("name", $modifier_id, $db);
            print "<form method=\"POST\">";
                      print "<li>$text";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"del_modifier\">";
            print "<input type=\"submit\" value=\"Delete Modifier\">";
            print "<input type=\"hidden\" name=\"modifier\" value=\"$modifier_id\">";
            print "</form><br></li>";
        }
        print "</ul>";
    }

    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_modifier\">";
    $sql = "SELECT modifier_id, name from event_modifiers";
    if (!$result = $db->query($sql)) {
        showerror($db);
    }
    print "<select name=\"new_modifier\">";
    while ($row=$result->fetch_assoc()) {
        $name = $row["name"];
        $modifier_id = $row["modifier_id"];
        print "<option value=\"$modifier_id\">$name</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Add General Modifier\">";
    print "</form>";

    print "<h3>Story Specific Modifiers</h3>";
    
    if ($story_modifier_list != '') {
         print "<ul>";
         foreach ($story_modifier_array as $modifier_id) {
             $text = get_value_for_story_modifier_id("text", $modifier_id, $db);
             print "<form method=\"POST\">";
                       print "<li>$text";
             print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
             print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
             print "<input type=\"hidden\" name=\"task\" value=\"del_story_modifier\">";
             print "<input type=\"submit\" value=\"Delete Specific Modifier\">";
             print "<input type=\"hidden\" name=\"modifier\" value=\"$modifier_id\">";
             print "</form><br></li>";
         }
         print "</ul>";
     }


    
    
    print "<form method=\"POST\">";
    print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
    print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
    print "<input type=\"hidden\" name=\"task\" value=\"add_story_modifier\">";
    $sql = "SELECT modifier_id, text from story_modifiers";
    if (!$result = $db->query($sql)) {
        showerror($db);
    }
    print "<select name=\"new_modifier\">";
    while ($row=$result->fetch_assoc()) {
        $name = $row["text"];
        $modifier_id = $row["modifier_id"];
        print "<option value=\"$modifier_id\">$name</option>";
    }
    print "</select>";
    print "<input type=\"submit\" value=\"Add Story Specific Modifier\">";
    print "</form>";


    $automaton = new story_automaton($story_id, $db);

    if (!$automaton->get_event($story_number_id)->end_state) {
        print "<h2>Action Transitions</h2>";

        // Print Transitions for Each Action
        
        print("<ul>");
        print_action_header("EMPATHY", $automaton->get_event($story_number_id)->empathy_unhandled);
        print_transitions_for_action(1, $story_id, $story_number_id, $event_location, $db);
        
        
        print_action_header("TECH", $automaton->get_event($story_number_id)->tech_unhandled);
        print_transitions_for_action(2, $story_id, $story_number_id, $event_location, $db);

        print_action_header("RUNNING", $automaton->get_event($story_number_id)->running_unhandled);
        print_transitions_for_action(3, $story_id, $story_number_id, $event_location, $db);

        print_action_header("COMBAT", $automaton->get_event($story_number_id)->combat_unhandled);
        print_transitions_for_action(4, $story_id, $story_number_id, $event_location, $db);

        print_action_header("WILLPOWER", $automaton->get_event($story_number_id)->willpower_unhandled);
        print_transitions_for_action(5, $story_id, $story_number_id, $event_location, $db);

        print_action_header("OBSERVATION", $automaton->get_event($story_number_id)->observation_unhandled);
        print_transitions_for_action(6, $story_id, $story_number_id, $event_location, $db);
        
        print_action_header("TRAVEL", $automaton->get_event($story_number_id)->observation_unhandled);
        print("<p><b>NB:</b> a transition is triggered when someone leaves the location.  If someone travels in that should be handled by a synchronised transition.</p>");
        print_transitions_for_action(100, $story_id, $story_number_id, $event_location, $db);
        
        print("</ul>");
        // Print Synchronised Transitions
        
        print "<h2>Synchronised Transitions</h2>";
        
        print_transitions_for_action(0, $story_id, $story_number_id, $event_location, $db);
        
        $event_object = $automaton->get_event($story_number_id);
        $transition_labels = $event_object->unhandled_transitions;
        
        print "<h2>Unhandled Transitions</h2>";
        
        //print ($event_object->location_event_state_string_long());
        
        print("<ul>");
        if ($transition_labels != '') {
            foreach ($transition_labels as $label) {
                print "<li>$label";
                print "<form method=\"POST\" action=\"edit_event.php\">";
                print "<input type=\"hidden\" name=\"transition_label\" value=\"$label\">";
                print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
                print "<input type=\"hidden\" name=\"task\" value=\"empty_transition\">";
                print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
                print "<input type=\"submit\" value=\"Add Empty Transition for Label\">";
                 
                print "</form>";
            }
        }
        print("</ul>");
    } else {
        print ("END STATE");
    }

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
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>

