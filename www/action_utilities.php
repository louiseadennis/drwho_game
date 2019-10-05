<?php
    function is_action($action, $db) {
        $sql = "SELECT * from actions WHERE name = '$action'";
        if (!$result = $db->query($sql))
            return false;
        if ($result->num_rows == 0)
            return false;
        
        return true;
    }
    
    function is_travel($action, $db) {
        return $action == "travel";
    }
    
    function print_action_default($action, $connection) {
        if (doctor_here($connection)) {
            $message = get_value_for_name_from("default_message_doctor", "actions", $action, $connection);
                print "<p>$message</p>";
        } else {
            $location_id = get_location($connection);
            $characters = characters_at_location($location_id, $connection);
            $needs_name = get_value_for_name_from("needs_name", "actions", $action, $connection);
            $message = get_value_for_name_from("default_message_no_doctor", "actions", $action, $connection);
            if ($needs_name == 0) {
                print "<p>$message</p>";
            } else {
                $char_num = count($characters);
                $dice = rand(0, $char_num - 1);
                $char_name = get_value_for_char_id("name", $characters[$dice], $connection);
                print "<p>$char_name $message</p>";
            }
        }
    }

    ?>
