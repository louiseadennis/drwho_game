<?php
    function print_event($db) {
        $event = get_current_event($db);
        $text = get_event_text($event, $db);
        if ($text != '') {
            print "$text<br>";
        } else {
            print "<br>";
        }
    }
    
    function print_event_long($db) {
        $event = get_current_event($db);
        $description = get_event_description($event, $db);
        print "<br>";
        
        $critters = get_event_critters($event, $db);
        if (!empty($critters)) {
            foreach ($critters as $critter_id) {
                add_critter($critter_id, $db);
                
                $icon = get_value_for_critter_id("icon", $critter_id, $db);
                // print $icon;
                print("<img src=../assets/$icon> ");
            }
        }
        
        $allies = get_event_allies($event, $db);
        if (!empty($allies)) {
            foreach ($allies as $ally_id) {
                add_ally($ally_id, $db);
                
                $icon = get_value_for_ally_id("icon", $ally_id, $db);
                // print $icon;
                print("<img src=../assets/$icon> ");
            }
        }
        
        if ($description != '')
            {
                print "$description<br>";
            }
        

        print "<br>";
        
        
    }

    // This returns event_id wrt. story id
    function get_current_event($connection) {
        $story = get_value_from_users("story", $connection);
        if ($story != 0 && $story != '') {
            $location_id = get_location($connection);
            $user_id = get_user_id($connection);
            
            $sql = "SELECT event_id from story_locations_in_play where user_id = '{$user_id}' and location_id = '{$location_id}' and story_id = '{$story}'";
            // print($sql);
            
            return select_sql_column($sql, "event_id", $connection);
        }
    }

    function get_event_text($event_id, $connection) {
        $story = get_value_from_users("story", $connection);
        
        $sql = "SELECT text from story_events where story_number_id = '{$event_id}' and story_id = '{$story}'";
        
        return select_sql_column($sql, "text", $connection);
    }

    function get_event_description($event_id, $connection) {
        $story = get_value_from_users("story", $connection);
        
        $sql = "SELECT description from story_events where story_number_id = '{$event_id}' and story_id = '{$story}'";
        
        return select_sql_column($sql, "description", $connection);

    }

    function get_event_allies($event_id, $connection) {
        $story = get_value_from_users("story", $connection);
        
        $sql = "SELECT ally_id_list from story_events where story_number_id = '{$event_id}' and story_id = '{$story}'";
        
        $ally_list =  select_sql_column($sql, "ally_id_list", $connection);
        if ($ally_list != "") {
            $ally_array = explode(",", $ally_list);
            return $ally_array;
        } else {
            return [];
        }

    }

    function get_event_critters($event_id, $connection) {
        $story = get_value_from_users("story", $connection);
        
        $sql = "SELECT critter_id_list from story_events where story_number_id = '{$event_id}' and story_id = '{$story}'";
        
        $critter_list =  select_sql_column($sql, "critter_id_list", $connection);
        if ($critter_list != "") {
            $critter_array = explode(",", $critter_list);
            return $critter_array;
        } else {
                   return [];
        }

    }



?>
