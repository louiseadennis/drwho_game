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
    
    // Action Side Effects =====================
    function side_effects($action, $connection) {
        $user_id = get_user_id($connection);
        $sql = "SELECT char_id FROM characters_in_play WHERE user_id = '$user_id'";
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($action == "travel") {
            $action = 100;
        }
        
        while ($row=$result->fetch_assoc()) {
            $char_id = $row["char_id"];
            $modification_list = get_value_for_char_in_play_id("modifiers", $char_id, $connection);
            $modification_array = explode(",", $modification_list);
            foreach ($modification_array as $modifier) {
                $sql = "SELECT remove from story_modifiers WHERE modifier_id = '$modifier'";
                $remove = select_sql_column($sql, "remove", $connection);
                if ($remove == $action) {
                    $sql = "SELECT remove_modifier from story_modifiers WHERE modifier_id = '$modifier'";
                    $remove_modifier = select_sql_column($sql, "remove_modifier", $connection);
                    $value = 1;
                    if ($remove_modifier != '') {
                        $value = remove_modification_status($remove_modifier, $connection);
                    }
                    
                    if ($value) {
                        remove_modification_from_character($modifier, $char_id, $connection);
                    }
                 }
            }
        }
    }

    // Returns 1 if this modifier should be removed from al characteres
    function remove_modification_status($modifier, $connection) {
        if ($modifier == "transmat") {
            $travel_type = get_value_from_users("travel_type", $connection);
            if ($travel_type == "transmat") {
                return 1;
            }
        }
        return 0;
    }

    //================= Print Spport
    // Basic actions have a default "result" that can be printed if there is no other message related to the transition for the action.<
    function action_default_string($action, $connection) {
        if (doctor_here($connection)) {
            $message = get_value_for_name_from("default_message_doctor", "actions", $action, $connection);
                return "<p>$message</p>";
        } else {
            $location_id = get_location($connection);
            $characters = characters_at_location($location_id, $connection);
            $needs_name = get_value_for_name_from("needs_name", "actions", $action, $connection);
            $message = get_value_for_name_from("default_message_no_doctor", "actions", $action, $connection);
            if ($needs_name == 0) {
                return "<p>$message</p>";
            } else {
                $char_num = count($characters);
                $dice = rand(0, $char_num - 1);
                $char_name = get_value_for_char_id("name", $characters[$dice], $connection);
                return "<p>$char_name $message</p>";
            }
        }
    }

    function print_action_default($action, $connection) {
        print(action_default_string($action, $connection));
    }

    function action_string($connection) {
        $last_action = get_value_from_users("last_action", $connection);
        $pov_switch = 0;
        if (is_travel_action($last_action, $connection)) {
            $travel_type = get_value_from_users("travel_type", $connection);
            if ($travel_type == "pov_switch") {
                $pov_switch = 1;
            }
        }
        
        if (having_adventure($connection) && !$pov_switch) {
            // print("B print_action from action_utilities");
            $last_transition = get_value_from_users("last_transition", $connection);
            return transition_outcome_string($last_transition, $last_action, $connection);
        } else {
                if (is_basic_action($last_action, $connection)) {
                    return action_default_string($last_action, $connection);
                } else {
                    return "<p>  &nbsp;</p>";
                }
        }
    }

    function print_action($action, $connection) {
        print(action_string($action, $connection));
    }

    
    // GENERAL DB SUPPORT
    function get_value_for_action_id($column, $action_id, $connection) {
      $sql = "SELECT {$column} FROM actions WHERE action_id = '{$action_id}'";

        return select_sql_column($sql, $column, $connection);
    }


    
 
    ?>
