<?php
    
    function start_story($story_id, $db) {
        $story = get_value_from_users("story", $db);
        if ($story == '0') {
            $story_name = get_value_for_story_id("title", $story_id, $db);
            print "<u>$story_name</u>";
            print "<form method=\"POST\" action=\"../main.php\"><input type=hidden name=\"start_story\", value=\"$story_id\">";
            print "<input type=submit value=\"Start Adventure\"></form>";
        }
    }
    
    function get_value_for_story_id($column, $story_id, $connection) {
        $sql = "SELECT {$column} FROM stories WHERE story_id = '{$story_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["$column"];
                return $value;
            }
        }
    }
    
    

    ?>
