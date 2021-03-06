<?php
    //================================== Making a transition (without travel parts)

    
    //  Make a transition between two events (used for testing)
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
    
    //  An action has been performed - what should happen?
    function story_transition($action, $connection) {
        if (having_adventure($connection)) {
           // This is the event at the location the user is currently viewing
            $event = get_current_event($connection);
             $story_id = get_value_from_users("story", $connection);
 
            //  Get the action_id
            $action_id = 0;
            if (is_travel_action($action, $connection)) {
                $action_id = 100;
                $prev_location = get_value_from_users("prev_location", $connection);
            } elseif (is_basic_action($action, $connection)) {
                $action_id = get_value_for_name_from("action_id", "actions", $action, $connection);
            }
            
            $transition_in_table = 1;
            
            // Why might action_id = 0? Probably because of a bug.
            // In what circumstances is the event 0?  When travelling from a non-story location
            if ($action_id > 0 && $event != 0) {
                
                // Get all possible transitions for this action and event.
                if ($action_id != 100) {
                    $sql = "SELECT transition_label, transition_id, probability, modifiers from story_transitions where event_id = '{$event}' and story_id = '{$story_id}' and action_id = '{$action_id}'";
                } else {
                    $travel_type = get_value_from_users("travel_type", $connection);

                    $sql = "SELECT transition_label, transition_id, probability, modifiers from story_transitions where event_id = '{$event}' and story_id = '{$story_id}' and action_id = '{$action_id}' and travel_type = '{$travel_type}'";
                }
                
                // No transition....
                if (!$result = $connection->query($sql)) {
                    // There wasn't a transition for this action id.
                    // showerror($connection);
                    $transition_in_table = 0;
                }
                if ($result->num_rows == 0) {
                     $transition_in_table = 0;
                }
            
                // A transition will occur.  Figure out dice roll and use it to pick the relevant transition (where more than 1 option)
                if ($transition_in_table) {
                    $probability = 0;
                    $dice = rand(0, 100);
                    
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
                                $transition_label = $row["transition_label"];
                                break;
                            }
                        }
                    }
            
                    
                    make_transition($transition_label, $connection, $action_id);
                }
            }
        }
        
     }
    

    // Transition ID is specific to location while transition label is cross location
    function make_transition($transition_label, $connection, $action_id) {
        $user_id = get_user_id($connection);
         
        // This will collect all the locations currently in play for this user
        $sql = "SELECT location_id FROM story_locations_in_play WHERE user_id ='{$user_id}'";
        if (!$result = $connection->query($sql))
            showerror($connection);
        $location_id = get_location($connection);
            
        // Update the events at all the locations.
        while($row=$result->fetch_assoc()) {
            $story_location = $row["location_id"];
            $transition_info = new transition_info($story_location, $transition_label, $connection, $action_id);
            $new_event = $transition_info->outcome_event;
            $sql = "UPDATE story_locations_in_play SET event_id='{$new_event}' where user_id = '$user_id' and location_id = '$story_location'";
             if (!$connection->query($sql)) {
                showerror($connection);
            }
            $transition_id = $transition_info->transition_id;

            //$sql = "SELECT story_path from story_locations_in_play WHERE user_id = '{$user_id}' and location_id = '$story_location'";
            //$story_path = select_sql_column($sql, "story_path", $connection);
            // $story_path = $story_path . "," . $new_event;
            // $story_path = $story_path . "," . $new_location_event;
            //$sql = "UPDATE story_locations_in_play SET story_path='{$story_path}' where user_id = '$user_id' and location_id = '$location_id'";
            //if (!$connection->query($sql)) {
            //    showerror($connection);
            //}

            // If we're not at the location where the action took place it is possible the characters are moved to this location.
            if ($story_location != $location_id) {
                
                if (forced_travel_transition($transition_info->transition_id, $connection)) {
                    $travel_info = new forced_travel_info($location_transition_id, $connection);
                    $travel_info->force_travel($connection);
                }
            } else {
                $sql = "UPDATE users SET last_transition='{$transition_id}' where user_id = '$user_id'";
                // print($sql);
                if (!$connection->query($sql)) {
                    showerror($connection);
                }
                
                $sql = "SELECT lost_fight from story_transitions where transition_id = '{$transition_id}'";
                $lost_fight = select_sql_column($sql, "lost_fight", $connection);
                if ($lost_fight) {
                    lost_fight($db);
                }
                
            }

            $random_character = get_value_for_transition_id("random_character_input", $transition_id, $connection);
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
        

    }

    
    // ======================= Basic support functions
    // Bonuses to dice rolls based on character skills
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
        return $modifier;
    }



    //============== Print Support
    function print_transition_outcome($transition_id, $action, $connection) {
        // print($action_id);
        // print($transition_id);
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
    
    //==================================  Travel
    // Does this transition involve forced travel?
    function forced_travel_transition($transition, $db) {
        $sql = "SELECT action_id, force_travel from story_transitions where transition_id = '{$transition}'";
        $action_id = select_sql_column($sql, "action_id", $db);
        // Am I sure it's only 100 and 0?
        if ($action_id == 100 || $action_id == 0) {
            return select_sql_column($sql, "force_travel", $db);
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

    //====================== DB support
    function get_value_for_transition_id($column, $transition_id, $connection) {
        $sql = "SELECT {$column} from story_transitions where transition_id = '{$transition_id}'";
        
        return select_sql_column($sql, $column, $connection);
    }
        

    function get_transition_id($event_id, $action_id, $story_id, $db) {
        $sql = "SELECT transition_id FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id} AND action_id = '{$action_id}'";
        return select_sql_column($sql, "transition_id", $db);
    }

    

    
    // ====================== Support classes
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

    // Class containing relevant information about a forced travel
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


    // What does a transition do for this event at this location?
    class transition_info {
        public $outcome_event;
        public $label;
        public $action_id;
        public $location_id;
        public $story_id;
        public $transition_id;
        public $location_event;
        
        function __construct($location_id, $transition_label, $db, $action_id) {
            $this->label = $transition_label;
            $this->location_id =$location_id;
            if ($location_id == get_location($db)) {
                $this->action_id = $action_id;
            } else {
                $this->action_id = 0;
            }
            $this->story_id = get_value_from_users("story", $db);
            $user_id = get_user_id($db);
            $sql = "SELECT event_id FROM story_locations_in_play WHERE user_id='{$user_id}' AND location_id='$location_id'";
            $this->location_event = select_sql_column($sql, "event_id", $db);
            $sql = "SELECT outcome, transition_id FROM story_transitions WHERE story_id='$this->story_id' AND location_id='$this->location_id' AND transition_label = '$transition_label' AND action_id = '$this->action_id' AND event_id = '$this->location_event'";
            $this->outcome_event = select_sql_column($sql, "outcome", $db);
            $this->transition_id = select_sql_column($sql, "transition_id", $db);
            
         }
    }


?>
