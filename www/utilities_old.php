<?php
    date_default_timezone_set('UTC');

    function DEFINE_date_create_from_format()
      {

          function date_create_from_format( $dformat, $dvalue )
          {

                $schedule = $dvalue;
                $schedule_format = str_replace(array('Y','m','d', 'H', 'i','a'),array('%Y','%m','%d', '%H', '%M', '%p' ) ,$dformat);
                // %Y, %m and %d correspond to date()'s Y m and d.
                // %I corresponds to H, %M to i and %p to a
                $ugly = strptime($schedule, $schedule_format);
                $ymd = sprintf(
                    // This is a format string that takes six total decimal
                    // arguments, then left-pads them with zeros to either
                    // 4 or 2 characters, as needed
                    '%04d-%02d-%02d %02d:%02d:%02d',
                    $ugly['tm_year'] + 1900,  // This will be "111", so we need to add 1900.
                    $ugly['tm_mon'] + 1,      // This will be the month minus one, so we add one.
                    $ugly['tm_mday'],
                    $ugly['tm_hour'],
                    $ugly['tm_min'],
                    $ugly['tm_sec']
                );
                $new_schedule = new DateTime($ymd);

               return $new_schedule;
          }
    }

    if( !function_exists("date_create_from_format") )
     DEFINE_date_create_from_format();

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

    function default_charge()
    {
        return 30;
    }

    function default_health()
    {
        return 30;
    }

    function maximum_phase()
    {
        return 5;
    }

    function default_lester_recharges() {
         return 20;
    }

    function default_stunned() {
         return 5;
    }
    
    function default_zero_coordinates() {
        return 20;
    }
    
    function default_random_locations() {
            return 216;
    }

    function critter_number($connection) {
      $sql = "SELECT * FROM critters";

      if (!$result = $connection->query($sql))
          showerror($connection);
      
      return $result->num_rows;
    }
    
    function random_fic($connection) {
        $fanfic_id_list = get_value_from_users("fanfic_id_list", $connection);
        $sql = "SELECT fanfic_id from fanfic";
        if (!$result = $connection->query($sql)) {
            showerror($connection);
        }
        $fanfic_array = array();
        
        if (!is_null($fanfic_id_list)) {
            $fanfic_id_array = explode(",", $fanfic_id_list);
            while ($row = $result->fetch_assoc()) {
                $fanfic_id = $row["fanfic_id"];
                if (!in_array($fanfic_id, $fanfic_id_array)) {
                    array_push($fanfic_array, $fanfic_id);
                }
            }
        } else {
            while ($row = $result->fetch_assoc()) {
                $fanfic_id = $row["fanfic_id"];
                array_push($fanfic_array, $fanfic_id);
            }
        }
        
        $size = count($fanfic_array);
        if ($size == 0 ) {
            return 0;
        } else {
            $dice = rand(0, $size - 1);
            return $fanfic_array[$dice];
        }
    }
    
    function print_equipment_purchase ($connection) {
        $budget = check_for_equipment("budget", $connection);
        if ($budget) {
            $sql = "SELECT * from equipment";
            if (!$result = $connection->query($sql)) {
                showerror($connection);
            }
            print "<p>You may purchase:";
            print "<ul>";
            while ($row=$result->fetch_assoc()) {
                if ($row["purchasable"] == 1) {
                    $name = $row["name"];
                    $cost = $row["cost"];
                    $equip = $row["equip_id"];
                    $printing = true;
                    if (! check_for_equipment($name, $connection)) {
                        print "<li><form method=\"POST\" action=\"main.php\">$name for $cost. ";
                    } else if ($row["default_uses"] < 500){
                        print "<li><form method=\"POST\" action=\"main.php\">Refills for $name for $cost. ";
                    } else {
                        $printing = false;
                    }
                    
                    if ($printing) {
                        $location_id=get_location($connection);
                        print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
                        print "<input type=\"hidden\" name=\"travel_type\" value=\"none\">";
                        print "<input type=\"hidden\" name=\"item_purchased\" value=$equip>";
                        print "<input type=\"hidden\" name=\"last_action\" value=\"purchase\">";
                        print "<input type=\"submit\" value=\"Buy\">";
                        print "</form></li>";
                    }
                } else if ($row["default_uses"] < 500 && $row["name"] != "budget" && check_for_equipment($row["name"], $connection)){
                    $name = $row["name"];
                    $cost = $row["cost"];
                    $equip = $row["equip_id"];
                    print "<li><form method=\"POST\" action=\"main.php\">Refills for $name for $cost. ";
                    
            
                    $location_id=get_location($connection);
                    print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
                    print "<input type=\"hidden\" name=\"travel_type\" value=\"none\">";
                    print "<input type=\"hidden\" name=\"item_purchased\" value=$equip>";
                    print "<input type=\"hidden\" name=\"last_action\" value=\"purchase\">";
                    print "<input type=\"submit\" value=\"Buy\">";
                    print "</form></li>";
                    
                }
            }
            print "</ul>";
        }
    }
    
    
    function update_lof_choice($choice, $value, $location_id, $connection) {
        if ($choice == 1) {
            $choice_text = get_value_for_lof_choice_id($value, $choice, $connection);
            $choice_img = get_value_for_name_from("picture", "lof_paired_choices", $choice_text,  $connection);
            $sql = "UPDATE landoffiction SET picture='{$choice_img}' WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            $sql = "UPDATE lof_choices SET {$value}='T' WHERE choice_id=101";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            $sql = "UPDATE landoffiction SET next_choice=2 WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            return 1;
        } else if ($choice == 2) {
            $choice_text = get_value_for_lof_choice_id($value, $choice, $connection);
            $sql = "UPDATE landoffiction SET racoon='{$choice_text}' WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            $sql = "UPDATE lof_choices SET {$value}='T' WHERE choice_id=102";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            $sql = "UPDATE landoffiction SET next_choice=3 WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            return 1;

        } else if ($choice == 3) {
            $sql = "UPDATE locations SET name='{$value}' WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            $sql = "UPDATE landoffiction SET next_choice=4 WHERE location_id='$location_id'";
            if (!$connection->query($sql)) {
                showerror($connection);
            }
            return 1;
        } else if ($choice == 4) {
            $sql = "SELECT * FROM landoffiction where location_id={$location_id}";
            if (!$result = $connection->query($sql))
                showerror($connection);
            
            while ($row=$result->fetch_assoc()) {
                $story = $row["story"];

                $new_story = $story . "<br>" . $value;
                
                $sql = "UPDATE landoffiction SET story='{$new_story}' WHERE location_id='$location_id'";
                if (!$connection->query($sql)) {
                    showerror($connection);
                }
                return 1;
            }
            return 1;
        }
    }
    
    function print_footer($location_id, $connection) {
        $master = get_value_from_users("master", $connection);
        if ($master == 1) {
            $sql = "SELECT * FROM locations where location_id={$location_id}";
            if (!$result = $connection->query($sql))
                showerror($connection);
            
            while ($row=$result->fetch_assoc()) {
                $story = $row["story"];
                if ($story == '') {
                    print "<p>You can write what has happened here.</p>";
                } else {
                    print "<p>";
                    print $story;
                    print "<p>What happens next?</p>";
                }
                
                print "<form method=\"POST\" action=\"main.php\">";
                print "<input type=\"hidden\" name=\"last_action\" value=\"write\">";
                print "<input type=\"hidden\" name=\"location\" value=\"$location_id\">";
                print "<input type=\"text\" size=\"100\" name=\"story\"></a>";
                print "<p><input type=\"submit\" value=\"Is what happens next!!\"></form></p>";
                
            }
            
        }
    }
    
    function print_land_of_fiction($location_id, $connection) {
        $choice_point = get_value_for_lof_id("next_choice", $location_id, $connection);
        if ($choice_point == 1) {
            print "<img src=assets/location217.png>";
            print "<h2>A White Void</h2>";
            print "<p>An image of a place begins to form out of the whiteness.  You get the impression you can choose the image...</p>";
            print "<form method=\"POST\" action=\"main.php\">";
            print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
            print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
            print "<input type=\"hidden\" name=\"choice_id\" value=\"choice1\">";
           print "<select name=\"picture\">";
            $sql = "SELECT * FROM lof_choices where choice_id=1";
            
            if (!$result = $connection->query($sql))
                showerror($connection);
            
            $choice1 = 'X';
            $choice2 = 'X';
            $choice3 = 'X';
            $choice4 = 'X';
            $choice5 = 'X';
            $choice6 = 'X';
           while ($row=$result->fetch_assoc()) {
                $choice1 = $row["choice1"];
                $choice2 = $row["choice2"];
                $choice3 = $row["choice3"];
                $choice4 = $row["choice4"];
                $choice5 = $row["choice5"];
                $choice6 = $row["choice6"];
             }
            
            $sql = "SELECT * FROM lof_choices where choice_id=101";
            if (!$result = $connection->query($sql))
                showerror($connection);
            
            while ($row=$result->fetch_assoc()) {
                if ($row["choice1"] != 'T') {
                    print "<option value=\"choice1\">$choice1</option>";
                }
                if ($row["choice2"] != 'T') {
                    print "<option value=\"choice2\">$choice2</option>";
                }
                if ($row["choice3"] != 'T') {
                    print "<option value=\"choice3\">$choice3</option>";
                }
                if ($row["choice4"] != 'T') {
                    print "<option value=\"choice4\">$choice4</option>";
                }
                if ($row["choice5"] != 'T') {
                    print "<option value=\"choice5\">$choice5</option>";
                }
                if ($row["choice6"] != 'T') {
                    print "<option value=\"choice6\">$choice6</option>";
                }
            }

            print "</select>";
     
            print "<p><input type=\"submit\" value=\"Choose image\"></p></form>";
            print "<form method=\"POST\" action=\"main.php\">";
            print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
            print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
            print "<input type=\"hidden\" name=\"picture\" value=\"none\">";
            print "<input type=\"hidden\" name=\"choice_id\" value=\"choice1\">";
            print "<input type=\"submit\" value=\"No I don't want to choose!\"></p></form>";

        } else {
            $sql = "SELECT * FROM landoffiction where location_id={$location_id}";
            if (!$result = $connection->query($sql))
                showerror($connection);
            
            $choice_array = array();
            while ($row=$result->fetch_assoc()) {
                $picture_text = $row["picture"];
                print "<img src=assets/$picture_text>";
            }
            
            if ($choice_point > 3) {
                $placename = get_value_for_location_id("name", $location_id, $connection);
                print "<h2>$placename</h2>";
            }
            
            if ($choice_point == 2) {
                print "<p>You can see a figure in the distance walking towards you.  You get the impression you can choose who it is...</p>";
                print "<form method=\"POST\" action=\"main.php\">";
                print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                print "<input type=\"hidden\" name=\"choice_id\" value=\"choice2\">";
                print "<select name=\"racoon\">";
                $sql = "SELECT * FROM lof_choices where choice_id=2";
                print $sql;
                
                if (!$result = $connection->query($sql))
                    showerror($connection);
                
                $choice1 = 'X';
                $choice2 = 'X';
                $choice3 = 'X';
                $choice4 = 'X';
                $choice5 = 'X';
                $choice6 = 'X';
                while ($row=$result->fetch_assoc()) {
                    $choice1 = $row["choice1"];
                    $choice2 = $row["choice2"];
                    $choice3 = $row["choice3"];
                    $choice4 = $row["choice4"];
                    $choice5 = $row["choice5"];
                    $choice6 = $row["choice6"];
                }
                
                $sql = "SELECT * FROM lof_choices where choice_id=102";
                if (!$result = $connection->query($sql))
                    showerror($connection);
                
                while ($row=$result->fetch_assoc()) {
                    if ($row["choice1"] != 'T') {
                        print "<option value=\"choice1\">$choice1</option>";
                    }
                    if ($row["choice2"] != 'T') {
                        print "<option value=\"choice2\">$choice2</option>";
                    }
                    if ($row["choice3"] != 'T') {
                        print "<option value=\"choice3\">$choice3</option>";
                    }
                    if ($row["choice4"] != 'T') {
                        print "<option value=\"choice4\">$choice4</option>";
                    }
                    if ($row["choice5"] != 'T') {
                        print "<option value=\"choice5\">$choice5</option>";
                    }
                    if ($row["choice6"] != 'T') {
                        print "<option value=\"choice6\">$choice6</option>";
                    }
                }
                
                print "</select>";
                
                print "<p><input type=\"submit\" value=\"Choose Who it Is\"></p></form>";
                print "<form method=\"POST\" action=\"main.php\">";
                print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                print "<input type=\"hidden\" name=\"racoon\" value=\"none\">";
                print "<input type=\"hidden\" name=\"choice_id\" value=\"choice2\">";
                print "<input type=\"submit\" value=\"No I don't want to choose!\"></p></form>";
                
            } else {
                $sql = "SELECT * FROM landoffiction where location_id={$location_id}";
                if (!$result = $connection->query($sql))
                    showerror($connection);
                
                while ($row=$result->fetch_assoc()) {
                    $racoon_text = $row["racoon"];
                    $uchar = ucfirst($racoon_text);
                    
                    $char_collected = check_for_character($racoon_text, $connection);
                    if (!$char_collected) {
                    
                        
                        print "<p><img src=assets/$racoon_text.png align=left>";
                        print "<p>$uchar is here.</p>";
                        update_users("new_character", $racoon_text, $connection);
                        if ($racoon_text == "torrence") {
                            $visited_already = get_value_from_users("new_character", $connection);
                            if ($visited_already != "torrence'") {
                                add_equipment("torrence", $connection);
                            }
                        }
                    } else {
                        print "<p>$uchar was here.</p>";
                    }
                }
                
                if ($choice_point == 3) {
                    print "<p>You should name this place.</p>";
                    print "<form method=\"POST\" action=\"main.php\">";
                    print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                    print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                    print "<input type=\"hidden\" name=\"choice_id\" value=\"choice3\">";
                    print "<input type=\"text\" size=\"50\" name=\"placename\">";
                    print "<p><input type=\"submit\" value=\"Name the Place!\"></form></p>";
                    print "<form method=\"POST\" action=\"main.php\">";
                    print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                    print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                    print "<input type=\"hidden\" name=\"placename\" value=\"x1x1x1\">";
                    print "<input type=\"hidden\" name=\"choice_id\" value=\"choice3\">";
                    print "<input type=\"submit\" value=\"No I don't want to choose!\"></p></form>";
                } else {
                    $sql = "SELECT * FROM landoffiction where location_id={$location_id}";
                    if (!$result = $connection->query($sql))
                        showerror($connection);
                    
                    while ($row=$result->fetch_assoc()) {
                        $story = $row["story"];
                        if ($story == '') {
                            print "<p>You should start writing what has happened here.</p>";
                        } else {
                            print $story;
                            print "<p>What happens next?</p>";
                        }
                        
                        print "<form method=\"POST\" action=\"main.php\">";
                        print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                        print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                        print "<input type=\"hidden\" name=\"choice_id\" value=\"choice4\">";
                        print "<input type=\"text\" size=\"100\" name=\"story\"></a>";
                        print "<p><input type=\"submit\" value=\"Is what happens next!!\"></form></p>";
                        print "<form method=\"POST\" action=\"main.php\">";
                        print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                        print "<input type=\"hidden\" name=\"travel_type\" value=\"lof\">";
                        print "<input type=\"hidden\" name=\"story\" value=\"x1x1x1\">";
                        print "<input type=\"hidden\" name=\"choice_id\" value=\"choice4\">";
                        print "<input type=\"submit\" value=\"No I don't want to choose!\"></p></form>";

                    }

                }
            }
            
        }
    }

    function print_junction_anomaly($anomaly, $connection) {
        $destination = get_anomaly_destination($anomaly, $connection);
        $water = get_value_for_location_id("water", $destination, $connection);
        print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $destination . "\">";
        print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
        print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
        if (fight($connection)) {
               print "<p><input type=\"submit\" value=\"Run through anomaly\"></p></form>";
        } else {
               print "<p><input type=\"submit\" value=\"Go through anomaly\"></p></form>";
        }

        if ($water) {
               print "<p>There is water pouring out of the anomaly.</p>";
        }
    }

    function add_location_clue($location_id, $connection) {
         $clues = get_value_from_users("locationclues", $connection);
         if (is_null($clues)) {
            update_users("locationclues", $location_id, $connection);
         } else {
           $clue_array = explode(",", $clues);
           if (!in_array($location_id, $clue_array)) {
              $new_clues = $clues . "," . $location_id;
              update_users("locationclues", $new_clues, $connection);
              }
         }
    }
    
    function visited_sanctuary($connection) {
        $sanctuary_visits = get_value_from_users("sanctuary", $connection);
        $sanctuary_seen = get_value_from_users("sanctuary_seen", $connection);
        if ($sanctuary_visits < $sanctuary_seen) {
            $sanctuary_visits++;
            update_users("sanctuary", $sanctuary_visits, $connection);
        }
    }
    
    function sanctuary_seen($connection, $number) {
        $sanctuary_seen = get_value_from_users("sanctuary_seen", $connection);
        if ($sanctuary_visits < $number) {
            update_users("sanctuary_seen", $number, $connection);
        }
    }

    function add_fanfic($fanfic_id, $connection) {
        $uname = $_SESSION["loginUsername"];
        
        $fanfic_id_list = get_value_from_users("fanfic_id_list", $connection);
        $fanfic_id_array = explode(",", $fanfic_id_list);
        
        if (is_null($fanfic_id_list)) {
            update_users("fanfic_id_list", $fanfic_id, $connection);
        } else {
            if (!in_array($fanfic_id, $fanfic_id_array)) {
                $new_fanfic_id_list = $fanfic_id_list . "," . $fanfic_id;
                update_users("fanfic_id_list", $new_fanfic_id_list, $connection);
            }
        }
    }
    
    function have_fanfic($fanfic_id,$connection) {
        $uname = $_SESSION["loginUsername"];
        
        $fanfic_id_list = get_value_from_users("fanfic_id_list", $connection);
        $fanfic_id_array = explode(",", $fanfic_id_list);
        
        if (is_null($fanfic_id_list)) {
            return 0;
        } else {
            if (!in_array($fanfic_id, $fanfic_id_array)) {
                return 0;
            } else {
                return 1;
            }
        }
    }
    
    function print_fanfic_with_cover($fanfic_id, $connection) {
        $cover = get_value_for_fanfic_id("cover", $fanfic_id, $connection);
        if (! is_null($cover)) {
            print "<p><center><img src=$cover></center></p>";
        }
        print_fanfic($fanfic_id, $connection);
        $summary = get_value_for_fanfic_id("description",$fanfic_id,$connection);
        print "<p>$summary</p>";
    }
    
    function print_fanfic($fanfic_id, $connection) {
        $title = get_value_for_fanfic_id("title", $fanfic_id, $connection);
        $author = get_value_for_fanfic_id("author", $fanfic_id, $connection);
        $url = get_value_for_fanfic_id("url", $fanfic_id, $connection);
        $rating = get_value_for_fanfic_id("rating", $fanfic_id, $connection);
        
        print("<a href=$url>$title</a> by $author (Rating: $rating)");
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

    function get_present_day_locations($connection) {
        $sql = "SELECT * FROM locations WHERE present_day=1";
        
        if (!$result = $connection->query($sql))
          showerror($connection);
      
        $location_array = array();
        while ($row=$result->fetch_assoc()) {
              $location_id = $row["location_id"];
          $phase = get_value_from_users("phase", $connection);
          if ($phase != 1 || $location_id != 19) {
                 array_push($location_array, $row["location_id"]);
          }
        }
        return $location_array;
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

    function update_users($column, $value, $connection) {
        $uname = $_SESSION["loginUsername"];
        $sql = "UPDATE users SET {$column}='{$value}' WHERE name='$uname'";
        if (!$connection->query($sql)) {
           showerror($connection);
        }
        return 1;
    }

    function junction_here($connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $sql = "SELECT a1 FROM junctions WHERE user_id='$user_id' AND location_id='$location_id'";
        if (!$result = $connection->query($sql))
          showerror($connection);
      
      if ($result->num_rows != 1)
          return 0;
      else {
         return 1;
      }
    }
    
    function update_location_count($db) {
        $count = get_value_from_users("location_count", $db);
        $new_count = $count+1;
        update_users("location_count", $new_count, $db);
    }

    function change_anomaly($anomaly,  $prev_location, $connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $anomaly_string = "a" . $anomaly;
        $sql = "SELECT $anomaly_string FROM junctions WHERE user_id='$user_id' AND location_id='$location_id'";

        if (!$result = $connection->query($sql))
          showerror($connection);

        while ($row=$result->fetch_assoc()) {
              $value = $row["$anomaly_string"];
          if ($value == 0) {
            $d60 = rand(1, default_random_locations());
                $sql = "UPDATE junctions SET {$anomaly_string}=$d60 WHERE location_id='$location_id' AND user_id='$user_id'";
            if (!$connection->query($sql)) {
                   showerror($connection);
                }
          } else if ($value != $prev_location) {
                $sql = "UPDATE junctions SET {$anomaly_string}=0 WHERE location_id='$location_id' AND user_id='$user_id'";
            if (!$connection->query($sql)) {
                   showerror($connection);
                }
          }
        }

      
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

    function get_unresolved_event_id($connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $sql = "SELECT event_id FROM events WHERE user_id='$user_id' AND location_id='$location_id' AND resolved=0";

        if (!$result = $connection->query($sql))
          showerror($connection);
      
        if ($result->num_rows != 1)
          return 0;
        else {
         while ($row=$result->fetch_assoc()) {
          $value = $row["event_id"];
          return $value;
          }
        }
    }

    function get_anomaly_destination($anomaly, $connection) {
        $user_id = get_user_id($connection);
        $location_id = get_location($connection);
        $sql = "SELECT {$anomaly} FROM junctions WHERE user_id='$user_id' AND location_id='$location_id'";

        if (!$result = $connection->query($sql))
          showerror($connection);
      
        if ($result->num_rows != 1)
          return 0;
        else {
         while ($row=$result->fetch_assoc()) {
          $value = $row["$anomaly"];
          return $value;
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

    function update_location($location_id, $column, $value, $connection) {
        $sql = "UPDATE locations SET {$column}='{$value}' WHERE location_id='$location_id'";
        if (!$connection->query($sql)) {
           showerror($connection);
        }
        return 1;
    }

    function update_event($event_id, $column, $value, $connection) {
        $sql = "UPDATE events SET {$column}='{$value}' WHERE event_id='$event_id'";
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

    function get_value_for_equip_id($column, $equip_id, $connection) {
      $sql = "SELECT {$column} FROM equipment WHERE equip_id = '{$equip_id}'";

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

    function get_value_for_critter_id($column, $critter_id, $connection) {
      $sql = "SELECT {$column} FROM critters WHERE critter_id = '{$critter_id}'";

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

    function get_value_for_weapon_id($column, $weapon_id, $connection) {
      $sql = "SELECT {$column} FROM weapons WHERE weapon_id = '{$weapon_id}'";

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

    function get_weapon_id($equip_id, $connection) {
      $sql = "SELECT weapon_id FROM weapons WHERE equip_id = '{$equip_id}'";

      if (!$result = $connection->query($sql))
          showerror($connection);
      
      if ($result->num_rows != 1)
          return 0;
      else {
         while ($row=$result->fetch_assoc()) {
               $value = $row["weapon_id"];
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
    
    function get_value_for_lof_id($column, $lof_id, $connection) {
            $sql = "SELECT {$column} FROM landoffiction WHERE location_id = '{$lof_id}'";
        
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
    
        function get_value_for_lof_choice_id($column, $lof_id, $connection) {
            $sql = "SELECT {$column} FROM lof_choices WHERE choice_id = '{$lof_id}'";
            
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

    function get_value_for_event_id($column, $event_id, $connection) {
      $sql = "SELECT {$column} FROM events WHERE event_id = '{$event_id}'";

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
    
    function get_value_for_fanfic_id($column, $fanfic_id, $connection) {
        $sql = "SELECT {$column} from fanfic WHERE fanfic_id = '{$fanfic_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row = $result->fetch_assoc()) {
                $value = $row["$column"];
                return $value;
            }
        }
    }
    
    function get_value_for_sanctuary_id($column, $sanctuary_id, $connection) {
        $sql = "SELECT {$column} from sanctuary WHERE sanctuary_id = '{$sanctuary_id}'";
        
        if (!$result = $connection->query($sql))
            showerror($connection);
        
        if ($result->num_rows != 1)
            return 0;
        else {
            while ($row = $result->fetch_assoc()) {
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

    function check_for_character($character, $connection) {
       $char_id_list = get_value_from_users("char_id_list", $connection);

       if ($char_id_list != 0 ) {
          $char_id = get_value_for_name_from("char_id", "characters", $character, $connection);
          $char_id_array = explode(",", $char_id_list);
          if (in_array($char_id, $char_id_array)) {
             return 1;
          } else {
             return 0;
          }
       }
    }

    function collect_new_characters($connection) {
         $new_character = get_value_from_users("new_character", $connection);
         if ($new_character != "") {
            $char_id_list = get_value_from_users("char_id_list", $connection);
            if ($char_id_list == 0 || $char_id_list == '') {
               update_users("char_id_list", $new_character, $connection);
            } else {
               $new_char_id_list = $char_id_list . "," . $new_character;
               }
         }
    }

    function check_for_equipment($equip, $connection) {
       $equip_id_list = get_value_from_users("equipment", $connection);

       if ($equip_id_list != 0 ) {
          $equip_id = get_value_for_name_from("equip_id", "equipment", $equip, $connection);
          $equip_id_array = explode(",", $equip_id_list);
          if (in_array($equip_id, $equip_id_array)) {
             return 1;
          } else {
             return 0;
          }
       }
    }

    function anomaly($location_id) {
         print "<p>Before you is an anomaly!</p>";

         print "<p><form method=\"POST\" action=\"main.php\">";
         print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
         print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
         print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
         print "<input type=\"submit\" value=\"Go through the anomaly\">";
         print "</form>";
         print "</p>";
    }

    function print_accessible_location($old_location, $location_id, $mysql) {
         print "<p><form method=\"POST\" action=\"main.php\">";
         print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
         print "<input type=\"hidden\" name=\"travel_type\" value=\"vehicle\">";
         print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
         $name = get_value_for_location_id("name", $location_id, $mysql);
         $cost = travel_cost($old_location, $location_id, $mysql);
          print "<input type=\"submit\" value=\"$name\">  This will cost $cost.";
        
         print "</form>";
         print "</p>";
    }
    
    function travel_cost($old_location, $location_id, $mysql) {
        $old_area = get_value_for_location_id("rough_area", $old_location, $mysql);
        $this_area = get_value_for_location_id("rough_area", $location_id, $mysql);
        $cost = 0;
        if ($old_area == $this_area) {
            $cost = 0;
        } else if (abs($old_area - $this_area) < 50) {
            $cost = 1;
        } else {
            $cost = 10;
        }
        return $cost;
    }
    
    function print_accessible_location_foot($location_id, $mysql) {
            print "<p><form method=\"POST\" action=\"main.php\">";
            print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
            print "<input type=\"hidden\" name=\"travel_type\" value=\"foot\">";
            print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
            $name = get_value_for_location_id("name", $location_id, $mysql);
            print "<input type=\"submit\" value=\"$name\">";
            print "</form>";
            print "</p>";
    }


    function get_location($connection) {
        return get_value_from_users("location_id", $connection);
    }

    function get_user_phase($connection)
    {
        return get_value_from_users("phase", $connection);
    }

    function print_header($connection) {
        print "<div class=header>";
        collect_new_characters($connection);
        print "<a href=profile.php>User Profile</a>";
        print "&nbsp; &nbsp; &nbsp;       <a href=log.php>Log Book</a>";
        print "&nbsp; &nbsp; &nbsp;       <a href=logout.php>Log Out</a>";
        print "<hr>";
        print "</div>";
    }

    function print_anomaly($connection) {
       $travel_type = get_value_from_users("travel_type", $connection);
       $random_anomalies = get_value_from_users("random_anomalies", $connection);
       $hp = get_value_from_users("hp", $connection);
       $second_anomaly = 0;
       if ($random_anomalies && $hp > 0) {
          $location_id = get_location($connection);
          $anomaly_chance = get_value_for_location_id("anomaly_chance", $location_id, $connection);
          $has_device = get_value_from_users("has_device", $connection);

          $d100_roll = rand(1, 100);
          if ($d100_roll <= $anomaly_chance) {
            $second_anomaly = 1;
            $d60_roll = rand(1, default_random_locations());
          }
       }

       if ($travel_type == "anomaly" || $travel_type == "device") {
          $prev = get_value_from_users("prev_location", $connection);
          $water = get_value_for_location_id("water", $prev, $connection);
          if (!is_null($prev)) {
             print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $prev . "\">";
         print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
             print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
          

        $last_action = get_value_from_users("last_action", $connection);
        if ($last_action == "travel") {
               if ($hp > 0) {
                if ($travel_type == "anomaly") {
              $needed_boat = get_value_from_users("needed_boat", $connection);
                  if (fight($connection)) {
                 if ($needed_boat) {
                  print "<p><b>You are swept back out of the anomaly and are attacked.</b>  <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
                  update_users("needed_boat", 0, $connection);
             } else {
                   print "<p>You walk through the anomaly and are attacked.  <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
             }
              } else {
                if ($needed_boat) {
                    print "<p><b>You are swept back out of the anomaly</b>. <input type=\"submit\" value=\"Go back through\"></p></form>";
                  update_users("needed_boat", 0, $connection);
            } else {
                    print "<p>You have come through an anomaly. <input type=\"submit\" value=\"Go back through\"></p></form>";
            }
                  }

                } else {
                  if (fight($connection)) {
                 print "<p>The Device opens an anomaly.  You walk through the anomaly and are attacked.  <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
                  } else {
                    print "<p>The Device opens an anomaly and you step through. <input type=\"submit\" value=\"Go back through Anomaly\"></p></form>";
                  }
                }
         }
            } else {
              $hp = get_value_from_users("hp", $connection);
                  if ($hp > 0) {
                 if (fight($connection)) {
                    print "<p>The anomaly you came through is here.   <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
             } else {
                   print "<p>The anomaly you came through is here.   <input type=\"submit\" value=\"Go back through\"></p></form>";
            }
           }
        }

            if ($water) {
               print "<p>There is water pouring out of the anomaly.</p>";
            }
          }

          if ($second_anomaly) {
                 $water = get_value_for_location_id("water", $d60_roll, $connection);
             print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $d60_roll . "\">";
             print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                 print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
             if (fight($connection)) {
               print "<p>There is a second anomaly here. <input type=\"submit\" value=\"Run through anomaly\"></p></form>";
             } else {
               print "<p>There is a second anomaly here. <input type=\"submit\" value=\"Go through anomaly\"></p></form>";
            }

            if ($water) {
                   print "<p>There is water pouring out of the anomaly.</p>";
                }
            critter_from_anomaly($d60_roll, $connection);
          }
      } else if ($second_anomaly) {
                 $water = get_value_for_location_id("water", $d60_roll, $connection);
             print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $d60_roll . "\">";
             print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                 print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
             if (fight($connection)) {
               print "<p>There is an anomaly here. <input type=\"submit\" value=\"Run through anomaly\"></p></form>";
             } else {
               print "<p>There is an anomaly here. <input type=\"submit\" value=\"Go through anomaly\"></p></form>";
            }

            if ($water) {
                   print "<p>There is water pouring out of the anomaly.</p>";
                }
            critter_from_anomaly($d60_roll, $connection);
      }
    }

    function print_wait($mysql) {
         $location_id=get_location($mysql);
         print "<form method=\"POST\" action=\"main.php\">";
         print "<input type=\"hidden\" name=\"travel_type\" value=\"none\">";
         print "<input type=\"hidden\" name=\"last_action\" value=\"wait\">";
             print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
         print "<input type=\"submit\" value=\"Wait here a bit\"></p></form>";
    }

    function critter_from_anomaly($anomaly_location, $mysql) {
         $fight = unresolved_event($mysql);
         if (!$fight) {
            $coin_toss = rand(0, 1);
            if ($coin_toss) {
               $critters = get_value_for_location_id("critter", $anomaly_location, $mysql);
               if (!is_null($critters)) {
                  $critter_id = get_a_critter($anomaly_location, $mysql);
                      add_critter($critter_id, $mysql);
              create_new_fight_event($critter_id, $mysql);
              $critter_name = get_value_for_critter_id("name", $critter_id, $mysql);
              print "<p><b>A $critter_name has come through the anomaly!</b></p>";
              $location_id = get_location($mysql);
              $water_here = get_value_for_location_id("water", $location_id, $mysql);
              $aquatic = get_value_for_critter_id("aquatic", $critter_id, $mysql);
              if ($water_here & !$acquatic) {
                 print "<p>It drowns!</p>";
              } else if ($acquatic & !$water_here) {
                print "<p>It flops about helplessly on the ground.</p>";
              } else {
                critter_attack(0, $mysql);
              }
               }
            }
         }
    }

    function print_anomaly_no_random($connection) {
       $travel_type = get_value_from_users("travel_type", $connection);

      if ($travel_type == "anomaly" || $travel_type == "device") {
          $prev = get_value_from_users("prev_location", $connection);
          $water = get_value_for_location_id("water", $prev, $connection);
          if (!is_null($prev)) {
             print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $prev . "\">";
         print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
             print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
          

        $last_action = get_value_from_users("last_action", $connection);
        if ($last_action == "travel") {
               $hp = get_value_from_users("hp", $connection);
               if ($hp > 0) {
                if ($travel_type == "anomaly") {
                  if (fight($connection)) {
                 print "<p>You walk through the anomaly and are attacked.  <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
              } else {
                    print "<p>You have come through an anomaly. <input type=\"submit\" value=\"Go back through\"></p></form>";
                  }

                } else {
                  if (fight($connection)) {
                 print "<p>The Device opens an anomaly.  You walk through the anomaly and are attacked.  <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
                  } else {
                    print "<p>The Device opens an anomaly and you step through. <input type=\"submit\" value=\"Go back through Anomaly\"></p></form>";
                  }
                }
         }
            } else {
              $hp = get_value_from_users("hp", $connection);
                  if ($hp > 0) {
                 if (fight($connection)) {
                    print "<p>The anomaly you came through is here.   <input type=\"submit\" value=\"Run back to the anomaly\"></p></form>";
             } else {
                   print "<p>The anomaly you came through is here.   <input type=\"submit\" value=\"Go back through\"></p></form>";
            }
           }
        }

            if ($water) {
               print "<p>There is water pouring out of the anomaly.</p>";
            }
          }
       }
    }

    function leek_critter_id($leek_critter) {
         if ($leek_critter == 1) {
            return 4;
         } else if ($leek_critter == 2) {
           return 5;
         } else {
           return 1;
         }
    }

    function get_a_critter($location_id, $connection) {
         $critter_ids = get_value_for_location_id("critter", $location_id, $connection);
         $critter_array = explode(",", $critter_ids);
         $pick = rand(0, count($critter_array) - 1);
         return $critter_array[$pick];
    }

    function create_new_fight_event($critter_id, $connection) {
         $user_id = get_user_id($connection);
         $location_id = get_location($connection);
             $default_hp = get_value_for_critter_id("hp", $critter_id, $connection);
         $sql = "INSERT INTO events (user_id, location_id, fight, critter, critter_hp, resolved) values ('$user_id', $location_id, 1, $critter_id, $default_hp, 0)";
         if (!$connection->query($sql))
            showerror($connection);
    }

    function create_anomaly_junction($connection) {
         $user_id = get_user_id($connection);
         $location_id = get_location($connection);
         $sql = "INSERT INTO junctions (user_id, location_id) values ('$user_id', $location_id)";
         if (!$connection->query($sql))
            showerror($connection);

         junction_anomaly_set("a1", $connection);
         junction_anomaly_set("a2", $connection);
         junction_anomaly_set("a3", $connection);
         junction_anomaly_set("a4", $connection);
         junction_anomaly_set("a5", $connection);
         junction_anomaly_set("a6", $connection);
         junction_anomaly_set("a7", $connection);
         junction_anomaly_set("a8", $connection);
         junction_anomaly_set("a9", $connection);
         junction_anomaly_set("a10", $connection);

    }

    function junction_anomaly_set($anomaly, $connection) {
         $user_id = get_user_id($connection);
         $location_id = get_location($connection);
             $coin = rand(0,1);
         if ($coin == 0) {
            $sql = "UPDATE junctions set {$anomaly}=0 where user_id='$user_id' AND location_id='$location_id'";
            if (!$connection->query($sql)) {
                   showerror($connection);
                }
         } else {
            $dice = rand(1, 60);
            $sql = "UPDATE junctions set {$anomaly}=$dice where user_id='$user_id' AND location_id='$location_id'";
            if (!$connection->query($sql)) {
                   showerror($connection);
                }
         }
    }

    function critter_attack($leek, $connection) {
        $location_id = get_value_from_users("location_id", $connection);
        $hp = get_value_from_users("hp", $connection);
        $critter_hp=1;
        
        $fight_just_done = 0;
        if ($leek) {
            $leek_critter = get_value_from_users("leek_critter", $connection);
                if ($leek_critter < 4) {
                    $fight = 1;
                }
        } else {
            $fight = fight($connection);
        }
        

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
             if ($leek) {
                $critter_id = leek_critter_id($leek_critter);
                $critter_hp = get_value_from_users("leek_critter_hp", $connection);
             } else {
               $critter_id = get_value_for_event_id("critter", $event_id, $connection);
               $critter_hp = get_value_for_event_id("critter_hp", $event_id, $connection);
             }
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


    function player_attack($leek, $weapon_id, $connection) {
        $location_id = get_value_from_users("location_id", $connection);
        if ($leek) {
            $critter_hp = get_value_from_users("leek_critter_hp", $connection);
            $critter = get_value_from_users("leek_critter", $connection);
            $critter_id = leek_critter_id($critter);
        } else {
            $event_id = get_unresolved_event_id($connection);
            $critter_hp = get_value_for_event_id("critter_hp", $event_id, $connection);
            $critter_id = get_value_for_event_id("critter", $event_id, $connection);
        }
        
        $critter_name = get_value_for_critter_id("name", $critter_id, $connection);
        if ($critter_hp > 0) {
            $hit_percentage = get_value_for_weapon_id("hit_percentage", $weapon_id, $connection);
            $d10_roll = rand(0, 100);
            if ($d10_roll < $hit_percentage) {
                $damage = get_value_for_weapon_id("damage", $weapon_id, $connection);
                $critter_hp = $critter_hp - $damage;
                
                if ($leek) {
                    update_users("leek_critter_hp", $critter_hp, $connection);
                } else {
                    update_event($event_id, "critter_hp", $critter_hp, $connection);
                }
                
                if (get_value_for_weapon_id("stuns", $weapon_id, $connection)) {
                    print "<p>You stun the $critter_name.</p>";
                    if ($water) {
                        print "<p>It sinks beneath the waves.</p>";
                        update_event($event_id, "critter_hp", -1, $connection);
                    }
                    
                    $present_day = get_value_for_location_id("present_day", $location_id, $connection);
                    if ($present_day) {
                        print "<p>It is removed to the menagerie</p>";
                        if (!$leek) {
                            update_event($event_id, "critter_hp", -1, $connection);
                        } else {
                            update_users("leek_critter_hp", -1, $connection);
                        }
                    } else {
                        update_event($event_id, "stunned", default_stunned(), $connection);
                    }
                } else if ($critter_hp <= 0) {
                    $water = get_value_for_location_id("water", $location_id, $connection);
                    
                    if (!$water) {
                        print "<p>The $critter_name falls to the ground dead.</p>";
                    } else {
                        print "<p>The $critter_name sinks beneath the waves.</p>";
                    }
                } else {
                    if ($weapon_id == 5) {
                        print "<p>You stab the $critter_name but it continues to attack.</p>";
                    } else if ($weapon_id == 10) {
                        print "<p>Torrence savages the $critter_name but it continues to attack.</p>";
                    } else if ($weapon_id == 6 || $weapon_id == 9) {
                        print "<p>You hit the $critter_name but it continues to attack.</p>";
                    } else {
                        print "<p>You shoot the $critter_name but it continues to attack.</p>";
                    }
                }
            } else {
                print "<p>You miss the $critter_name.</p>";
            }
        }
    }

    function resolve_events($connection) {
        $user_id = get_user_id($connection);
        $sql = "SELECT event_id FROM events WHERE user_id='$user_id' AND location_id='$location_id' AND resolved=0";

        if (!$result = $connection->query($sql))
          showerror($connection);

        while ($row=$result->fetch_assoc()) {
          $value = $row["event_id"];
          $critter_stunned = get_value_for_event_id("stunned", $event_id, $connection);
          if ($critter_stunned > 0) {
              $critter_id = get_value_for_event_id("critter", $event_id, $connection);
                  $critter_name = get_value_for_critter_id("name", $critter_id, $connection);
              $new_stunned = $critter_stunned - 1;
              update_event($event_id, "stunned", $new_stunned, $connection);
              if ($critter_stunned == 0) {
             update_event($event_id, "resolved", 1, $connection);
              }
          }
        }


    }


    function fight_event($user_id, $location_id, $connection) {
         $location_id = get_value_from_users("location_id", $connection);
         $fight = get_value_for_location_id("fight", $location_id, $connection);
         return $fight;
    }

    function print_device($connection) {
         $h = get_value_from_users("has_device", $connection);
         if ($h == 1) {
             $charge = get_value_from_users("charge", $connection);
           
             print "<h4>The Device</h4>";
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
             print "<p>Charge: " . $charge . "</p>";
             print "<p><form method=\"POST\" action=\"main.php\">";
             print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
             print "<input type=\"hidden\" name=\"travel_type\" value=\"device\">";
            print "<center><table>";
             print "<tr>";
             $coord1 = get_value_from_users("c1_prev", $connection);
             if ($coord1 != 'Z') {
                 print_dial(1, $connection);
                 print_dial(2, $connection);
                 print_dial(3, $connection);
             } else {
                 print_z_dial(1, $connection);
                 print_z_dial(2, $connection);
                 print_z_dial(3, $connection);
             }
             print "</tr></table></center>";
         
             if ($charge > 0) {
                 $hp = get_value_from_users("hp", $connection);
                 $healing_start = get_value_from_users("healing_start", $connection);
                 if (!is_null($healing_start)) {
                     $heals = check_healing($healing_start, $connection);
                 }
                 if ($hp > 0 ||  $heals > 0) {
                     print "<input type=\"submit\" value=\"Activate Device\">";
                 } else {
                     print "<p>You are unconscious and unable to use the device.</p>";
                 }
             } else {
                 print "<p>The device is out of charge.  It recharges at 1 unit per 30 minutes.  You will need to wait.</p>";
             }
             print "</form>";
         }
    }
    
    function print_dial($dial, $connection) {
        $dial_prev = "c" . $dial . "_prev";
        $c1 = get_value_from_users($dial_prev, $connection);
        print "<td><select name=\"dial$dial\">";
        $select0 = "";
        $select1 = "";
        $select2 = "";
        $select3 = "";
        $select4 = "";
        $select5 = "";
        $select6 = "";
        $zero_coordinates = 0;
        if ($dial == 1) {
            $d100_roll = rand(1, 100);
            if ($d100_roll < default_zero_coordinates()) {
                $zero_coordinates = 1;
            }
        }

        if ($dial == 1 ) {
            if ($c1 == 'F') {
                $select5 = 'selected';
            } else if ($c1 == 'B') {
                $select1 = 'selected';
            } else if ($c1 == 'C') {
                $select2 = 'selected';
            } else if ($c1 == 'D') {
                $select3 = 'selected';
            } else if ($c1 == 'E')    {
                $select4 = 'selected';
            } else if ($c1 == 'Z') {
                $select6 = 'selected';
            } else {
                $select0 = 'selected';
            }
            print "<option $select0 value=\"A\">A</option>";
            print "<option $select1 value=\"B\">B</option>";
            print "<option $select2 value=\"C\">C</option>";
            print "<option $select3 value=\"D\">D</option>";
            print "<option $select4 value=\"E\">E</option>";
            print "<option $select5 value=\"F\">F</option>";
            if ($zero_coordinates || $c1 == 'Z') {
                print "<option $select6 value=\"Z\"> </option>";
            }
        } else if ($dial == 2) {
            if ($c1 == 'T') {
                $select5 = 'selected';
            } else if ($c1 == 'I') {
                $select1 = 'selected';
            } else if ($c1 == 'N') {
                $select2 = 'selected';
            } else if ($c1 == 'O') {
                $select3 = 'selected';
            } else if ($c1 == 'R')    {
                $select4 = 'selected';
            } else {
                $select0 = 'selected';
            }
            print "<option $select0 value=\"A\">A</option>";
            print "<option $select1 value=\"I\">I</option>";
            print "<option $select2 value=\"N\">N</option>";
            print "<option $select3 value=\"O\">O</option>";
            print "<option $select4 value=\"R\">R</option>";
            print "<option $select5 value=\"T\">T</option>";
        } else  {
            if ($c1 == 'T') {
                $select5 = 'selected';
            } else if ($c1 == 'D') {
                $select1 = 'selected';
            } else if ($c1 == 'E') {
                $select2 = 'selected';
            } else if ($c1 == 'G') {
                $select3 = 'selected';
            } else if ($c1 == 'S')    {
                $select4 = 'selected';
            } else {
                $select0 = 'selected';
            }
            print "<option $select0 value=\"C\">C</option>";
            print "<option $select1 value=\"D\">D</option>";
            print "<option $select2 value=\"E\">E</option>";
            print "<option $select3 value=\"G\">G</option>";
            print "<option $select4 value=\"S\">S</option>";
            print "<option $select5 value=\"T\">T</option>";
        }
        print "</select> &nbsp; &nbsp; </td>";
        print "<td>";
    }
    
    
    function print_z_dial($dial, $connection) {
        print "<td><select name=\"dial$dial\">";
        $select0 = "";
     
        $select0 = 'selected';

        print "<option $select0 value=\"X\"> </option>";
        print "</select> &nbsp; &nbsp; </td>";
        print "<td>";
    }

    function use_device($dial1, $dial2, $dial3, $connection) {
        if ($dial1 != 'Z') {
            $location_id = get_location_from_coords($dial1, $dial2, $dial3, $connection);
        } else {
            $location_id = 217;
        }
          $log = get_value_from_users("log", $connection);
          $log_entry = "(" . $dial1 . "," . $dial2 . "," . $dial3 .  ")";
          $log_array = explode(":", $log);
          if (!in_array($log_entry, $log_array)) {
             if ($log != '') {
                $new_log = $log . ":" . $log_entry;
            update_users("log", $new_log, $connection);
         } else {
            update_users("log", $log_entry, $connection);
         }
          }
          $charge = get_value_from_users("charge", $connection);
          if ($charge == default_charge()) {
         $now = now();
             update_users("recharge_start", $now, $connection);
          }
          $new_charge = $charge - 1;
          update_users("charge", $new_charge, $connection);
          return $location_id;
    }

    function check_charge($recharge_start, $connection) {
         $unixOriginalDate = strtotime($recharge_start);
             $unixNowDate = strtotime('now');
             $difference = $unixNowDate - $unixOriginalDate ;
             $days = (int)($difference / 86400);
             $hours = (int)($difference / 3600);
             $minutes = (int)($difference / 60);
             $seconds = $difference;
         //$now = new DateTime();
         //$rtime = date_create_from_format('Y\-m\-d\ H:i:s', $recharge_start);
         // $diff  = $rtime->diff($now);
        // if (($t = $diff->format("%m")) > 0)
         if ($days > 0)
            $charges = default_charge();
    //	 else if (($t = $diff->format("%d")) > 0)
    //	    $charges = default_charge();
    //	 else if (($t = $diff->format("%H")) > 0)
         else if ($hours > 0) {
            $charges = $hours*2;
            if (($minutes - $hours*60) > 30) {
                $charges = $charges + 1;
            }
    //	 else if (($t = $diff->format("%i")) > 0)
    //	    $charges = 2;
         } else if ($minutes > 30) {
             $charges = 1;
         } else {
            $charges = 0;
         }
        return $charges;
    }

    function check_healing($healing_start, $connection) {
            $unixOriginalDate = strtotime($healing_start);
            $unixNowDate = strtotime('now');
            $difference = $unixNowDate - $unixOriginalDate ;
            $days = (int)($difference / 86400);
            $hours = (int)($difference / 3600);
            $minutes = (int)($difference / 60);
            $seconds = $difference;
         //$now = new DateTime();
         //$rtime = date_create_from_format('Y\-m\-d\ H:i:s', $recharge_start);
         // $diff  = $rtime->diff($now);
        // if (($t = $diff->format("%m")) > 0)
        // print "Difference: $difference";
        // print "Minutes: $minutes";
       //  print "Hours: $hours";
            $charges = 0;
            if ($days > 0)
                $charges = default_health();
    //	 else if (($t = $diff->format("%d")) > 0)
    //	    $charges = default_health();
    //	 else if (($t = $diff->format("%H")) > 0)
            else if ($hours > 0)
                $charges = $hours*2;
    //	 else if (($t = $diff->format("%i")) > 0)
    //	    $charges = 2;
            else if ($minutes > 30)
                $charges = $charges + 1;
            else
                $charges = 0;
        // print "Charges: $charges";
            return $charges;
    }

    function now() {
             $datetime = new DateTime();
         $now = $datetime->format('Y\-m\-d\ H:i:s');
         return $now;
    }

    function get_location_from_coords($dial1, $dial2, $dial3, $connection) {
        $sql = "SELECT location_id FROM locations WHERE tm_coord_1 = '{$dial1}' AND tm_coord_2 = '{$dial2}' AND tm_coord_3 = '{$dial3}'";

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

        function in_land_of_fiction($mysql) {
            $location_id = get_location($mysql);
            $f = get_value_for_location_id("tm_coord_2", $location_id, $mysql);
            if ($f == 'Z') {
                return true;
            }
            return false;
        }
    
        function update_prev_coordinates($mysql) {
            $travel_type = get_value_from_users("travel_type", $mysql);
            if ($travel_type == 'device') {
                $location_id = get_location($mysql);
                $c1 = get_value_for_location_id("tm_coord_1", $location_id, $mysql);
                update_users("c1_prev", $c1, $mysql);
                $c2 = get_value_for_location_id("tm_coord_2", $location_id, $mysql);
                update_users("c2_prev", $c2, $mysql);
                $c3 = get_value_for_location_id("tm_coord_3", $location_id, $mysql);
                update_users("c3_prev", $c3, $mysql);
            }
        }
    
    function print_sanctuary_start($mysql) {
        print "<div class=\"dynamic\">";
        print "<div class=\"action\">";
        print_character_joined($mysql);
        print_item_used(0, $mysql);
        print_health($mysql);
        print_wait($mysql);
        $hp = get_value_from_users("hp", $mysql);
        if ($hp > 0) {
            $prev = get_value_from_users("prev_location", $mysql);
            print "<p>You feel like you are waking up and can leave Sanctuary if you wish</p>";
            print "<form method=\"POST\" action=\"main.php\"><input type=\"hidden\" name=\"location\" value=\"" . $prev . "\">";
            print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
            print "<input type=\"hidden\" name=\"travel_type\" value=\"wake\">";
            print "<input type=\"submit\" value=\"Wake Up\">";
            print "</form>";
        }
        print "</div>";
        $has_device = get_value_from_users("has_device", $mysql);
        $phase = get_value_from_users("phase", $mysql);
        if ($phase > 1 || $has_device) {
            update_prev_coordinates($mysql);
            print "<div class=device>";
            print_device($mysql);
            print_equipment($mysql);
            print "</div>";
        }
        print "</div>";
        
    }
    
    function print_standard_start($mysql) {
          print "<div class=\"dynamic\">";
          print "<div class=\"action\">";
          print_character_joined($mysql);
          print_item_used(0, $mysql);
          critter_attack(0, $mysql);
          print_health($mysql);
           if (! in_land_of_fiction($mysql)) {
                print_anomaly($mysql);
            }
          print_wait($mysql);
          print "</div>";
          $has_device = get_value_from_users("has_device", $mysql);
          $phase = get_value_from_users("phase", $mysql);
          if ($phase > 1 || $has_device) {
              update_prev_coordinates($mysql);
              print "<div class=device>";
              print_device($mysql);
              print_equipment($mysql);
              print "</div>";
          }
          print "</div>";
        
    }
    
    function print_critter_trail_start($critter_id, $mysql) {
        print "<div class=\"dynamic\">";
        print "<div class=\"action\">";
        print_character_joined($mysql);
        print_item_used(0, $mysql);
        print "You are on the Critter Trail!";
        if (!met_critter($critter_id, $mysql)) {
            create_new_fight_event($critter_id, $mysql);
            $event_id = get_unresolved_event_id($mysql);
            $critter_hp = get_value_for_critter_id("hp", $critter_id, $mysql);
            update_event($event_id, "critter_hp", $critter_hp, $mysql);
            add_critter($critter_id, $mysql);
            critter_attack(0, $mysql);
        } else {
            critter_attack(0, $mysql);
        }
        print_health($mysql);
        if (! in_land_of_fiction($mysql)) {
            print_anomaly($mysql);
        }
        print_wait($mysql);
        print "</div>";
        $has_device = get_value_from_users("has_device", $mysql);
        $phase = get_value_from_users("phase", $mysql);
        if ($phase > 1 || $has_device) {
            update_prev_coordinates($mysql);
            print "<div class=device>";
            print_device($mysql);
            print_equipment($mysql);
            print "</div>";
        }
        print "</div>";
        
    }
    
    function print_leek_start($mysql) {
          print "<div class=\"dynamic\">";
          print "<div class=\"action\">";
          print_character_joined($mysql);
          print_item_used(1, $mysql);
          critter_attack(1, $mysql);
          print_health($mysql);
          print_anomaly($mysql);
          print_wait($mysql);
          print "</div>";
          $has_device = get_value_from_users("has_device", $mysql);
          $phase = get_value_from_users("phase", $mysql);
          $critter_hp = get_value_from_users("leek_critter_hp", $mysql);
          $critter_number = get_value_from_users("leek_critter", $mysql);
          //if ($critter_number > 2 && $critter_hp <=0) {
          //	    update_users("has_device", 1, $mysql);
          //}
          if ($phase > 1 || $has_device) {
            update_prev_coordinates($mysql);
            print "<div class=device>";
            print_device($mysql);
            print_equipment($mysql);
            print "</div>";
          }
          print "</div>";
    }

    function print_health($mysql) {
         $hp = get_value_from_users("hp", $mysql);
         if ($hp < default_health()) {
             $healing_start = get_value_from_users("healing_start", $mysql);
             if (!is_null($healing_start)) {
                 $time_difference = check_healing($healing_start, $mysql);
                 if ($time_difference > 0) {
                     if ($time_difference + $hp > default_health()) {
                         $hp = default_health();
                         update_users("hp", $hp, $mysql);
                     } else {
                         $hp = $time_difference + $hp;
                         $now = now();
                         update_users("healing_start", $now, $mysql);
                         update_users("hp", $hp, $mysql);
                    }
                 }
              }
         }
         if ($hp == 0) {
            print "<p><b>You are Unconscious!</b>  Check back in 1 hour.</p>";
         } else if ($hp < 4) {
            print "<p><b><font color=red>You are very badly hurt.</font></b></p>";
         } else if ($hp < 8) {
            print "<p><b>You are badly hurt.</b></p>";
         } else if ($hp < 12) {
            print "<p>You are hurt.</p>";
         } else if ($hp < 16) {
            print "<p>You are slightly hurt.</p>";
         } else if ($hp < 20) {
            print "<p>You are OK, but not at full health.</p>";
         }
        
    }

    function print_character_joined($connection) {
         $last_action = get_value_from_users("last_action", $connection);
         if ($last_action == "travel") {
             $new_character = get_value_from_users("new_character", $connection);
             if ($new_character != '' && $new_character != 'sid') {
                 $char_id_list = get_value_from_users("char_id_list", $connection);
                 $char_id = get_value_for_name_from("char_id", "characters", $new_character, $connection);
                 if ($char_id_list != 0 ) {
                     $new_char_id_list = $char_id_list . "," . $char_id;
                     update_users("new_character", '', $connection);
                     if (!check_for_character($new_character, $connection)) {
                         update_users("char_id_list", $new_char_id_list, $connection);
                         }
                 } else {
                     $char_id_array = explode(",", $char_id_list);
                     if (!in_array($char_id, $char_id_array)) {
                         update_users("new_character", '', $connection);
                         update_users("char_id_list", $char_id, $connection);
                     }
                 }
                 $ucchar = ucfirst($new_character);
                 $sex = get_value_for_name_from("gender", "characters", $new_character, $connection);
                 $pronoun = "He";
                 if ($sex == 2) {
                     $pronoun = "She";
                 }
                 if ($sex == 3) {
                     $pronoun = "They";
                 }
            
                 print "<p>$ucchar has joined you on your travels.  $pronoun is stored in your user profile.</p>";
             } else if ($new_character == 'sid') {
                 $char_id_list = get_value_from_users("char_id_list", $connection);
                 if ($char_id_list != 0 ) {
                     $new_char_id_list = $char_id_list . "," . "21" . "," . "47";
                     update_users("new_character", '', $connection);
                     update_users("char_id_list", $new_char_id_list, $connection);
                 } else {
                     $char_id_array = explode(",", $char_id_list);
                     if (!in_array("21", $char_id_array)) {
                         update_users("new_character", '', $connection);
                         update_users("char_id_list", "21,47", $connection);
                     }
                 }
                 print "<p>Sid and Nancy have joined you on your travels.  They are stored in your user profile.</p>";
             }
         }
    }

    function print_equipment($connection) {
       $equip_id_list = get_value_from_users("equipment", $connection);
       $uses_list = get_value_from_users("uses", $connection);
       print "<center><div class=equipment>";
       if (! is_null($equip_id_list) && $equip_id_list != "" && equip_id_list != "14") {
          print "<h2>Equipment</h2>";
          $hp = get_value_from_users("hp", $connection);
          if ($hp > 0) {
             $equip_id_array = explode(",", $equip_id_list);
             $uses_array = explode(",", $uses_list);
             $i = 0;
             print "<table border>";
             print "<tr><th>Item</th><th>Uses Left</th><th>Use</th></tr>";
             foreach ($equip_id_array as $equip) {
                 if ($equip != '14') {
                     print "<tr>";
                     print "<form method=\"POST\" action=\"main.php\">";
                     print "<td>";
                     $equip_name = get_value_for_equip_id("name", $equip, $connection);
                     print $equip_name;
                     print "</td><td>";
                     if ($uses_array[$i] < 500) {
                         print $uses_array[$i];
                     }
                     print "</td><td>";
                     $location_id=get_location($connection);
                     print "<input type=\"hidden\" name=\"location\" value=\"" . $location_id . "\">";
                     print "<input type=\"hidden\" name=\"travel_type\" value=\"none\">";
                     print "<input type=\"hidden\" name=\"item_used\" value=$equip>";
                     print "<input type=\"hidden\" name=\"last_action\" value=\"item\">";
                    if ($uses_array[$i] > 0) {
                        print "<input type=\"submit\" value=\"Use\">";
                    }
                     print "</td></form></tr>";
                 }
                 $i++;
             }
             print "</table>";
          } else {
             print "<p>You are unconscious and unable to use your equipment.</p>";
          }
       }
       print "</div></center>";
    }

    function print_item_used($leek, $connection) {
         $last_action = get_value_from_users("last_action", $connection);
         $item_used = get_value_from_users("item_used", $connection);

         if ($leek) {
              $leek_critter = get_value_from_users("leek_critter", $connection);
              if ($leek_critter < 5) {
                 $fight = 1;
              } else {
                $fight = 0;
              }
         } else {
              $fight = fight($connection);
         }

         if ($item_used != 0 & $last_action == "item") {
              $can_use = item_used($leek, $fight, $item_used, $connection);
              if ($can_use) {
                 if (!$fight || !is_weapon($item_used, $connection)) {
                  $default_message = get_value_for_equip_id("use_message", $item_used, $connection);
                  if ($item_used != 12) {
                       print "<p>$default_message</p>";
                  } else {
                       print "<p>$default_message";
                       $d60_roll = rand(1, default_random_locations());
                   print "<form method=\"POST\" action=\"main.php\">";
                   print "<input type=\"hidden\" name=\"location\" value=\"" . $d60_roll . "\">";
                           print "<input type=\"hidden\" name=\"travel_type\" value=\"anomaly\">";
                          print "<input type=\"hidden\" name=\"last_action\" value=\"travel\">";
                          print "<input type=\"submit\" value=\"Go through the anomaly\">";
                          print "</form>";
                          print "</p>";
                  }
             }
              } else {
                 print "<p>You can no longer use this item</p>";
              }
              update_users("item_used", 0, $connection);
         }
    }

    function item_used($leek, $fight, $equip_id, $connection) {
          $equip_id_list = get_value_from_users("equipment", $connection);
          $equip_id_array = explode(",", $equip_id_list);
          $i = 0;
          foreach ($equip_id_array as $equip) {
                  if ($equip == $equip_id) {
                 if (is_weapon($equip_id, $connection)) {
                $weapon_id = get_weapon_id($equip_id, $connection);
                if ($fight) {
                   player_attack($leek, $weapon_id, $connection);
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

    function is_weapon($equip_id, $connection) {
      $sql = "SELECT *  FROM weapons WHERE equip_id = '{$equip_id}'";

      if (!$result = $connection->query($sql))
          showerror($connection);
      
      if ($result->num_rows != 1)
          return 0;
      else {
           return 1;
      }

    }



    function add_equipment($equip, $connection) {
       if (! check_for_equipment($equip, $connection)) {
          $equip_id = get_value_for_name_from("equip_id", "equipment", $equip, $connection);
          $default_uses = get_value_for_name_from("default_uses", "equipment", $equip, $connection);
          $equip_id_list = get_value_from_users("equipment", $connection);
          if (is_null($equip_id_list) || $equip_id_list == "") {
             $new_equip_id_list = $equip_id;
         $uses = $default_uses;
          } else {
             $new_equip_id_list = $equip_id_list . "," . $equip_id;
         $uses_list = get_value_from_users("uses", $connection);
         $uses = $uses_list . "," . $default_uses;
          }
          update_users("equipment", $new_equip_id_list, $connection);
          update_users("uses", $uses, $connection);
       } else {
          $equip_id_list = get_value_from_users("equipment", $connection);
          $equip_id = get_value_for_name_from("equip_id", "equipment", $equip, $connection);
          $default_uses = get_value_for_name_from("default_uses", "equipment", $equip, $connection);
          if ($default_uses < 500) {
             $uses_list = get_value_from_users("uses", $connection);
             $uses_array = explode(",", $uses_list);
             $equip_id_array = explode(",", $equip_id_list);
         $i = 0;
         foreach ($equip_id_array as $eq) {
             if ($eq == $equip_id) {
                 $use = $uses_array[$i];
             $new_use = $use + $default_uses;
             if ($new_use > 100) {
                $new_use = 100;
             }
             $uses_array[$i] = $new_use;
                 $new_uses_list =  join(",", $uses_array);
             update_users("uses", $new_uses_list, $connection);
             }
             $i = $i + 1;
         }
          }
       }
    }

    function check_location($location, $connection) {
        $real_location = get_location($connection);
        
        if ($real_location != 225) {
            $library_f = get_value_from_users("library_fanfic", $connection);
            if ($library_f != 0) {
                update_users("library_fanfic", 0, $connection);
            }
        }

        if ($real_location == $location) {
            return 1;
        } else {
            $location_string = "location" . $real_location;
            header("Location: $location_string.php");
            exit;
        }
    }
    
    function print_travel($location, $db) {
        $budget = check_for_equipment("budget", $db);
        $hp = get_value_from_users("hp", $db);
        if ($hp > 0 && $budget) {
            $budget_count = get_budget_index($db);
            $uses_list = get_value_from_users("uses", $db);
            $uses_array = explode(",", $uses_list);
            $budget_value = $uses_array[$budget_count];
            print ("Your budget is $budget_value");
            print "<p>From here you can get by conventional transport to:<ul>";
            $accessible = get_present_day_locations($db);
            $sorted = array();
            foreach ($accessible as $by_car) {
                $name = get_value_for_location_id("name",$by_car,$db);
                array_push($sorted, $name);
            }
            sort($sorted);
            foreach ($sorted as $by_car) {
                if ($by_car != 19) {
                    print "<li>";
                    $s_id = get_value_for_name_from("location_id","locations",addslashes($by_car),$db);
                    print_accessible_location($location, $s_id, $db);
                    print "</li>";
                }
            }
            print "</ul></p>";
            
            
            
        }
    }
    
    function get_budget_index($db) {
        $equip_id_list = get_value_from_users("equipment", $db);
         $equip_id_array = explode(",", $equip_id_list);
        // 14 is the value for budget - hard coding - bad Louise
        $budget_count = array_search('14', $equip_id_array);
        return $budget_count;
    }
?>
