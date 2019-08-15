<?php
    function now() {
        $datetime = new DateTime();
        $now = $datetime->format('Y\-m\-d\ H:i:s');
        return $now;
    }
?>
