<?php
    // There are six basic actions that a player may take in a location empath, tech, running, combat, willpower and observation
    // There are also travel actions in which the player chooses to move one or more characters using some mode of transport (e.g., tardis, transmat)
    // Taking an action in a location causes a transition (see transitions.php)


    // Is  this one of the six basic actions?
    function is_basic_action($action, $db) {
        $sql = "SELECT * from actions WHERE name = '$action'";
        if (!$result = $db->query($sql))
            return false;
        if ($result->num_rows == 0)
            return false;
        
        return true;
    }
    
    // Is this a travel action?
    function is_travel_action($action, $db) {
        return $action == "travel";
    }
    
    // is this an action (either basic or travel)
    function is_action($action, $db) {
        if (is_travel_action($action, $db)) {
            return true;
        }
        if (is_basic_action($action, $db)) {
            return true;
        }
        return false;
    }
    
    // Basic actions have a default "result" that can be printed if there is no other message related to the transition for the action.<
    function print_action_default($action, $connection) {
        if (doctor_here($connection)) {
            $message = get_value_for_name_from("default_message_doctor", "actions", $action, $connection);
                print "<p>$message</p>";
        } else {
            $location_id = get_location($connection);
            $characters = characters_at_location($location_id, $connection);
            $needs_name = get_value_for_name_from("needs_name", "actions", $action, $connection);
            $message = get_value_for_name_from("default_message_no_doctor", "actions", $action, $connection);
            if ($needs_name == 0) {
                print "<p>$message</p>";
            } else {
                $char_num = count($characters);
                $dice = rand(0, $char_num - 1);
                $char_name = get_value_for_char_id("name", $characters[$dice], $connection);
                print "<p>$char_name $message</p>";
            }
        }
    }
    
 
    ?>
