<?php
    
    function forced_travel_transition($transition, $db) {
        $sql = "SELECT action_id, force_travel from story_transitions where transition_id = '{$transition}'";
        //print $sql;
        $action_id = select_sql_column($sql, "action_id", $db);
        if ($action_id == 100 || $action_id == 0) {
            return select_sql_column($sql, "force_travel", $db);
        }
        return 0;
    }
    
    class forced_travel_info {
        public $from_location;
        public $to_location;
        public $characters = array();
        public $travel_type;
        
        function __construct($transition_id, $db) {
            $sql = "SELECT travel_type, location_id, new_location from story_transitions where transition_id = '{$transition_id}'";
            $this->travel_type = select_sql_column($sql, "travel_type", $db);
            $this->from_location = select_sql_column($sql, "location_id", $db);
            $this->characters = characters_at_location($this->from_location, $db);
            $this->to_location = select_sql_column($sql, "new_location", $db);
            
            // $this->print_travel_info();
        }
        
        function force_travel($db) {
            foreach($this->characters as $char_id) {
                update_character($char_id, "location_id", $this->to_location, $db);
            }
        }
        
        function print_travel_info() {
            print("From: $this->from_location, To: $this->to_location, Travel_type: $this->travel_type");
            foreach ($this->characters as $character) {
                print("<br>&nbsp; $character");
            }
        }
    }
    
    
    function modify_dice($action_id, $connection) {
        $location_id = get_location($connection);
        $characters_at_location = characters_at_location($location_id, $connection);
        $modifier = 0;
        foreach ($characters_at_location as $char_id) {
            if (is_conscious($char_id, $connection)) {
                $stat = "";
                if ($action_id == 1) {
                    $stat = "empathy";
                } elseif ($action_id == 2) {
                    $stat = "tech";
                } elseif ($action_id == 3) {
                    $stat = "running";
                } elseif ($action_id == 4) {
                    $stat = "combat";
                } elseif ($action_id == 5) {
                    $stat = "willpower";
                } elseif ($action_id == 6) {
                    $stat = "observation";
                }
                
                if ($stat != "") {
                    $sql = "SELECT $stat FROM characters_in_play WHERE char_id = '{$char_id}'";
                    $stat_mod = select_sql_column($sql, "$stat", $connection);
                    
                    $sql = "SELECT doctor FROM characters WHERE char_id = '{$char_id}'";
                    if (select_sql_column($sql, "doctor", $connection) == 1) {
                        $stat_mod = $stat_mod * 3;
                    } else {
                        $stat_mod = $stat_mod + 2;
                    }
                    
                    $modifier = $modifier + $stat_mod;
                }
            }
        }
        
        // print($modifier);
        return $modifier;
    }
    
    function story_transition($action, $connection) {
        if (having_adventure($connection)) {
            // This is the current event _at this location_ if it equals 0 then this location currently has no event for this story
            // Event_id is wrt. story_id
            $current_event = get_current_event($connection);
            $event = 0;
            if ($current_event != 0) {
                $event = $current_event;
            }
            $location_id = get_location($connection);
            $story_id = get_value_from_users("story", $connection);
            
            $action_id = 0;
            if (is_travel_action($action, $connection)) {
                $action_id = 100;
                $prev_location = get_value_from_users("prev_location", $connection);
                // Nothing interesting happening here, but maybe we came from somewhere with a transition to here.
                // Don't think this should happen any more -- locations of interest now have empty states if no one is there.
                if ($current_event == 0) {
                    $user_id = get_user_id($connection);
                    // BUT prev_location is not a story location
                    //$sql = "SELECT event_id from story_locations_in_play where user_id = '{$user_id}' and location_id = '{$prev_location}'";
                    //$event = select_sql_column($sql, "event_id", $connection);
                    $event = 0;
                    // print("Should we be in this bit of code?");
                    // Apparently we get here if Tardis flying from a non story location.
                }
            }
            
            // $event is id wrt. current story (not unique)
            
            if (is_basic_action($action, $connection)) {
                $action_id = get_value_for_name_from("action_id", "actions", $action, $connection);
            }
            
            $transition_in_table = 1;
            
            // Why might action_id = 0?
            // In what circumstances is the event 0?  When travelling from a non-story location
            if ($action_id > 0 && $event != 0) {
                // Work out which transition we are on
                $travel_type = get_value_from_users("travel_type", $connection);
                
                if ($action_id != 100) {
                    $sql = "SELECT transition_id, probability, modifiers from story_transitions where event_id = '{$event}' and story_id = '{$story_id}' and action_id = '{$action_id}'";
                } else {
                    $sql = "SELECT transition_id, probability, modifiers from story_transitions where event_id = '{$event}' and story_id = '{$story_id}' and action_id = '{$action_id}' and transition_label = '{$travel_type}'";
                }
                // print $sql;
                
                if (!$result = $connection->query($sql)) {
                    // There wasn't a transition for this action id.
                    // showerror($connection);
                    $transition_in_table = 0;
                }
                
                if ($result->num_rows == 0) {
                    //print("A");
                    $transition_in_table = 0;
                }
                //print("C");
            
                if ($transition_in_table) {
                    //print("B");
                    $probability = 0;
                    $dice = rand(0, 100);
                    //print("dice: " . $dice . "\n");
                    
                    // Options appear in order of increasing goodness therefore companion abilities add to dice roll
                    $modifier = modify_dice($action_id, $connection);
                    
                    //print("modifier: " . $modifier);
                    $dice = $dice + $modifier;
                    // print("dice: " . $dice . "\n");
                    if ($dice > 100) {
                        $dice = 100;
                    }
                    while ($row=$result->fetch_assoc()) {
                        $modifiers = $row["modifiers"];
                        if (check_modifiers($modifiers, $connection)) {
                            $probability = $probability + $row["probability"];
                            if ($dice <= $probability) {
                                $transition_id = $row["transition_id"];
                                break;
                            }
                        }
                    }
            
                    
                    make_transition($transition_id, $connection);
                }
            }
        }
        
        $transition = get_value_from_users("last_transition", $connection);

        $sql = "SELECT lost_fight from story_transitions where transition_id = '{$transition}'";
        $lost_fight = select_sql_column($sql, "lost_fight", $connection);
        if ($lost_fight) {
            lost_fight($db);
        }
    }
    
    function transition($from_event, $to_event, $story_id, $connection) {
        if ($from_event != $to_event) {
            $sql = "SELECT transition_id FROM story_transitions where event_id = '{$from_event}' and story_id = '{$story_id}' and outcome='{$to_event}'";
            if ($result = $connection->query($sql)) {
                $row = $result->fetch_assoc();
                $transition_id = $row["transition_id"];
                make_transition($transition_id, $connection);
            }
        }
    }
    
    
