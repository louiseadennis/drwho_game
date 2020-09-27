<?php
    function update_users($column, $value, $connection) {
        $uname = $_SESSION["loginUsername"];
        $sql = "UPDATE users SET {$column}='{$value}' WHERE name='$uname'";
        if (!$connection->query($sql)) {
            showerror($connection);
        }
        return 1;
    }
    
    function update_character($char_id, $column, $value, $connection) {
        $user_id = get_user_id($connection);
        $sql = "UPDATE characters_in_play SET {$column}='{$value}' WHERE user_id='$user_id' AND char_id='$char_id'";
        if (!$connection->query($sql)) {
            showerror($connection);
        }
        return 1;

    }
    
    function get_value_for_name_from($column, $table, $name, $connection) {
        $sql = "SELECT {$column} FROM {$table} WHERE name = '{$name}'";
        // print($sql);
        
        return select_sql_column($sql, $column, $connection);
    }
    
    function get_value_from_users($column, $connection) {
        $uname = $_SESSION["loginUsername"];
        
        $sql = "SELECT {$column}  FROM users WHERE name = '{$uname}'";
        
        return select_sql_column($sql, $column, $connection);
     }
    
    function get_value_for_location_id($column, $location_id, $connection) {
        $sql = "SELECT {$column} FROM locations WHERE location_id = '{$location_id}'";
        
        return select_sql_column($sql, $column, $connection);
     }
    
    function get_value_for_char_id($column, $char_id, $connection) {
        $sql = "SELECT {$column} FROM characters WHERE char_id = '{$char_id}'";
        
        return select_sql_column($sql, $column, $connection);
    }

    function get_value_for_item_id($column, $item_id, $connection) {
        $sql = "SELECT {$column} FROM items where item_id = '{$item_id}'";
        
        return select_sql_column($sql, $column, $connection);
    }
    
    function get_value_for_event_modifier_id($column, $modifier_id, $connection) {
         $sql = "SELECT {$column} FROM event_modifiers where modifier_id = '{$modifier_id}'";
         
         return select_sql_column($sql, $column, $connection);
     }

    function get_value_for_story_modifier_id($column, $modifier_id, $connection) {
         $sql = "SELECT {$column} FROM story_modifiers where modifier_id = '{$modifier_id}'";
         
         return select_sql_column($sql, $column, $connection);
     }

    function get_user_id($connection)
    {
        return get_value_from_users("user_id", $connection);
    }
    
    function get_location($connection) {
        return get_value_from_users("location_id", $connection);
    }
    
    function get_tardis_location($connection) {
        return get_value_from_users("tardis_location", $connection);
    }

    function unresolved_event($connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $sql = "SELECT event_id FROM events WHERE user_id='$user_id' AND location_id='$location_id' AND resolved=0";
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            return 1;
        }
    }

    function critter_number($connection) {
        $sql = "SELECT * FROM critters";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        return $result->num_rows;
    }
    
    function ally_number($connection) {
        $sql = "SELECT * FROM allies";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        return $result->num_rows;
    }

    function story_number($connection) {
        $sql = "SELECT * FROM stories";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        return $result->num_rows;
    }

    
    function add_critter($critter_id, $connection) {
          $uname = $_SESSION["loginUsername"];

          $critter_id_list = get_value_from_users("critter_id_list", $connection);
          $critter_id_array = explode(",", $critter_id_list);

          if (is_null($critter_id_list)) {
             update_users("critter_id_list", $critter_id, $connection);
          } else {
                if (!in_array($critter_id, $critter_id_array)) {
                     $new_critter_id_list = $critter_id_list . "," . $critter_id;
             update_users("critter_id_list", $new_critter_id_list, $connection);
            }
          }
    }
    
    function add_ally($ally_id, $connection) {
          $uname = $_SESSION["loginUsername"];

          $ally_id_list = get_value_from_users("ally_id_list", $connection);
          $ally_id_array = explode(",", $ally_id_list);

          if (is_null($ally_id_list)) {
             update_users("ally_id_list", $ally_id, $connection);
          } else {
                if (!in_array($ally_id, $ally_id_array)) {
                    $new_ally_id_list = $ally_id_list . "," . $ally_id;
                    update_users("ally_id_list", $new_ally_id_list, $connection);
            }
          }
    }
    
    function met_critter($critter_id, $connection) {
        $uname = $_SESSION["loginUsername"];
        
        $critter_id_list = get_value_from_users("critter_id_list", $connection);
        $critter_id_array = explode(",", $critter_id_list);
        
        if (is_null($critter_id_list)) {
            return 0;
        } else {
            if (!in_array($critter_id, $critter_id_array)) {
                return 0;
            }
            return 1;
        }
    }
    
    function met_ally($ally_id, $connection) {
        $uname = $_SESSION["loginUsername"];
        
        $ally_id_list = get_value_from_users("ally_id_list", $connection);
        $ally_id_array = explode(",", $ally_id_list);
        
        if (is_null($ally_id_list)) {
            return 0;
        } else {
            if (!in_array($ally_id, $ally_id_array)) {
                return 0;
            }
            return 1;
        }
    }


    function get_value_for_critter_id($column, $critter_id, $connection) {
      $sql = "SELECT {$column} FROM critters WHERE critter_id = '{$critter_id}'";

        return select_sql_column($sql, $column, $connection);
    }
    
    function get_value_for_ally_id($column, $ally_id, $connection) {
      $sql = "SELECT {$column} FROM allies WHERE ally_id = '{$ally_id}'";

        return select_sql_column($sql, $column, $connection);
    }


    function get_value_for_action_id($column, $action_id, $connection) {
      $sql = "SELECT {$column} FROM actions WHERE action_id = '{$action_id}'";

        return select_sql_column($sql, $column, $connection);
    }

    function connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database) {
        $db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
        if ($db -> connect_errno > 0) {
            die('Unable to connect to database [' . $db->connect_error . ']');
        }
        return $db;
    }
    
    // Stolen from PHP and MySQL by Hugh E. Williams and David Lane
    function showerror($mysql)
    {
        die("Error " . mysqli_errno($mysql) . " : " . mysqli_error($mysql));
    }
    
    function select_sql_column($sql, $column, $connection) {
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
    
    function sql_return_to_array($sql, $column, $connection) {
        if (!$result = $connection->query($sql))
              showerror($connection);
        
        $rows = array();
        while ($row=$result->fetch_assoc()) {
            $value = $row["$column"];
            if (! in_array($value, $rows )) {
                array_push($rows, $value);
            }
        }
        return $rows;
    }
?>
