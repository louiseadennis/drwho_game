<?php
    
    function check_for_character($character, $connection) {
        $char_id_list = get_value_from_users("char_id_list", $connection);
        
        if ($char_id_list != 0 ) {
            $char_id = get_value_for_name_from("char_id", "characters", $character, $connection);
            $char_id_array = explode(",", $char_id_list);
            if (in_array($char_id, $char_id_array)) {
                return 1;
            } else {
                return 0;
            }
        }
    }
?>
