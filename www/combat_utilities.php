<?php
    function default_health()
    {
        return 30;
    }
    
    function critter_attack($connection) {
        $location_id = get_value_from_users("location_id", $connection);
        $hp = get_value_from_users("hp", $connection);
        $critter_hp=1;
        
        $fight_just_done = 0;
        $fight = fight($connection);

        $unresolved_event = unresolved_event($connection);
        
        if ($unresolved_event) {
            $event_id = get_unresolved_event_id($connection);
            if ($hp <= 0) {
                update_event($event_id, "fight", 0, $connection);
                update_event($event_id, "resolved", 1, $connection);
                $fight_just_done = 1;
                $fight = 0;
            }
            $critter_stunned = get_value_for_event_id("stunned", $event_id, $connection);
        } else {
            $critter_stunned = 0;
        }
        
        
        if (!$unresolved_event && !$leek && !$fight_just_done && $hp > 0) {
            $fight_chance = get_value_for_location_id("fight_chance", $location_id, $connection);
            
            if ($fight_chance > 0) {
                $dice = rand(0,100);
                if ($dice < $fight_chance) {
                    $fight = 1;
                    $critter_id = get_a_critter($location_id, $connection);
                    create_new_fight_event($critter_id, $connection);
                    $event_id = get_unresolved_event_id($connection);
                }
            }
        }
        
        if ($fight) {
            $critter_id = get_value_for_event_id("critter", $event_id, $connection);
            $critter_hp = get_value_for_event_id("critter_hp", $event_id, $connection);
            $critter_name = get_value_for_critter_id("name", $critter_id, $connection);
            
            if ($critter_hp > 0 && $critter_stunned == 0) {
                $critter_icon = get_value_for_critter_id("icon", $critter_id, $connection);
                if (!is_null($critter_icon)) {
                    print "<img src=$critter_icon align=left>";
                }
                print "<p><b>You are being attacked by a $critter_name</b></p>";
                add_critter($critter_id, $connection);
                $hit_percentage = get_value_for_critter_id("hit_percentage", $critter_id, $connection);
                $d10_roll = rand(0, 100);
                if ($d10_roll < $hit_percentage) {
                    $damage = get_value_for_critter_id("damage", $critter_id, $connection);
                    $hp = $hp - $damage;
                    if ($hp < 0) {
                        $hp = 0;
                    }
                    if ($hp == 0) {
                        update_event($event_id, "fight", 0, $connection);
                        update_event($event_id, "resolved", 1, $connection);
                    }
                    update_users("hp", $hp, $connection);
                    $now = now();
                    update_users("healing_start", $now, $connection);
                    print "<p>The $critter_name strikes you.</p>";
                } else {
                    print "<p>But you dodge out of the way!</p>";
                }
            } else {
                update_event($event_id, "fight", 0, $connection);
            }
            
            if ($critter_hp <= 0) {
                update_event($event_id, "resolved", 1, $connection);
            }
        }
        
        if ($critter_stunned > 0 && $critter_hp > 0) {
            $critter_id = get_value_for_event_id("critter", $event_id, $connection);
            $critter_name = get_value_for_critter_id("name", $critter_id, $connection);
            print "<p>There is an unconscious $critter_name here.</p>";
            $new_stunned = $critter_stunned - 1;
            update_event($event_id, "stunned", $new_stunned, $connection);
            if ($critter_stunned == 1) {
                print "<p>But it seems to be waking up.</p>";
                update_event($event_id, "fight", 1, $connection);
            }
        }
    }
    
    function fight($connection) {
        if (unresolved_event($connection)) {
            $event_id = get_unresolved_event_id($connection);
            $stunned = get_value_for_event_id("stunned", $event_id, $connection);
            if (!$stunned) {
                $fight = get_value_for_event_id("fight", $event_id, $connection);
                return fight;
            }
        }
        
        return 0;
    }


?>
