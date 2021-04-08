<?php
    
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
