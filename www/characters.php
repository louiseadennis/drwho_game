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
    
    function encountered_new_character($character, $connection) {
        $new_characters = get_value_from_users("new_character", $connection);
        $char_id = get_value_for_name_from("char_id", "characters", $character, $connection);

        if ($new_characters == '') {
             update_users("new_character", $char_id, $connection);
        } else {
            $char_id_array = explode(",", $new_characters);
            if (!in_array($char_id, $char_id_array)) {
                $new_char_id_list = $new_characters . "," . $char_id;
                update_users("new_character", $new_char_id_list, $connection);
             }
        }
    }
    
    function collect_new_characters($connection) {
        $new_characters = get_value_from_users("new_character", $connection);
        if ($new_characters != "") {
            $char_id_array = explode(",", $new_characters);
            foreach ($char_id_array as $char_id) {
                $char_id_list = get_value_from_users("char_id_list", $connection);
                if ($char_id_list == 0 || $char_id_list == '') {
                    update_users("char_id_list", $char_id, $connection);
                } else {
                    $new_char_id_list = $char_id_list . "," . $new_character;
                    update_users("char_id_list", $new_char_id_list, $connection);
                }
            }
        }
    }
    
    function print_character_image($char_name, $connection) {
        $uchar = ucfirst($char_name);
        $no_space_char_name = str_replace(" ", "_", $char_name);
        $game_url = default_url();
        print "<img src=$game_url/assets/$no_space_char_name.png alt=\"$uchar.\">";
    }
    
    function print_character_and_name($connection, $char_id) {
        $char_name = get_value_for_char_id("name", $char_id, $db);
        $uchar = ucfirst($char_name);
        $no_space_char_name = str_replace(" ", "_", $char_name);
        print_character_image($char_name, $connection);
        print "<p>$uchar";
    }

?>
