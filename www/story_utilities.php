<?php
    
    function start_story($story_id, $db) {
        if ($story_id != 0) {
            $story = get_value_from_users("story", $db);
            if ($story == '0') {
                $story_name = get_value_for_story_id("title", $story_id, $db);
                // print "<u>$story_name</u>";
                print "<form method=\"POST\" action=\"../main.php\"><input type=hidden name=\"start_story\", value=\"$story_id\">";
                print "New Adventure: ";
                print "<input type=submit value=\"Start $story_name\"></form>";
            }
        } else {
            $story = get_value_from_users("story", $db);
            if ($story == '0') {
                print "<form></form>";
            }
        }
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
        $initial_event = get_initial_event($story_id, $location_id, $db);
        $user_id = get_user_id($db);
        
        $sql = "INSERT INTO story_locations_in_play (event_id, user_id, story_path) VALUES ($initial_event, $user_id, \"\")";
        // print $sql;
        
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
     }

    
    function get_value_for_story_id($column, $story_id, $connection) {
        $sql = "SELECT {$column} FROM stories WHERE story_id = '{$story_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["$column"];
                return $value;
            }
        }
    }
    
    function get_story_events($story_id, $location_id, $connection) {
        $sql = "SELECT events from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["events"];
                return $value;
            }
        }
    }
    
    function get_initial_event($story_id, $location_id, $connection) {
        $sql = "SELECT default_initial from story_locations WHERE story_id = '{$story_id}' AND location_id = '{$location_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["default_initial"];
                return $value;
            }
        }
    }
    
    

    ?>
