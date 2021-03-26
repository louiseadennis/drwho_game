<?php
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

?>
