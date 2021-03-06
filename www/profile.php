<?php

require_once('./config/accesscontrol.php');

// Set up/check session and get database password etc.
require_once('./config/MySQL.php');
require_once('utilities.php');
session_start();
sessionAuthenticate(default_url());

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);

$uname = $_SESSION["loginUsername"];
$location = get_location($db);
    
    $tardis_crew_change = $_POST['char_id'];
    
    if ($tardis_crew_change != "") {
        if (tardis_crew_member($tardis_crew_change, $db)) {
            leave_crew($tardis_crew_change, $db);
        } else {
            join_crew($tardis_crew_change, $db);
        }
    }

?>
<html>
<head>
<title>Dr Who Game -
<?php
    echo $uname;
?>
 Profile</title>

<link rel="stylesheet" href="./styles/default.css?v=1" type="text/css">
</head>
<body>
<?php
    print_header_info_pages($db);
    ?>


<div class=main style="padding:1em">

<h1>User Profile</h1>

<h2>User Details</h2>
Username:
<?php
echo $uname;
?>


<?php
$char_id_list = get_value_from_users("char_id_list", $db);
if ($char_id_list != '') {
   print "<h2>Characters</h2>";
    print "<p>The Tardis crew can be changed in between adventures.</p>";
    $story = get_value_from_users("story", $db);
    $between_adventures = ($story == 0);
    print "<h3>Doctors</h3>";
    $doctor_array = get_doctors($db);
    $i = 0;
    print "<form form method=\"POST\" action=\"profile.php\">";
    print "<table>";
    foreach($doctor_array as $doctor) {
        $char_name = get_value_for_char_id("name", $doctor, $db);
        $uchar = ucfirst($char_name);
        if ($i == 0) {
            print "<tr>";
        }
        $no_space_char_name = str_replace(" ", "_", $char_name);
        if (is_conscious($doctor, $db)) {
            print "<td align=center><p><img src=assets/$no_space_char_name.png alt=\"$uchar.\"></p><p>$uchar</p><p>";
        } else {
            print "<td align=center><p><img stlye=\"opacity:0.2\" src=assets/$no_space_char_name.png alt=\"$uchar.\"></p><p>$uchar</p><p>";
        }
        
        if ($between_adventures & is_conscious($doctor, $db)) {
            if (tardis_crew_member($doctor, $db)) {
                print "<input type=\"radio\" name=\"char_id\" value=\"$doctor\" checked>";
                print "Current Doctor";
            } else {
                print "<input type=\"radio\" name=\"char_id\" value=\"$doctor\">";
                // print "Current Doctor";
            }
        }
        
        print "</p></td>";
        if ($i == 5) {
            print "</tr>";
            $i = 0;
        } else {
            $i = $i + 1;
        }
    }
    print "</table>";
    if ($between_adventures) {
        print "<input type=\"submit\" value=\"Change Current Doctor\" style=\"font-size:2em\">";
    }
    print "</form>";
    print "<h3>Companions</h3>";
   print "<table>";
   $char_id_array = explode(",", $char_id_list);
   $i = 0;
   foreach ($char_id_array as $char_id) {
       $is_doctor = get_value_for_char_id("doctor", $char_id, $db);
       if (!$is_doctor) {
          $char_name = get_value_for_char_id("name", $char_id, $db);
          $uchar = ucfirst($char_name);
          if ($i == 0) {
             print "<tr>";
          }
          $no_space_char_name = str_replace(" ", "_", $char_name);
           if (is_conscious($char_id, $db)) {
               print "<td align=center><p><img src=assets/$no_space_char_name.png alt=\"$uchar.\"></p><p>$uchar</p><p>";
           } else {
               print "<td align=center><p><img style=\"opacity:0.2\" src=assets/$no_space_char_name.png alt=\"$uchar.\"></p><p>$uchar</p><p>";
           }
           if ($between_adventures) {
               $max_size = max_tardis_crew();
               if (tardis_crew_size($db) < $max_size) {
                   if (!tardis_crew_member($char_id, $db)) {
                       print "<form form method=\"POST\" action=\"profile.php\">";
                       print "<input type=\"hidden\" name=\"char_id\" value=\"";
                       print $char_id;
                       print "\"><input type=\"submit\" value=\"Add to Tardis Crew\" style=\"font-size:2em\"></form>";
                   }
               }
               
               
               if (tardis_crew_member($char_id, $db)) {
                   if (is_conscious($char_id, $db)) {
                       print "<form form method=\"POST\" action=\"profile.php\">";
                       print "<input type=\"hidden\" name=\"char_id\" value=\"";
                       print $char_id;
                       print "\"><input type=\"submit\" value=\"Remove from Tardis Crew\" style=\"font-size:2em\"></form>";
                   } else {
                       print "<br>&nbsp;";
                   }
               }
           }
          print "</p></td>";
          if ($i == 5) {
             print "</tr>";
         $i = 0;
          } else {
            $i = $i + 1;
          }
       }
   }
   print "</table>";
}

