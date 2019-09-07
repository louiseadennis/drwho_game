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
    
    function get_value_from_users($column, $connection) {
        $uname = $_SESSION["loginUsername"];
        
        $sql = "SELECT {$column}  FROM users WHERE name = '{$uname}'";
        
        if (!$result = $connection->query($sql))
            return 0;
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row=$result->fetch_assoc()) {
                $value = $row["$column"];
                return $value;
            }
        }
    }
    
    function get_value_for_location_id($column, $location_id, $connection) {
        $sql = "SELECT {$column} FROM locations WHERE location_id = '{$location_id}'";
        
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
    
    function get_value_for_char_id($column, $char_id, $connection) {
        $sql = "SELECT {$column} FROM characters WHERE char_id = '{$char_id}'";
        
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
?>