function print_transition_outcome($transition_id, $action, $connection) {
// print($action_id);
if ($transition_id != 0) {

// Printing outcome of action
$outcome_text = get_value_for_transition_id("outcome_text", $transition_id, $connection);
$random_character = get_value_for_transition_id("random_character_input", $transition_id, $connection);
$transition_location = get_value_for_transition_id("location_id", $transition_id, $connection);
$location_id =get_location($connection);
if ($location_id == $transition_location) {
if ($random_character) {
    $user_id = get_user_id($connection);
    $location_id = get_location($connection);
    $sql = "SELECT event_character FROM story_locations_in_play WHERE user_id = '$user_id' AND location_id = '$location_id'";
            // print $sql;
    $char = select_sql_column($sql, "event_character", $connection);
    // print("$random_character character: " . $char);
            
            
    $char_name = get_value_for_char_id("name", $char, $connection);
    $outcome_text = $char_name . $outcome_text;
            // print($outcome_text);
}
        
print("<p>$outcome_text</p>");
} else {
print "<p>  &nbsp;</p>";
}
} elseif ($action != '') {
    
if (is_basic_action($action, $connection)) {
    print_action_default($action, $connection);
} else {
    print "<p>  &nbsp;</p>";
}
} else {
print ("<p>&nbsp;</p>");
}
}


    
    function make_transition($transition_id, $connection) {
        // Update story_locations_in_play
        $new_event = get_value_for_transition_id("outcome", $transition_id, $connection);
        $label = get_value_for_transition_id("transition_label", $transition_id, $connection);
        $action_id = get_value_for_transition_id("action_id", $transition_id, $connection);
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $story_id = get_value_from_users("story", $connection);
        
        $sql = "UPDATE users SET last_transition='{$transition_id}' where user_id = '$user_id'";
       // print ($sql);
        if (!$connection->query($sql)) {
            showerror($connection);
        }
        
        // This will collect all the locations currently in play for this user
        $sql = "SELECT location_id FROM story_locations_in_play WHERE user_id ='{$user_id}'";
                 
        if (!$result = $connection->query($sql))
            showerror($connection);
            
        // Update the events at all the locations.
        while($row=$result->fetch_assoc()) {
            $story_location = $row["location_id"];
                      
            if ($story_location == $location_id) {
                  
                 // if ($action_id != 100 || $current_event != 0) {
                $sql = "UPDATE story_locations_in_play SET event_id='{$new_event}' where user_id = '$user_id' and location_id = '$location_id'";
                if (!$connection->query($sql)) {
                    showerror($connection);
                }
              
                $sql = "SELECT story_path from story_locations_in_play WHERE user_id = '{$user_id}' and location_id = '$location_id'";
                $story_path = select_sql_column($sql, "story_path", $connection);
              
                $story_path = $story_path . "," . $new_event;

                $sql = "UPDATE story_locations_in_play SET story_path='{$story_path}' where user_id = '$user_id' and location_id = '$location_id'";
                if (!$connection->query($sql)) {
                    showerror($connection);
                }
            } else {
                //print("A");
                $sql = "SELECT event_id FROM story_locations_in_play WHERE user_id='{$user_id}' AND location_id='$story_location'";
                $location_event = select_sql_column($sql, "event_id", $connection);
                //print("location: " . $story_location);
                //print("current event: " . $location_event);
                  
                //if ($action_id != '100') {
                    $action_id = 0;
                //}
                $sql = "SELECT outcome, transition_id FROM story_transitions WHERE story_id='$story_id' AND location_id='$story_location' AND transition_label = '$label' AND action_id = '${action_id}' AND event_id = '$location_event'";
                
                $location_transition_id = select_sql_column($sql, "transition_id", $connection);
                
                
                //print($sql);
                if ($result2 = $connection->query($sql)) {
                    $new_location_event = select_sql_column($sql, "outcome", $connection);
                    // Nothing for this transition.
                    if ($new_location_event == 0) {
                        $new_location_event = $location_event;
                    }
                   // print("OTHER LOCATION new current event: " . $new_location_event);
                          
                    $sql = "UPDATE story_locations_in_play SET event_id='{$new_location_event}' where user_id = '$user_id' and location_id = '$story_location'";
                    if (!$connection->query($sql)) {
                        showerror($connection);
                    }
                          
                    $sql = "SELECT story_path from story_locations_in_play WHERE user_id = '{$user_id}' and location_id = '$story_location'";
                    $story_path = select_sql_column($sql, "story_path", $connection);
                          
                    $story_path = $story_path . "," . $new_location_event;

                    $sql = "UPDATE story_locations_in_play SET story_path='{$story_path}' where user_id = '$user_id' and location_id = '$story_location'";
                    if (!$connection->query($sql)) {
                            showerror($connection);
                    }
                    
                    if (forced_travel_transition($location_transition_id, $connection)) {
                        // print("A");
                        $travel_info = new forced_travel_info($location_transition_id, $connection);
                        $travel_info->force_travel($connection);
                    }
                }
                          
            }
        }
        
        $random_character = get_value_for_transition_id("random_character_input", $transition_id, $connection);
        //print ("A: $transition_id $random_character $transition_id");
        if ($random_character) {
            $tardis_crew_size = conscious_tardis_crew_size($connection);
            $dice = rand(0, $tardis_crew_size - 1);
            $tardis_crew = conscious_tardis_crew($connection);
            $char = $tardis_crew[$dice];
            //$outcome_text = $char_name . $outcome_text;
            //print($outcome_text);
            // THIS SEEMS TO UPDATE ALL LOCATIONS - I CAN SEE AN ARGUMENT FOR THIS BUT SUSPECT WILL CAUSE ISSUES
            $sql = "UPDATE story_locations_in_play SET event_character = '{$char}' where user_id = '$user_id'";
            //print $sql;
            if (!$connection->query($sql)) {
                showerror($connection);
            }
        }
    }

    
    function get_value_for_transition_id($column, $transition_id, $connection) {
        $sql = "SELECT {$column} from story_transitions where transition_id = '{$transition_id}'";
        
        return select_sql_column($sql, $column, $connection);
    }
        

    function get_transition_id($event_id, $action_id, $story_id, $db) {
        $sql = "SELECT transition_id FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id} AND action_id = '{$action_id}'";
        return select_sql_column($sql, "transition_id", $db);
    }

    
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
    
    
       function remove_modification_status($modifier, $connection) {
           if ($modifier == "transmat") {
               $travel_type = get_value_from_users("travel_type", $connection);
               if ($travel_type == "transmat") {
                   return 1;
               }
           }
           return 0;
       }
    
    
    function travel_while_transition($db, $forced_travel, $travel_type, $travel_info, $starting_location) {
        $location_id = get_location($db);
        $travellers = [];
        
        // Figure out which characters are travelling
        if ($forced_travel) {
             $travellers = characters_at_location($starting_location, $db);
        } else {
            $travellers = $travel_info->travellers;
        }

        // Find new location
        if (!empty($travellers) || $travel_type == "pov_switch") {
            // New location was part of $_POST information
            if ($travel_type == "transmat" || $travel_type == "pov_switch") {
                $location_id = $travel_info->location;
            }
            
            // New location is baked into the transition
            if ($forced_travel) {
                $sql = "SELECT new_location from story_transitions where transition_id = '{$transition}'";
                $location_id = select_sql_column($sql, "new_location", $db);
            }
        
            // New location depends upon Tardis control
            if ($travel_type == "tardis") {
                if (! empty($travellers)) {
                    $dial1 = $travel_info->$dial1;
                    $dial2 = $travel_info->$dial2;
                    $dial3 = $travel_info->$dial3;
                    $dial4 = $travel_info->$dial14;
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
            // Location remains the same
            $location_id = get_location($db);
        }
        
        return $location_id;
    }
    
    class travel_info {
        public $location;
        public $travellers = array();
        public $dial1;
        public $dial2;
        public $dial3;
        public $dial4;
        
        function __construct($location, $traveller1, $traveller2, $traveller3, $traveller4, $dial1, $dial2, $dial3, $dial4) {
            $this->location = $location;

            if ($traveller1 != "") {
                array_push($this->travellers, $traveller1);
            }
 
            if ($traveller2 != "") {
                array_push($this->travellers, $traveller2);
            }

            if ($traveller3 != "") {
                array_push($this->travellers, $traveller3);
            }
 
            if ($traveller4 != "") {
                array_push($this->travellers, $traveller4);
            }
            
            $this->dial1 = $dial1;
            $this->dial2 = $dial2;
            $this->dial3 = $dial3;
            $this->dial4 = $dial4;
        }
    }

?>
