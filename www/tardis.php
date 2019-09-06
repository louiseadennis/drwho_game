<?php
    function check_charge($recharge_start, $connection) {
        $unixOriginalDate = strtotime($recharge_start);
        $unixNowDate = strtotime('now');
        $difference = $unixNowDate - $unixOriginalDate ;
        $days = (int)($difference / 86400);
        $hours = (int)($difference / 3600);
        $minutes = (int)($difference / 60);
        $seconds = $difference;
        if ($days > 0)
            $charges = default_charge();
        else if ($hours > 0) {
            $charges = $hours*2;
            if (($minutes - $hours*60) > 30) {
                $charges = $charges + 1;
            }
         } else if ($minutes > 30) {
            $charges = 1;
        } else {
            $charges = 0;
        }
        return $charges;
    }
    
    function update_log($dial1, $dial2, $dial3, $dial4, $connection) {
        $log = get_value_from_users("log", $connection);
        $log_entry = "(" . $dial1 . "," . $dial2 . "," . $dial3 . "," . $dial4 . ")";
        $log_array = explode(":", $log);
        if (!in_array($log_entry, $log_array)) {
            if ($log != '') {
                $new_log = $log . ":" . $log_entry;
                update_users("log", $new_log, $connection);
            } else {
                update_users("log", $log_entry, $connection);
            }
        }
    }
    
    function use_tardis($dial1, $dial2, $dial3, $dial4, $connection) {
        $desired_location_id = get_location_from_coords($dial1, $dial2, $dial3, $dial4, $connection);
        $current_location_id = get_location($connection);
        $sql = "select * from locations";
        if (!$result = $connection->query($sql)) {
            showerror($connection);
        }
        $location_num = $result->num_rows;
        $dice = rand(1, $location_num);
        while ($dice == $current_location_id) {
             $dice = rand(1, $location_num);
        }
        $location_id = $dice;
        
        
        
        return $location_id;
    }
?>
