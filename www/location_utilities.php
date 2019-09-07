<?php
    function print_header($connection) {
        print "<div class=header>";
        // collect_new_characters($connection);
        print "<a href=../profile.php>User Profile</a>";
        print "&nbsp; &nbsp; &nbsp; <a href=../log.php>Location Log</a>";
        print "&nbsp; &nbsp; &nbsp; <a href=../logout.php>Log Out</a>";
        print "<hr>";
        print "</div>";
    }
    
    function print_standard_start($mysql) {
        print "<div class=\"dynamic\">";
        print "<div class=\"action\">";
        print_character_joined($mysql);
        print_item_used($mysql);
        // critter_attack($mysql);
        print "</div>";
        // print "<div class=tardis>";
        // print_tardis($mysql);
        // print_wait($mysql);
        // print "</div>";
        print "</div>";
        
    }
    
    function print_character_joined($connection) {
        $last_action = get_value_from_users("last_action", $connection);
        if ($last_action == "travel") {
            $new_characters = get_value_from_users("new_character", $connection);
            if ($new_characters != '') {
                $char_id_array = explode(",", $new_characters);
                foreach ($char_id_array as $char_id) {
                    $char_id_list = get_value_from_users("char_id_list", $connection);
                    $char_name = get_value_for_char_id("name", $char_id, $connection);
                    if ($char_id_list != 0 && $char_id_list != '') {
                        $new_char_id_list = $char_id_list . "," . $char_id;
                        update_users("new_character", '', $connection);
                        if (!check_for_character($char_name, $connection)) {
                            update_users("char_id_list", $new_char_id_list, $connection);
                        }
                    } else {
                        $char_id_array = explode(",", $char_id_list);
                        if (!in_array($char_id, $char_id_array)) {
                            update_users("new_character", '', $connection);
                            update_users("char_id_list", $char_id, $connection);
                        }
                    }
                    $ucchar = ucfirst($char_name);
                    $sex = get_value_for_name_from("gender", "characters", $char_name, $connection);
                    $pronoun = "He";
                    if ($sex == 2) {
                        $pronoun = "She";
                    }
                    if ($sex == 3) {
                        $pronoun = "They";
                    }
                
                    print "<p>$ucchar has joined you on your travels.  $pronoun is stored in your user profile where you can add them to the Tardis crew if you wish.</p>";
                }
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
    
    
    function print_tardis($connection) {
        $location_id = get_location($connection);
        $tardis_location = get_tardis_location($connection);
        if ($location_id == $tardis_location) {
            print "<div class=tardis>";
            $charge = get_value_from_users("charge", $connection);
            
            print "<h4>The Tardis</h4>";
            if (is_null($charge)) {
                $default_charge = default_charge();
                update_users("charge", $default_charge, $connection);
                $charge = $default_charge;
            } else {
                $recharge_start = get_value_from_users("recharge_start", $connection);
                if (is_null($recharge_start)) {
                    $recharge_start = now();
                }
                
                $time_difference = check_charge($recharge_start, $connection);
                if ($time_difference > 0) {
                    if ($time_difference + $charge > default_charge()) {
                        $charge = default_charge();
                        update_users("charge", $charge, $connection);
                    } else {
                        $charge = $time_difference + $charge;
                        $now = now();
                        update_users("recharge_start", $now, $connection);
                        update_users("charge", $charge, $connection);
                    }
                }
            }
            print "<p>Tardis Power Bank Level: " . $charge . "</p>";
            print "<p><form method=\"POST\" action=\"../main.php\">";
            print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
            print "<input type=\"hidden\" name=\"travel_type\" value=\"tardis\">";
            print "<center><table>";
            print "<tr>";
             $planet = get_value_for_location_id("planet", $location_id, $connection);
            $century = get_value_for_location_id("century", $location_id, $connection);
            $d1 = get_value_for_location_id("d1", $location_id, $connection);
            $d2 = get_value_for_location_id("d2", $location_id, $connection);
            print_dial(1, $connection, $planet);
            print_dial(2, $connection, $century);
            print_dial(3, $connection, $d1);
            print_dial(4, $connection, $d2);
            update_log($planet, $century, $d1, $d2, $connection);
            print "</tr></table></center>";
            
            if ($charge > 0) {
                //$tardis_team = get_value_from_users("tardis_team", $connection);
                $char_id_array = characters_at_location($location_id, $connection);
                $to_transmat = 1;
                foreach ($char_id_array as $char_id) {
                    $char_name = get_value_for_char_id("name", $char_id, $connection);
                    $uchar = ucfirst($char_name);
                    print "<label><input type=checkbox name=\"person$to_transmat\" value=$char_id><labelspan>$uchar</labelspan></label><br>";
                    $to_transmat++;
                }
                for ($i = $to_transmat; $to_transmat<5; $to_transmat++) {
                    print "<input type=\"hidden\" name=\"person$to_transmat\" value=\"\">";
                }
                
                $text = get_value_for_location_id("text", $location_id, $connection);
                
                print "<input type=\"submit\" value=\"Fly Tardis\">";
            } else {
                print "<p>The Tardis is low on power.  It recharges at 1 unit per 30 minutes.  You will need to wait.</p>";
            }
            print "</form>";
            print "</div>";
        }
    }
    
    function print_dial($dial, $connection, $value) {
        $dial_prev = "c" . $dial . "_prev";
        $c1 = $value;
        print "<td><select name=\"dial$dial\">";
        $select0 = "";
        $select1 = "";
        $select2 = "";
        $select3 = "";
        $select4 = "";
        $select5 = "";
        $select6 = "";
        $select7 = "";
        $select8 = "";
        $select9 = "";
       
        if ($c1 == '1') {
            $select1 = 'selected';
        } else if ($c1 == '2') {
            $select2 = 'selected';
        } else if ($c1 == '3') {
            $select3 = 'selected';
        } else if ($c1 == '4')    {
            $select4 = 'selected';
        } else if ($c1 == '5') {
            $select5 = 'selected';
        } else if ($c1 == '6') {
            $select6 = 'selected';
        } else if ($c1 == '7') {
            $select7 = 'selected';
        } else if ($c1 == '8') {
            $select8 = 'selected';
        } else if ($c1 == '9') {
            $select9 = 'selected';
        } else {
            $select0 = 'selected';
        }
        print "<option $select0 value=\"0\">0</option>";
        print "<option $select1 value=\"1\">1</option>";
        print "<option $select2 value=\"2\">2</option>";
        print "<option $select3 value=\"3\">3</option>";
        print "<option $select4 value=\"4\">4</option>";
        print "<option $select5 value=\"5\">5</option>";
        print "<option $select6 value=\"5\">6</option>";
        print "<option $select7 value=\"5\">7</option>";
        print "<option $select8 value=\"5\">8</option>";
        print "<option $select9 value=\"5\">9</option>";
        print "</select> &nbsp; &nbsp; </td>";
    }
    
    function print_tardis_team($db) {
        $location_id = get_location($db);
        $tardis_team = get_value_from_users("tardis_team", $db);
        if ($tardis_team != '') {
            print "<h2>Tardis Crew</h2>";
            print "<table>";
            $char_id_array = explode(",", $tardis_team);
            print "<tr>";
            foreach ($char_id_array as $char_id) {
                $char_location = character_location($char_id, $db);
                $char_name = get_value_for_char_id("name", $char_id, $db);
                $uchar = ucfirst($char_name);
                if ($char_location == $location_id) {
                    $no_space_char_name = str_replace(" ", "_", $char_name);
                    print "<td align=center><img src=../assets/$no_space_char_name.png alt=\"$uchar.\"><p>$uchar</td>";
                } else {
                    $location_name = get_value_for_location_id("name", $char_location, $db);
                    print "<td align=center><form form method=\"POST\" action=\"../main.php\">";
                    print "<input type=\"hidden\" name=\"location\" value=\"";
                    print $char_location;
                   print "\">";
                    print $uchar;
                    print "<br> is on <br>";
                    print $location_name;
                    print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                    print "<input type=\"hidden\" name=\"travel_type\" value=\"pov_switch\">";
                    print "<br><input type=\"submit\" value=\"Switch to $location_name\"></form></td>";
                }
            }
            print "</tr>";
            print "</table>";
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
    
    function get_location_from_coords($dial1, $dial2, $dial3, $dial4, $connection) {
        $sql = "SELECT location_id FROM locations WHERE planet = '{$dial1}' AND century = '{$dial2}' AND d1 = '{$dial3}' AND d2 = '{$dial4}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["location_id"];
                return $value;
            }
        }
    }
    
    function print_transmat($location_id, $db) {
        $start = get_location($db);
        print "<div class=transmat>";
        print "<h4>Transmat</h4>";
        print "<p><form method=\"POST\" action=\"../main.php\">";
        print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
        print "<input type=\"hidden\" name=\"travel_type\" value=\"transmat\">";
        print "<input type=\"hidden\" name=\"location\" value=\"$location_id\">";
        
        $tardis_team = characters_at_location($start, $db);
        $to_transmat = 1;
        foreach ($tardis_team as $char_id) {
            $char_name = get_value_for_char_id("name", $char_id, $db);
            $uchar = ucfirst($char_name);
            print "<label><input type=checkbox name=\"person$to_transmat\" value=$char_id><labelspan>$uchar</labelspan><br></label>";
            $to_transmat++;
        }
        for ($i = $to_transmat; $to_transmat<5; $to_transmat++) {
            print "<input type=\"hidden\" name=\"person$to_transmat\" value=\"\">";
        }
        
        $text = get_value_for_location_id("text", $location_id, $db);
        print "<br><input type=\"submit\" value=\"Transmat to $text\">";
        print "</form>";
        print "</div>";
    }


?>
