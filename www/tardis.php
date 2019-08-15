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
?>
