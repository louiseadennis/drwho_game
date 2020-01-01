<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate(default_url());

    $db = connect_to_db( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(3, $db);
    // check_location(3, $db);
    
?>
<html>
<head>
<title>Dr Who Game - Voga, 2873</title>

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

<div class=location>
<center>
<h2>Voga, 2873</h2>
<img src=../assets/locations/location3.png alt="Still of the Transmat Point on Voga - filmed at Wookey Hole." align=center>
</center>

<?php
    $event = get_current_event($db);
    if ($event == 4) {
        $ally_id = get_value_for_name_from("ally_id", "allies", "Vogans", $db);
        add_ally($ally_id, $db);
        $icon = get_value_for_ally_id("icon", $ally_id, $db);
        print("<br><img src=../assets/$icon align=left>");
        
        print("<i><p>The Tardis lands deep in a cave system where the crew are almost immediately captured by the Vogan inhabitants.</p></i>");
        
        lock_everyone_up(3, $db);
        
    }
    
    if ($event == 5) {
        print("<i><p>The crew meet up again but some have planet destroying bombs strapped to their chests.</p></i>");
    }

    if ($event == 6) {
        print("<i><p>The crew arrive on Voga with bombs strapped to their chests.</p></i>");
    }

    if ($event == 13) {
        free_everyone(3, $db);
    }
    
    if ($event == 12 || $event == 14) {
        end_story(1, $db);
        
        print("<i><p>The Cybermen have been defeated!</p></i>");
    }
    
    print_tardis_team($db);
?>

</div>

<div class=travel_sidebar>
<h2>Travel</h2>
<?php
    print_tardis($db);
    print_transmat(2, $db);
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
