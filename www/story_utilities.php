<?php
    
    function developer_mode() {
        return 1;
    }
    
    function story_number($connection) {
        $sql = "SELECT * FROM stories";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        return $result->num_rows;
    }

    function get_value_for_story_modifier_id($column, $modifier_id, $connection) {
         $sql = "SELECT {$column} FROM story_modifiers where modifier_id = '{$modifier_id}'";
         
         return select_sql_column($sql, $column, $connection);
     }
    
    
    function start_story($story_id, $db) {
        if ($story_id != 0) {
            $story = get_value_from_users("story", $db);
            if (in_end_state($story, $db)) {
                $story = 0;
            }
            if ($story == '0' || $story == '') {
                $story_name = get_value_for_story_id("title", $story_id, $db);
                print "<form method=\"POST\" action=\"../main.php\"><input type=hidden name=\"start_story\", value=\"$story_id\">";
                print "New Adventure: ";
                print "<input type=submit value=\"Start $story_name\"></form>";
            }
        }
    }
    
    function begin_story($story_id, $db) {
        update_users("story", $story_id, $db);
        create_story_path($story_id, $db);
     }
    
    function begin_story_elsewhere($story_id, $db) {
        update_users("story", $story_id, $db);
        create_story_path_elsewhere($story_id, $db);
     }

    
    function quit_story($story_id, $db) {
        create_log($story_id, $db);
        update_users("story", 0, $db);
        clear_story_path($db);
        
        $location_id = get_location($db);
        $char_id_array = characters_at_location($location_id, $db);
        foreach ($char_id_array as $char_id) {
            clear_all_modifiers_except_health($char_id, $db);
        }
        
        
    }
    
    function in_end_state($story, $db) {
        $location_id = get_location($db);
        $event_id = get_current_event($db);
        
        $sql = "SELECT text from story_events where event_location = '{$location_id}' AND story_number_id = '{$event_id}' AND story_id = '{$story}'";
        // print($sql);
        if ($result = $db->query($sql)) {
            if ($result->num_rows == 0) {
                return 0;
            }
            
            
            $sql = "SELECT action_id from story_transitions where story_id = '{$story}' and event_id = '{$event_id}'";
            if (!$result = $db->query($sql))
                showerror($db);
             
            if ($result->num_rows > 0) {
                // print("B");
                return 0;
            } else {
                // print("C $sql");
                return 1;
            }

        } else {
            showerror($db);
        }
        
        return 0;
    }
    
    function fail_story($story_id, $db) {
        quit_story($story_id, $db);
    }
    
    function end_story($story_id, $db) {
        quit_story($story_id, $db);
        
        $user_id = get_user_id($db);
        $sql = "SELECT story_id_list FROM users where user_id = '{$user_id}'";
        $story_id_list = select_sql_column($sql, "story_id_list", $db);
        $story_array = explode(",", $story_id_list);
        if (empty($story_array)) {
            $sql = "UPDATE users SET story_id_list='{$story_id}' where user_id = '{$user_id}'";
            if (!$db->query($sql)) {
                showerror($db);
            }
        } else {
            if (! in_array($story_id, $story_array)) {
                $story_id_list = $story_id_list . "," . $story_id;
                
                $sql = "UPDATE users SET story_id_list='{$story_id_list}' where user_id = '{$user_id}'";
                if (!$db->query($sql)) {
                    showerror($db);
                }
            }
        }
    }
    
    function create_story_path($story_id, $db) {
        // $location_id = get_location($db);
        
        // Get All Story Locations
        $locations = get_story_locations($story_id, $db);
        
        // Figure out relevant Initial Event for each location
        foreach ($locations as $story_location) {
            // And insert a location in play into the table.
           // if (count(characters_at_location($story_location, $db)) > 0) {
            // NO SOMEONE CAN ALWAYS TRAVEL IN
                // $initial_event id number is wrt. story_id not unique
                $initial_event = get_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, story_path, location_id) VALUES ($initial_event, $story_id, $user_id, \"\", $story_location)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
           // } else {
            //    $initial_event = get_not_present_initial_event($story_id, $story_location, $db);//
           //     $user_id = get_user_id($db);//
                
           //     $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, story_path, location_id) VALUES ($initial_event, $story_id, $user_id, \"\", $story_location)";
                // print $sql;
                
           //     if (!$result = $db->query($sql))
          //          showerror($db);
          //  }
        }
    }
    
    function get_initial_events($story_id, $db) {
        $locations = get_story_locations($story_id, $db);
        $initial_events = array();
        foreach ($locations as $story_location) {
            $initial_event = get_initial_event($story_id, $story_location, $db);
            array_push($initial_events, $initial_event);
        }
        return $initial_events;
    }
    
    function create_story_path_elsewhere($story_id, $db) {
        $location_id = get_location($db);
        
        // Get All Story Locations
        $locations = get_story_locations($story_id, $db);
        
        $one_starting_location = 0;
        
        // Figure out relevant Initial Event for each location
        foreach ($locations as $story_location) {
            // And insert a location in play into the table.
            if (count(characters_at_location($story_location, $db)) > 0 && $location_id != $story_location) {
                // $initial_event id number is wrt. story_id not unique
                $initial_event = get_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, story_path, location_id) VALUES ($initial_event, $story_id, $user_id, \"\", $story_location)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
                
                $one_starting_location = 1;
            }
        }
        
        foreach ($locations as $story_location) {
            // And insert a location in play into the table.
            // if ($location_id != $story_location && !$one_starting_location) {
                    // $initial_event id number is wrt. story_id not unique
                $initial_event = get_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                    
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, story_path, location_id) VALUES ($initial_event, $story_id, $user_id, \"\", $story_location)";
                    // print $sql;
                    
                if (!$result = $db->query($sql))
                    showerror($db);
                    
                $one_starting_location = 1;
            /* } elseif (count(characters_at_location($story_location, $db)) == 0 || $location_id == $story_location) {
                $initial_event = get_not_present_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, story_path, location_id) VALUES ($initial_event, $story_id, $user_id, \"\", $story_location)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
            } */
        }
        
        if (!$one_starting_location) {
            print ("ERROR ERROR in create_story_path_elsewhere");
        }
    }

    
    function create_log($story_id, $db) {
        $locations = get_story_locations($story_id, $db);
        
        $combined_path = "";
        foreach ($locations as $story_location) {
            $user_id = get_user_id($db);
            $sql = "SELECT story_path FROM story_locations_in_play where user_id = '{$user_id}' and location_id = '{$story_location}'";
            $story_path = select_sql_column($sql, "story_path", $db);
            $combined_path = $combined_path . ":" . $story_path;
        }
        
        $sql = "INSERT INTO story_logs (story_id, user_id, story_path) VALUES ($story_id, $user_id, '$combined_path')";
        if (!$result = $db->query($sql))
            showerror($db);
        
    }
    
    function clear_story_path($db) {
        $user_id = get_user_id($db);
         
         $sql = "DELETE FROM story_locations_in_play WHERE user_id = '{$user_id}'";
        // print $sql;
        
        if (!$result = $db->query($sql))
            // print "error";
            showerror($db);
        
        $sql = "UPDATE users SET last_transition = NULL WHERE user_id = '{$user_id}'";
               if (!$result = $db->query($sql))
                    showerror($db);
         }

    
    function get_value_for_story_id($column, $story_id, $connection) {
        $sql = "SELECT {$column} FROM stories WHERE story_id = '{$story_id}'";
        // print $sql;
        
        $value = select_sql_column($sql, "$column", $connection);
         return $value;
     }
    
    // Commenting out since I've not been updating these event lists - is this being used?
    //function get_story_events($story_id, $location_id, $connection) {
    //    $sql = "SELECT events from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
    //
    //    return select_sql_column($sql, "events", $connection);
    //}
    
    function get_story_locations($story_id, $connection) {
        $sql = "SELECT locations from stories WHERE story_id ='{$story_id}'";
        
        $locations = select_sql_column($sql, "locations", $connection);
        $location_array = explode(",", $locations);
        return $location_array;
    }
    
    // This is the default initial event id wrt. to the story - i.e.  not unique
    function get_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT default_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "default_initial", $connection);
    }
    
    function get_location_events($story_id, $location_id, $connection) {
        $sql = "SELECT story_number_id FROM story_events WHERE story_id = '{$story_id}' AND event_location = '{$location_id}'";
        if (!$connection->query($sql)) {
            showerror($connection);
            return [];
        } else {
            return $connection->query($sql);
        }
    }
    
    // This is the default initial event id wrt. to the story - i.e.  not unique
    function get_not_present_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT not_present_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "not_present_initial", $connection);
    }

    
    function go_to_event($story_id_number, $connection) {
        $story_id = get_value_from_users("story", $connection);
        quit_story($story_id, $connection);
        begin_story($story_id, $connection);
        $location_id = get_location($connection);
        $initial_event = get_initial_event($story_id, $location_id, $connection);
        // $not_present_event = get_not_present_initial_event($story_id, $location_id, $connection);
        $elsewhere_events = get_elsewhere_events($connection);
        $path = find_path_to($initial_event, $story_id_number, $elsewhere_events, $story_id, $connection);
        // print($path);
        if (is_null($path)) {
            // print("Trying not present<br>");
            quit_story($story_id, $connection);
            begin_story_elsewhere($story_id, $connection);
            $initial_event=$not_present_event;
            $elsewhere_events = get_elsewhere_events($connection);
            $path = find_path_to($not_present_event, $story_id_number, $elsewhere_events, $story_id, $connection);
            // print($path);
        }
        $from_event = $initial_event;
        // print($path);
        if (!is_null($path)) {
            $rpath = array_reverse($path);
            foreach ($rpath as $event) {
                transition($from_event, $event, $story_id, $connection);
                $from_event = $event;
            }
            // print("HI");
        }
    }
    
    function find_path_to($event1, $event2, $elsewhere_events, $story_id, $connection) {
        //print($event2);
        if ($event1 == $event2) {
            //print($event1);
            return array($event1);
        } else {
            $sql = "SELECT outcome, transition_label from story_transitions where event_id = '{$event1}' and story_id = '{$story_id}'";
            // print($sql);
             
             if (!$result = $connection->query($sql)) {
                 // This event has no transitions
                 return NULL;
             } else {
                 while ($row=$result->fetch_assoc()) {
                     $next_event = $row["outcome"];
                     $transition_label = $row["transition_label"];
                     
                     $can_transition = 1;
                     foreach ($elsewhere_events as $next_elsewhere) {
                         $sql = "SELECT outcome from story_transitions where event_id = '{$next_elsewhere}' and story_id = '{$story_id} and transition_label = '{$transition_label}'";
                     }
                     
                     // print($next_event);
                     if ($next_event != $event1) {
                         $path = find_path_to($next_event, $event2, $story_id, $connection);
                         if ($path != NULL) {
                             // print $path;
                             $path[] = $event1;
                             return $path;
                         }
                     }
                 }
                 return NULL;
             }

        }
    }
    
    function first_visit($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            $location_id = get_location($connection);
            $user_id = get_user_id($connection);
            
            $sql = "SELECT first_visit from story_locations_in_play where user_id = '{$user_id}' and location_id = '{$location_id}'";
            
            return select_sql_column($sql, "first_visit", $connection);
        }
    }
    
    function visited($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            $location_id = get_location($connection);
            $user_id = get_user_id($connection);

            $sql = "UPDATE story_locations_in_play SET first_visit='0' where user_id = '$user_id' and location_id = '$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
        }
    }
    
    function having_adventure($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            return 1;
        } else {
            return 0;
        }
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
        
    function get_story_event_id($story_id, $event_id, $db) {
        $sql = "SELECT story_event_id from story_events where story_id = '{$story_id}' and story_number_id = '{$event_id}'";
        
        return select_sql_column($sql, "story_event_id", $db);
    }
    
    function get_event_character($connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $sql = "SELECT event_character from story_locations_in_play WHERE user_id = '$user_id' and location_id = '$location_id'";
        // print ($sql);
        
        return select_sql_column($sql, "event_character", $connection);
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
    
    function check_modifiers($modifiers, $connection) {
        $modifier_array = explode(",", $modifiers);
        if ($modifiers == '') {
            return 1;
        } else {
            // print($modifiers);
            if ($modifiers == 'doctor present' || in_array('doctor present', $modifier_array)) {
                if (!doctor_here($connection)) {
                    return 0;
                }
                $doctor_id = current_doctor($connection);
                if (!is_conscious($doctor_id, $connection)) {
                    return 0;
                }
            }
        }
        
        return 1;
    }
    
    function get_stories($db) {
        $sql = "SELECT story_id FROM stories";
        if (!$result = $db->query($sql))
            showerror($connection);
        $stories = [];
        while ($row=$result->fetch_assoc()) {
            array_push($stories, $row["story_id"]);
        }
        return $stories;
    }
    
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
        

    ?>
