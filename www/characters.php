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
    
    function tardis_crew_member($char_id, $connection) {
        $tardis_crew = get_value_from_users("tardis_team", $connection);
        $crew_array = explode(",", $tardis_crew);
        return (in_array($char_id, $crew_array));
    }
    
    function tardis_crew_size($connection) {
        $tardis_crew = get_value_from_users("tardis_team", $connection);
        $crew_array = explode(",", $tardis_crew);
        return count($crew_array);
    }
    
    function conscious_tardis_crew_size($connection) {
        $location_team = conscious_tardis_crew($connection);
        
        return count($location_team);
    }
    
    function conscious_tardis_crew($connection) {
        $tardis_crew = get_value_from_users("tardis_team", $connection);
        $location_id = get_location($connection);
        $location_team = [];
        $char_id_array = explode(",", $tardis_crew);
        foreach ($char_id_array as $char_id) {
            $char_location = character_location($char_id, $connection);
            if ($char_location == $location_id && is_conscious($char_id, $connection)) {
                array_push($location_team, $char_id);
            }
        }
        return $location_team;
    }

    function unconscious($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "UPDATE characters_in_play SET unconscious = 1 where char_id = '$char_id' and user_id = '$user_id'";
        
        if (!$db->query($sql)) {
             showerror($db);
        }
    }
    
    function conscious($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "UPDATE characters_in_play SET unconscious = 0 where char_id = '$char_id' and user_id = '$user_id'";
        
        if (!$db->query($sql)) {
             showerror($db);
        }
    }
    
    function is_conscious($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "SELECT unconscious from characters_in_play where char_id ='$char_id' and user_id = '$user_id'";
        $unconscious = select_sql_column($sql, "unconscious", $db);
        
        return (!$unconscious);
    }
    
    function  locked_up($char_id, $db) {
        $user_id = get_user_id($db);
         $sql = "UPDATE characters_in_play SET incarcerated = 1 where char_id = '$char_id' and user_id = '$user_id'";
         
         if (!$db->query($sql)) {
              showerror($db);
         }
    }
    
    function freed($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "UPDATE characters_in_play SET incarcerated = 0 where char_id = '$char_id' and user_id = '$user_id'";
        
        if (!$db->query($sql)) {
             showerror($db);
        }
    }

    function is_locked_up($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "SELECT incarcerated from characters_in_play where char_id ='$char_id' and user_id = '$user_id'";
        $locked_up = select_sql_column($sql, "incarcerated", $db);
        
        return ($locked_up);
    }
    
    function  hypnotised($char_id, $db) {
        $user_id = get_user_id($db);
         $sql = "UPDATE characters_in_play SET hypnotised = 1 where char_id = '$char_id' and user_id = '$user_id'";
         
         if (!$db->query($sql)) {
              showerror($db);
         }
    }
    
    function not_hypnotised($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "UPDATE characters_in_play SET hypnotised = 0 where char_id = '$char_id' and user_id = '$user_id'";
        
        if (!$db->query($sql)) {
             showerror($db);
        }
    }

    function is_hypnotised($char_id, $db) {
        $user_id = get_user_id($db);
        $sql = "SELECT hypnotised from characters_in_play where char_id ='$char_id' and user_id = '$user_id'";
        $locked_up = select_sql_column($sql, "hypnotised", $db);
        
        return ($locked_up);
    }


    
    function join_crew($char_id, $connection) {
        $tardis_crew = get_value_from_users("tardis_team", $connection);
        $crew_array = explode(",", $tardis_crew);
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        if (!in_array($char_id, $crew_array)) {
            $is_doctor = get_value_for_char_id("doctor", $char_id, $connection);
            if ($is_doctor) {
                $current = current_doctor($connection);
                if ($char_id != $current) {
                     $new_char_id_list = $tardis_crew . "," . $char_id;
                    update_users("tardis_team", $new_char_id_list, $connection);
                    leave_crew($current, $connection);
                    
                    $sql = "INSERT INTO characters_in_play (char_id, user_id, location_id, prev_location) VALUES ('$char_id', '$user_id', '$location_id', '$location_id')";
                    if (!$connection->query($sql)) {
                        showerror($connection);
                    }
                }
            } else {
                $new_char_id_list = $tardis_crew . "," . $char_id;
                update_users("tardis_team", $new_char_id_list, $connection);
                $sql = "INSERT INTO characters_in_play (char_id, user_id, location_id, prev_location) VALUES ('$char_id', '$user_id', '$location_id', '$location_id')";
                if (!$connection->query($sql)) {
                    showerror($connection);
                }
            }
        }
    }
    
    function leave_crew($char_id, $connection) {
        $tardis_crew = get_value_from_users("tardis_team", $connection);
        $crew_array = explode(",", $tardis_crew);
        if (in_array($char_id, $crew_array)) {
            $new_array = array_diff($crew_array, array($char_id));
            $new_char_id_list = join(",", $new_array);
            update_users("tardis_team", $new_char_id_list, $connection);
            $user_id = get_user_id($connection);
            $sql = "DELETE FROM characters_in_play where char_id = '$char_id' AND user_id = '$user_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
        }
    }

    
    function print_character_image($char_name, $connection) {
        $uchar = ucfirst($char_name);
        $no_space_char_name = str_replace(" ", "_", $char_name);
        $game_url = default_url();
        $char_id = get_value_for_name_from("char_id", "characters", $char_name, $connection);
        if (is_conscious($char_id)) {
            print "<img src=$game_url/assets/$no_space_char_name.png alt=\"$uchar.\">";
        } else {
            print "<img style=\"opacity:0.2\" src=$game_url/assets/$no_space_char_name.png alt=\"$uchar.\">";
        }
    }
    
    function print_character_and_name($connection, $char_id) {
        $char_name = get_value_for_char_id("name", $char_id, $connection);
        $uchar = ucfirst($char_name);
        $no_space_char_name = str_replace(" ", "_", $char_name);
        print_character_image($char_name, $connection);
        print "<p>$uchar";
        
        print_character_modifiers($connection, $char_id);
    }
    
    function print_character_modifiers($connection, $char_id) {
        $modifiers = get_value_for_char_in_play_id("modifiers", $char_id, $connection);
        if ($modifiers != '0' && $modifiers != '') {
            $modifier_array = explode(",", $modifiers);
            // print "hello";
            foreach ($modifier_array as $modifier) {
                // print $modifier;
                $sql = "SELECT text from story_modifiers where modifier_id = '$modifier'";
                // print $sql;
                $text = select_sql_column($sql, "text", $connection);
                print "<p>$text";
            }
        }
    }
    
    function get_doctors($db) {
        $char_id_list = get_value_from_users("char_id_list", $db);
        $char_id_array = explode(",", $char_id_list);
        $doctor_array = [];
        foreach ($char_id_array as $char_id) {
            $is_doctor = get_value_for_char_id("doctor", $char_id, $db);
            if ($is_doctor) {
                array_push($doctor_array, $char_id);
            }
        }
        return $doctor_array;
    }
    
    function current_doctor($db) {
        $char_id_list = get_value_from_users("tardis_team", $db);
        $char_id_array = explode(",", $char_id_list);
        foreach ($char_id_array as $char_id) {
            $is_doctor = get_value_for_char_id("doctor", $char_id, $db);
            if ($is_doctor) {
                return $char_id;
            }
        }

    }
    
    function modify_character($char_id, $modifier, $db) {
        $modification_list = get_value_for_char_in_play_id("modifiers", $char_id, $db);
        $modification_array = explode(",", $modification_list);
        if (! in_array($modifier, $modification_array)) {
        
            $new_modification_list = $modification_list . "," . $modifier;
            $user_id = get_user_id($db);
            $sql = "";
            if ($modification_list == 0 || $char_id_list == '') {
                $sql = "UPDATE characters_in_play SET modifiers = {$modifier} where char_id = '$char_id' and user_id = '$user_id'";
                // print $sql;
            } else {
                $sql = "UPDATE characters_in_play SET modifiers = '$new_modification_list' where char_id = '$char_id' and user_id = '$user_id'";
            }
            if (!$db->query($sql)) {
                 showerror($db);
            }
            
            $sql = "SELECT unconscious from story_modifiers where modifier_id = '$modifier'";
            $unconscious = select_sql_column($sql, "unconscious", $db);
            if ($unconscious) {
                unconscious($char_id, $db);
             }
            
            $sql = "SELECT incarcerated from story_modifiers where modifier_id = '$modifier'";
             $incarcerated = select_sql_column($sql, "incarcerated", $db);
             if ($incarcerated) {
                 locked_up($char_id, $db);
              }
        }

        
    }
    
    function remove_modification_from_character($modifier, $char_id, $db) {
        $modification_list = get_value_for_char_in_play_id("modifiers", $char_id, $db);
        $modification_array = explode(",", $modification_list);
        $new_modification_list = "";
        foreach ($modification_array as $mod) {
            if ($modifier != $mod) {
                if ($new_modification_list == "") {
                    $new_modification_list = "$mod";
                } else {
                    $new_modification_list = $new_modification_list . "," . $mod;
                }
            }
        }
        $user_id = get_user_id($db);
        $sql = "UPDATE characters_in_play SET modifiers = '$new_modification_list' where char_id = '$char_id' and user_id = '$user_id'";
        if (!$db->query($sql)) {
             showerror($db);
        }
        
        $sql = "SELECT unconscious from story_modifiers where modifier_id = '$modifier'";
        $unconscious = select_sql_column($sql, "unconscious", $db);
        if ($unconscious) {
            conscious($char_id, $db);
        }
        
        $sql = "SELECT incarcerated from story_modifiers where modifier_id = '$modifier'";
        $incarcerated = select_sql_column($sql, "incarcerated", $db);
        if ($incarcerated) {
            freed($char_id, $db);
        }

    }
    
    function remove_modification($modifier, $connection) {
        $user_id = get_user_id($connection);
        $sql = "SELECT char_id FROM characters_in_play WHERE user_id = '$user_id'";
        if (!$result = $connection->query($sql))
            showerror($connection);
                
        while ($row=$result->fetch_assoc()) {
            $char_id = $row["char_id"];
            $modification_list = get_value_for_char_in_play_id("modifiers", $char_id, $connection);
            $modification_array = explode(",", $modification_list);
            foreach ($modification_array as $m) {
                if ($modifier == $m) {
                    remove_modification_from_character($modifier, $char_id, $connection);
                }
            }
        }
    }

    
    function character_location($char_id, $db) {
        $location_id = get_value_for_char_in_play_id("location_id", $char_id, $db);
        return $location_id;
        //print $location_id;
        //print "HELLO";
        // return 2;
    }
    
    function lock_everyone_up($location_id, $connection) {
        foreach (characters_at_location($location_id, $connection) as $char_id) {
            locked_up($char_id, $connection);
        }
    }
    
    function free_everyone($location_id, $connection) {
        foreach (characters_at_location($location_id, $connection) as $char_id) {
            freed($char_id, $connection);
        }
    }

    
    function characters_at_location($location_id, $connection) {
        $tardis_team = get_value_from_users("tardis_team", $connection);
        $location_team = [];
        $char_id_array = explode(",", $tardis_team);
        foreach ($char_id_array as $char_id) {
            $char_location = character_location($char_id, $connection);
            if ($char_location == $location_id) {
                array_push($location_team, $char_id);
            }
        }
        return $location_team;
    }
    
    function doctor_here($connection) {
        $location_id = get_location($connection);
        $characters = characters_at_location($location_id, $connection);
        foreach ($characters as $char_id) {
            $is_doctor = get_value_for_char_id("doctor", $char_id, $connection);
            if ($is_doctor)
                return true;
        }
        return false;
    }
    
    function character_locations($connection) {
        $tardis_team = get_value_from_users("tardis_team", $connection);
        $locations = [];
        $char_id_array = explode(",", $tardis_team);
        foreach ($char_id_array as $char_id) {
            $char_location = character_location($char_id, $connection);
            if (!in_array($char_location, $locations)) {
                array_push($locations, $char_location);
            }
        }
        return $locations;

    }
    
    function get_value_for_char_in_play_id($column, $char_id, $connection) {
        $user_id = get_user_id($connection);
        $sql = "SELECT {$column} FROM characters_in_play WHERE char_id = '{$char_id}' AND user_id = $user_id";
        // print $sql;
        
        return select_sql_column($sql, $column, $connection);

    }
    
?>
