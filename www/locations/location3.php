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
    print_event_long($db);
     


    if ($event == 6) {
        if (first_visit($db)) {
            foreach (characters_at_location(3, $db) as $char_id) {
                if (get_value_for_char_in_play_id("prev_location", $char_id, $db) != 3) {
                    modify_character($char_id, 1, $db);
                }
            }
            visited($db);
        }
        
        print("<i><p>The crew arrive on Voga with bombs strapped to their chests.</p></i>");
    }
    
    if ($event == 12 || $event == 14) {
        remove_modification(1, $db);
        end_story(1, $db);
        
        print("<i><p>The Cybermen have been defeated!</p></i>");
    }
    
    if ($event == 15) {
        remove_modification(1, $db);
        fail_story(1, $db);
        
        print("<i><p>Everyone Dies.  This story is over.</p></i>");
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
