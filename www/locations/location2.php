<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate(default_url());

    $db = connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(2, $db);
    
?>
<html>
<head>
<title>Dr Who Game - Nerva Beacon, 2873</title>

<link rel="stylesheet" href="../styles/default.css?v=12" type="text/css">

</head>
<body>
<?php
    print_header($db);
?>

<div class=main>
<div class=top_matter>
<?php
   print_standard_start($db);
    // This is Revenge of the Cybermen - Story 1.
   start_story(1, $db);
?>
</div>
<hr>

<div class=location>
<center>
<h2>Nerva Beacon, 2873</h2>
<img src=../assets/locations/location2.png align=center alt="Still of the Transmat Room in the Nerva Beacon.">
</center>

<?php
    $event = get_current_event($db);
    if ($event == 1) {
        $critter_id = get_value_for_name_from("critter_id", "critters", "Cybermat", $db);
        
        add_critter($critter_id, $db);
        
        $icon = get_value_for_critter_id("icon", $critter_id, $db);
        // print $icon;
        print("<br><img src=../assets/$icon align=left>")
        ?>
<i><p>The Nerva Beacon watches over a new asteroid orbiting Jupiter, warning spacecraft of its location.  When the Tardis crew arrive it is an eerie place full of dead bodies: victims of a mysterious plague.</p>
        
        <p>As the crew explore a Cybermat suddenly attacks!</p></i>
<?php
    } else if ($event == 2) {
        $bitten = get_event_character($db);
        $name = get_value_for_char_id("name", $bitten, $db);
        modify_character($bitten, 2, $db); // Bitten by cybermat modifer
        // unconscious($bitten, $db);
        
        $gender = get_value_for_char_id("gender", $bitten, $db);
        
        $pronoun = "She";
        $pronoun2 = "her";
        if ($gender == 1) {
            $pronoun = "He";
            $pronoun2 = "his";
        }
        
        print("<i><p>$name has been bitten by a cybermat.  $pronoun is unconscious with black lines growing across $pronoun2 face.<p></i>");
    } else if ($event == 3) {
        print("<i><p>Cybermen invade the beacon!  Aided by a traitor, Kellman, from the surviving crew.<p></i>");
    }
    
    print_tardis_team($db);
?>

</div>

<div class=travel_sidebar>
<h2>Travel</h2>

<?php
    print_tardis($db);
    print_transmat(3, $db);
    ?>
</div>
<div class=action_sidebar>
<h2>Actions</h2>

<?php
    print_default_actions($db);
    ?>
</div>

</div>
</body>
</html>
