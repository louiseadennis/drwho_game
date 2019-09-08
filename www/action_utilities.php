<?php
    function is_action($action, $db) {
        $sql = "SELECT * from actions WHERE name = '$action'";
        if (!$result = $db->query($sql))
            return false;
        if ($result->num_rows == 0)
            return false;
        
        return true;
    }
    ?>