print "<h2>Monsters and Villains Encountered</h2>";
$critter_id_list = get_value_from_users("critter_id_list", $db);
$critter_id_array = explode(",", $critter_id_list);
print "<table>";
$i = 0;
$j = 1;
while ($j <= critter_number($db)) {
    if ($i == 0) {
    	 print "<tr>";
    }
    if (in_array($j, $critter_id_array)) {
        $icon = get_value_for_critter_id("icon", $j, $db);
    } else {
        $icon = 'unknown_critter.png';
    }
    
    $stories = get_value_for_critter_id("stories", $j, $db);
 
    if (!is_null($icon)) {
        print "<td><img src=assets/$icon title=\"Find at: $stories\"></td>";
    } else {
        $critter_name = get_value_for_critter_id("name", $j, $db);
        print "<td>$critter_name<br>Sorry no icon!</td>";
    }
    if ($i == 7) {
    	 print "</tr>";
         $i = 0;
      } else {
      	$i = $i + 1;
     }
   $j++;
}
print "</table>";
    
    print "<h2>Allies Encountered</h2>";
    $ally_id_list = get_value_from_users("ally_id_list", $db);
    $ally_id_array = explode(",", $ally_id_list);
    print "<table>";
    $i = 0;
    $j = 1;
    while ($j <= ally_number($db)) {
        if ($i == 0) {
             print "<tr>";
        }
        if (in_array($j, $ally_id_array)) {
            $icon = get_value_for_ally_id("icon", $j, $db);
        } else {
            $icon = 'unknown_ally.png';
        }
        
        $stories = get_value_for_ally_id("stories", $j, $db);
     
        if (!is_null($icon)) {
            print "<td><img src=assets/$icon title=\"Find at: $stories\"></td>";
        } else {
            $ally_name = get_value_for_ally_id("name", $j, $db);
            print "<td>$ally_name<br>Sorry no icon!</td>";
        }
        if ($i == 7) {
             print "</tr>";
             $i = 0;
          } else {
            $i = $i + 1;
         }
       $j++;
    }
    print "</table>";
    
    print "<h2>Stories Successfully Completed</h2>";
    $log = get_value_from_users("story_id_list", $db);
    $log_array = explode(",", $log);
    print "<table>";
    $i = 0;
    $j = 1;
    while ($j <= story_number($db)) {
        if ($i = 0) {
            print "<tr>";
        }
        if (in_array($j, $log_array)) {
            $badge = get_value_for_story_id("badge", $j, $db);
            $icon = str_replace(".png", "_badge.png", $badge);
            // print $badge;
            // print "$icon";
        } else {
            $icon = 'unknown_story.png';
        }
        
        if (!is_null($icon)) {
            print "<td><img src=assets/$icon></td>";
        } else {
            $text = get_value_for_story_id("title", $j, $db);
            print "<td>$text<br>";
        }
        
        if ($i == 7) {
              print "</tr>";
              $i = 0;
           } else {
             $i = $i + 1;
          }
        $j++;
    }
    print "</table>";


?>
<p><form method="POST" action="main.php">
<input type="hidden" name="location_id" value="
<?php
echo $location
?>
">
<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>
