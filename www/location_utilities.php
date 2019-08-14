<?php
    function print_header($connection) {
        print "<div class=header>";
        collect_new_characters($connection);
        print "<a href=profile.php>User Profile</a>";
        print "&nbsp; &nbsp; &nbsp; <a href=logout.php>Log Out</a>";
        print "<hr>";
        print "</div>";
    }
    
    function print_standard_start($mysql) {
        print "<div class=\"dynamic\">";
        print "<div class=\"action\">";
        print_character_joined($mysql);
        print_item_used($mysql);
        critter_attack($mysql);
        print_health($mysql);
        // print_wait($mysql);
        print "</div>";
        print "</div>";
        
    }
    
    function print_character_joined($connection) {
        $last_action = get_value_from_users("last_action", $connection);
        if ($last_action == "travel") {
            $new_character = get_value_from_users("new_character", $connection);
            if ($new_character != '') {
                $char_id_list = get_value_from_users("char_id_list", $connection);
                $char_id = get_value_for_name_from("char_id", "characters", $new_character, $connection);
                if ($char_id_list != 0 ) {
                    $new_char_id_list = $char_id_list . "," . $char_id;
                    update_users("new_character", '', $connection);
                    if (!check_for_character($new_character, $connection)) {
                        update_users("char_id_list", $new_char_id_list, $connection);
                    }
                } else {
                    $char_id_array = explode(",", $char_id_list);
                    if (!in_array($char_id, $char_id_array)) {
                        update_users("new_character", '', $connection);
                        update_users("char_id_list", $char_id, $connection);
                    }
                }
                $ucchar = ucfirst($new_character);
                $sex = get_value_for_name_from("gender", "characters", $new_character, $connection);
                $pronoun = "He";
                if ($sex == 2) {
                    $pronoun = "She";
                }
                if ($sex == 3) {
                    $pronoun = "They";
                }
                
                print "<p>$ucchar has joined you on your travels.  $pronoun is stored in your user profile.</p>";
            }
        }
    }
    
    function print_item_used($connection) {
        $last_action = get_value_from_users("last_action", $connection);
        $item_used = get_value_from_users("item_used", $connection);
        
        if ($item_used != 0 & $last_action == "item") {
            $can_use = item_used($fight, $item_used, $connection);
            if ($can_use) {
                if (!$fight || !is_weapon($item_used, $connection)) {
                    $default_message = get_value_for_equip_id("use_message", $item_used, $connection);
                    print "<p>$default_message</p>";
                }
            } else {
                print "<p>You can no longer use this item</p>";
            }
            update_users("item_used", 0, $connection);
        }
    }
    
    function print_health($mysql) {
        $hp = get_value_from_users("hp", $mysql);
        if ($hp < default_health()) {
            $healing_start = get_value_from_users("healing_start", $mysql);
            if (!is_null($healing_start)) {
                $time_difference = check_healing($healing_start, $mysql);
                if ($time_difference > 0) {
                    if ($time_difference + $hp > default_health()) {
                        $hp = default_health();
                        update_users("hp", $hp, $mysql);
                    } else {
                        $hp = $time_difference + $hp;
                        $now = now();
                        update_users("healing_start", $now, $mysql);
                        update_users("hp", $hp, $mysql);
                    }
                }
            }
        }
        if ($hp == 0) {
            print "<p><b>You are Unconscious!</b>  Check back in 1 hour.</p>";
        } else if ($hp < 4) {
            print "<p><b><font color=red>You are very badly hurt.</font></b></p>";
        } else if ($hp < 8) {
            print "<p><b>You are badly hurt.</b></p>";
        } else if ($hp < 12) {
            print "<p>You are hurt.</p>";
        } else if ($hp < 16) {
            print "<p>You are slightly hurt.</p>";
        } else if ($hp < 20) {
            print "<p>You are OK, but not at full health.</p>";
        }
        
    }

    function check_location($location, $connection) {
        $real_location = get_location($connection);
    
        if ($real_location == $location) {
            return 1;
        } else {
            $location_string = "location" . $real_location;
            header("Location: $location_string.php");
            exit;
        }
    }
    
    function collect_new_characters($connection) {
        $new_character = get_value_from_users("new_character", $connection);
        if ($new_character != "") {
            $char_id_list = get_value_from_users("char_id_list", $connection);
            if ($char_id_list == 0 || $char_id_list == '') {
                update_users("char_id_list", $new_character, $connection);
            } else {
                $new_char_id_list = $char_id_list . "," . $new_character;
            }
        }
    }
    
    function item_used($fight, $equip_id, $connection) {
        $equip_id_list = get_value_from_users("equipment", $connection);
        $equip_id_array = explode(",", $equip_id_list);
        $i = 0;
        foreach ($equip_id_array as $equip) {
            if ($equip == $equip_id) {
                if (is_weapon($equip_id, $connection)) {
                    $weapon_id = get_weapon_id($equip_id, $connection);
                    if ($fight) {
                        player_attack($weapon_id, $connection);
                    }
                } else {
                    $equip_name = get_value_for_equip_id("name", $equip_id, $connection);
                    if ($equip_name == "first aid kit") {
                        $hp = get_value_from_users("hp", $connection);
                        if ($hp < default_health()) {
                            $hp = $hp + 3;
                            update_users("hp", $hp, $connection);
                        }
                    }
                }
                
                $uses_list = get_value_from_users("uses", $connection);
                $uses_array = explode(",", $uses_list);
                $use = $uses_array[$i];
                if ($use > 0 && $use < 500) {
                    $use2 = $use - 1;
                    $uses_array[$i] = $use2;
                    $new_uses_list =  join(",", $uses_array);
                    update_users("uses", $new_uses_list, $connection);
                    return 1;
                } else if ($use > 500) {
                    return 1;
                } else {
                    return 0;
                }
            }
            $i++;
        }
    }


?>
