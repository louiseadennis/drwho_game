<?php
    
    function start_story($story_id, $db) {
        if ($story_id != 0) {
            $story = get_value_from_users("story", $db);
            if ($story == '0' || $story == '') {
                $story_name = get_value_for_story_id("title", $story_id, $db);
                // print "<u>$story_name</u>";
                print "<form method=\"POST\" action=\"../main.php\"><input type=hidden name=\"start_story\", value=\"$story_id\">";
                print "New Adventure: ";
                print "<input type=submit value=\"Start $story_name\"></form>";
            }
        } //else {
            // $story = get_value_from_users("story", $db);
            //if ($story == '0') {
            //    print "Hello";
            //}
       // }
    }
    
    function begin_story($story_id, $db) {
        update_users("story", $story_id, $db);
        create_story_path($story_id, $db);
     }
    
    function quit_story($story_id, $db) {
        update_users("story", 0, $db);
        clear_story_path($db);
    }
    
    function create_story_path($story_id, $db) {
        $location_id = get_location($db);
        
        // Get All Story Locations
        $locations = get_story_locations($story_id, $db);
        
        // Figure out relevant Initial Event for each location
        foreach ($locations as $story_location) {
            // And insert a location in play into the table.
            if ($location_id == $story_location) {
                $initial_event = get_initial_event($story_id, $location_id, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, user_id, story_path, location_id) VALUES ($initial_event, $user_id, \"\", $location_id)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
            } else {
                $initial_event = get_not_present_initial_event($story_id, $story_location, $db);
                $user_id = get_user_id($db);
                
                $sql = "INSERT INTO story_locations_in_play (event_id, user_id, story_path, location_id) VALUES ($initial_event, $user_id, \"\", $story_location)";
                // print $sql;
                
                if (!$result = $db->query($sql))
                    showerror($db);
            }
        }
    }
    
    function clear_story_path($db) {
        $user_id = get_user_id($db);
         
         $sql = "DELETE FROM story_locations_in_play WHERE user_id = '{$user_id}'";
        // print $sql;
        
        if (!$result = $db->query($sql))
            // print "error";
            showerror($db);
     }

    
    function get_value_for_story_id($column, $story_id, $connection) {
        $sql = "SELECT {$column} FROM stories WHERE story_id = '{$story_id}'";
        // print $sql;
        
        $value = select_sql_column($sql, "$column", $connection);
         return $value;
     }
    
    function get_story_events($story_id, $location_id, $connection) {
        $sql = "SELECT events from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "events", $connection);
    }
    
    function get_story_locations($story_id, $connection) {
        $sql = "SELECT locations from stories WHERE story_id ='{$story_id}'";
        
        $locations = select_sql_column($sql, "locations", $connection);
        $location_array = explode(",", $locations);
        return $location_array;
    }
    
    function get_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT default_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "default_initial", $connection);
    }
    
    function get_not_present_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT not_present_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        return select_sql_column($sql, "not_present_initial", $connection);
    }

    function get_current_event($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            $location_id = get_location($connection);
            $user_id = get_user_id($connection);
            
            $sql = "SELECT event_id from story_locations_in_play where user_id = '{$user_id}' and location_id = '{$location_id}'";
            
            return select_sql_column($sql, "event_id", $connection);
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
            $current_event = get_current_event($connection);
            $event = 0;
            if ($current_event != 0) {
                $event = $current_event;
            }
            $location_id = get_location($connection);
            
            $action_id = 0;
            if (is_travel($action, $connection)) {
                $action_id = 100;
                $prev_location = get_value_from_users("prev_location", $connection);
                // Nothing interesting happening here, but maybe we came from somewhere with a transition to here.
                if ($current_event == 0) {
                    $user_id = get_user_id($connection);
                    $sql = "SELECT event_id from story_locations_in_play where user_id = '{$user_id}' and location_id = '{$prev_location}'";
                    $event = select_sql_column($sql, "event_id", $connection);
                }
            }
            
            if (is_action($action, $connection)) {
                $action_id = get_value_for_name_from("action_id", "actions", $action, $connection);
            }
            
            if ($action_id > 0 && $event != 0) {
                // Work out which transition we are on
                $sql = "SELECT transition_id, probability from story_transitions where event_id = '{$event}' and action_id = '{$action_id}'";
                //print $sql;
                if (!$result = $connection->query($sql))
                    showerror($connection);
            
                $probability = 0;
                $dice = rand(0, 100);
                while ($row=$result->fetch_assoc()) {
                    $probability = $probability + $row["probability"];
                    if ($dice <= $probability) {
                        $transition_id = $row["transition_id"];
                        break;
                    }
                }
            
                // Update story_locations_in_play
                $new_event = get_value_for_transition_id("outcome", $transition_id, $connection);
                $user_id = get_user_id($connection);
         
                if ($action_id != 100 || $current_event != 0) {
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
                    $sql = "INSERT INTO story_locations_in_play (event_id, user_id, story_path, location_id) VALUES ($new_event, $user_id, \"\", $location_id)";
                    
                    if (!$result = $connection->query($sql)) {
                        showerror($connection);
                    }
                
                }
                
                // Print action
                $outcome_text = get_value_for_transition_id("outcome_text", $transition_id, $connection);
                $random_character = get_value_for_transition_id("random_character_input", $transition_id, $connection);
                if ($random_character) {
                    $tardis_crew_size = conscious_tardis_crew_size($connection);
                    $dice = rand(0, $tardis_crew_size - 1);
                    $tardis_crew = get_value_from_users("tardis_team", $connection);
                    $crew_array = explode(",", $tardis_crew);
                    $char = $crew_array[$dice];
                    $char_name = get_value_for_char_id("name", $char, $connection);
                    $outcome_text = $char_name . $outcome_text;
                
                    $sql = "UPDATE story_locations_in_play SET event_character = '{$char}' where user_id = '$user_id'";
                    // print $sql;
                    if (!$connection->query($sql)) {
                        showerror($connection);
                    }
                }
            
                print("<p>$outcome_text</p>");
            } else {
                print ("<p>&nbsp;</p>");
            }
        } else {
            print ("<p>&nbsp;</p>");
        }
    }
    
    function get_value_for_transition_id($column, $transition_id, $connection) {
        $sql = "SELECT {$column} from story_transitions where transition_id = '{$transition_id}'";
        
        return select_sql_column($sql, $column, $connection);
    }
    
    function get_event_text($event_id, $connection) {
        $sql = "SELECT text from story_events where story_event_id = '{$event_id}'";
        
        return select_sql_column($sql, "text", $connection);
    }
    
    function get_event_character($connection) {
        $user_id = get_user_id($connection);
        $sql = "SELECT event_character from story_locations_in_play WHERE user_id = '$user_id'";
        
        return select_sql_column($sql, "event_character", $connection);
    }
        

    ?>
