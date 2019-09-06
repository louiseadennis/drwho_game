<?php
    
    function start_story($story_id, $db) {
        update_users("story", $story_id, $db);
    }
    ?>
