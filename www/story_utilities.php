<?php
    
    function developer_mode() {
        return 1;
    }
    
    //========== Getters
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
    
    function get_value_for_story_id($column, $story_id, $connection) {
        $sql = "SELECT {$column} FROM stories WHERE story_id = '{$story_id}'";
        // print $sql;
        
        $value = select_sql_column($sql, "$column", $connection);
         return $value;
     }
    
    
    function get_story_locations($story_id, $connection) {
        $sql = "SELECT locations from stories WHERE story_id ='{$story_id}'";
        
        $locations = select_sql_column($sql, "locations", $connection);
        $location_array = explode(",", $locations);
        return $location_array;
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
    function get_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT default_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "default_initial", $connection);
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

    
    // This is the default initial event id wrt. to the story - i.e.  not unique
    //function get_not_present_initial_event($story_id, $location_id, $connection) {
     //   $sql = "SELECT not_present_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
    //
    //    return select_sql_column($sql, "not_present_initial", $connection);
   // }
    
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
    
    
    function having_adventure($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            return 1;
        } else {
            return 0;
        }
    }

        
    
    //=============== Starting and Ending Stories
    
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
    
    function fail_story($story_id, $db) {
        quit_story($story_id, $db);
    }
    
    function end_story($story_id, $db) {
        quit_story($story_id, $db);
        
        $story_path = get_value_from_users("story_path", $db);
        
        $user_id = get_user_id($db);
        $sql = "SELECT story_id_list FROM users where user_id = '{$user_id}'";
        $story_id_list = select_sql_column($sql, "story_id_list", $db);
        $story_array = explode(",", $story_id_list);
        $story_id = $story_id . ";;;" . $story_path;
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
    
    function in_end_state($story, $db) {
        $location_id = get_location($db);
        $event_id = get_current_event($db);
        if ($event_id == '') {
            return 1;
        }
        
        $sql = "SELECT text from story_events where event_location = '{$location_id}' AND story_number_id = '{$event_id}' AND story_id = '{$story}'";
         if ($result = $db->query($sql)) {
            if ($result->num_rows == 0) {
                return 0;
            }
            
            
            $sql = "SELECT action_id from story_transitions where story_id = '{$story}' and event_id = '{$event_id}'";
             if (!$result = $db->query($sql))
                showerror($db);
             
            if ($result->num_rows > 0) {
                 return 0;
            } else {
                return 1;
            }

        } else {
            showerror($db);
        }
        
        return 0;
    }
    
    //==============  Story Paths
    
    function create_story_path($story_id, $db) {
        // Get All Story Locations
        $locations = get_story_locations($story_id, $db);
        
        // Figure out relevant Initial Event for each location
        foreach ($locations as $story_location) {
            // And insert a location in play into the table.
           // if (count(characters_at_location($story_location, $db)) > 0) {
            // NO SOMEONE CAN ALWAYS TRAVEL IN
                $initial_event = get_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, location_id) VALUES ($initial_event, $story_id, $user_id, $story_location)";
                if (!$result = $db->query($sql))
                    showerror($db);
            
                $sql = "UPDATE users SET story_path = '' WHERE user_id = '{$user_id}'";
                 
                if (!$result = $db->query($sql))
                    showerror($db);
         }
    }
    
    // Debugging function
    // Not sure all of this makes sense
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
                
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, location_id) VALUES ($initial_event, $story_id, $user_id, $story_location)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
                
                $sql = "UPDATE users SET story_path = '' WHERE user_id = '{$user_id}'";
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
                    
                $sql = "INSERT INTO story_locations_in_play (event_id, story_id, user_id, location_id) VALUES ($initial_event, $story_id, $user_id, $story_location)";
                    // print $sql;
                    
                $sql = "UPDATE users SET story_path = '' WHERE user_id = '{$user_id}'";
                if (!$result = $db->query($sql))
                    showerror($db);
            
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
        
        $sql = "UPDATE users SET story_path = '' WHERE user_id = '{$user_id}'";
         
        if (!$result = $db->query($sql))
            showerror($db);
    }

    function update_path($sentence, $db) {
        $story_path = get_value_from_users("story_path", $db);
        
        $path_array = explode(":::", $story_path);
        
        
        $sentence = str_replace("\'", "'", $sentence);
        //$sentence = str_replace("'", "\'", $sentence);
        
        if (!in_array($sentence, $path_array)) {
            if ($story_path != '') {
                $story_path = $story_path . ":::" . $sentence;
            } else {
                $story_path = $sentence;
            }
        }
        
        //$sentence = str_replace("\'", "'", $sentence);
        $story_path = str_replace("'", "\'", $story_path);

        update_users("story_path", $story_path, $db);

    }

    function update_path_action($action_string, $db) {
        $story_path = get_value_from_users("story_path", $db);
        if ($action_string == "") {
            $action_string == " ";
        }
        
        
        
        $location_id = get_location($db);
        $location_name = get_value_for_location_id("name", $location_id, $db);
        foreach (characters_at_location($location_id, $db) as $char_id) {
            $char_name = get_value_for_char_id("name", $char_id, $db);
            $action_string = $char_name . "+" . $action_string;
        }
        $action_string = $location_name . "+" . $action_string;
        
        $path_array = explode(":::", $story_path);
        
        //print("A");
        $sentence = str_replace("\'", "'", $action_string);
        // $sentence = str_replace("'", "\'", $sentence);
        // print ($sentence);
        
        
        $story_path = $story_path . ":::" . $sentence;
        
        //$sentence = str_replace("\'", "'", $sentence);
        $story_path = str_replace("'", "\'", $story_path);
        // print($story_path);

        update_users("story_path", $story_path, $db);
        //print("B");

    }

    function print_path($db) {
        //print("START PRINT");
        $user_id = get_user_id($db);
        $sql = "SELECT story_path from users WHERE user_id = '{$user_id}'";
        $story_path = select_sql_column($sql, "story_path", $db);
        
        $path_array = explode(":::", $story_path);
        
        $event = 1;
        $story = "";
        $location = "";
        foreach ($path_array as $path_item) {
                if ($event == 1) {
                    $story = $story . $path_item;
                    $event = 0;
                    //print("A");
                    //print($story);
                } else {
                    $event_info_array = explode("+", $path_item);
                    $length = count($event_info_array);
                    if ($location != "" && $event_info_array[0] != $location  && $event_info_array[$length - 1] == "pov_switch") {
                        $location = $event_info_array[0];
                        $story = $story . "  Meanwhile at " . "$location where ";
                        for ($chars = 1; $chars < count($event_info_array) - 1; $chars++) {
                            if ($chars != 1) {
                                $story = $story . " and ";
                            }
                            $story = $story . "$event_info_array[$chars]";
                        }
                        $story = $story . " are.  ";
                    } else {
                        $location = $event_info_array[0];
                    }
                    if ($event_info_array[$length - 1] != "pov_switch") {
                        $story = $story . $event_info_array[count($event_info_array) - 1];
                        }
                    $event = 1;
                    //print("B");
                    //print($story);
                }
        }
        
        print($story);
        //print("LEAVING PRINT PATH");
    }

    //=============== Logs
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
    
    
    //===============  Printing Functions
    
    // Start story button
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

    // Debugging/Testing Support
    function go_to_event($story_id_number, $connection) {
        $story_id = get_value_from_users("story", $connection);
        quit_story($story_id, $connection);
        begin_story($story_id, $connection);
        $location_id = get_location($connection);
        $initial_event = get_initial_event($story_id, $location_id, $connection);
        $elsewhere_events = get_elsewhere_events($connection);
        $path = find_path_to($initial_event, $story_id_number, $elsewhere_events, $story_id, $connection);
         if (is_null($path)) {
            quit_story($story_id, $connection);
            begin_story_elsewhere($story_id, $connection);
            $initial_event=$not_present_event;
            $elsewhere_events = get_elsewhere_events($connection);
            $path = find_path_to($not_present_event, $story_id_number, $elsewhere_events, $story_id, $connection);
         }
        $from_event = $initial_event;
        if (!is_null($path)) {
            $rpath = array_reverse($path);
            foreach ($rpath as $event) {
                transition($from_event, $event, $story_id, $connection);
                $from_event = $event;
            }
         }
    }
    
    function find_path_to($event1, $event2, $elsewhere_events, $story_id, $connection) {
        if ($event1 == $event2) {
            return array($event1);
        } else {
            $sql = "SELECT outcome, transition_label from story_transitions where event_id = '{$event1}' and story_id = '{$story_id}'";
              
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
                     
                      if ($next_event != $event1) {
                         $path = find_path_to($next_event, $event2, $story_id, $connection);
                         if ($path != NULL) {
                              $path[] = $event1;
                             return $path;
                         }
                     }
                 }
                 return NULL;
             }

        }
    }
    

    ?>
