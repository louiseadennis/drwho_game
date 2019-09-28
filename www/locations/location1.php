<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate(default_url());

    $db = connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(1, $db);
    
    // Add the first Doctor and Susan at start.
    $char_id_list = get_value_from_users("char_id_list", $db);
    if ($char_id_list == 0 || $char_id_list == '') {
        update_users("char_id_list", "1,15", $db);
    }
    
    $tardis_team_list = get_value_from_users("tardis_team", $db);
    if ($tardis_team_list == 0 || $tardis_team_list == '') {
        // update_users("tardis_team", "1,15", $db);
        join_crew(1, $db);
        join_crew(15, $db);
    }
    
    $barbara_collected = check_for_character('barbara', $db);
// $phase = get_user_phase($db);
 //   $d100_roll = rand(1, 100);

?>
<html>
<head>
<title>Dr Who Game - Totter's Lane 1963</title>

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
   start_story(0, $db);
?>
</div>
<hr>

<div class=location>
<center>
<h2>I. M. Foreman, Scrapyard, Totter's Lane, 1963</h2>
<img src=../assets/locations/location1.png alt="Still of the Totter's Lane Junkyard Entrance in 1963." align=center>
</center>

<i><p>A foggy winter's night, in a London back street; the little road was empty and silent.  A tall figure loomed up out of the fog -- the helmeted, caped figure of a policeman patrolling his beat.</p>
<p> He moved along the little street, trying shop doors, walked on past the shops to where the street ended in a high blank wall.  There were high wooden gates in the wall, with a smaller, entry-gate set into one of them.</p>
<p>The policeman shone his torch onto the gates, holding the beam for a moment on a faded notice:<br>
I. M. Foreman<br>
Scrap Merchant.</p></i>
<p align=right>- Terrance Dicks</p>

<?php
    if (!$barbara_collected) {
        print_character_image('barbara', $db);
        print_character_image('ian', $db);
        encountered_new_character('barbara', $db);
        encountered_new_character('ian', $db);
        print "<p>Two school teachers, Ian Chesterton and Barbara Wright, stumble into the Tardis in search of their mysterious pupil, Susan, an unearthly child.</p>";
    }
    print_tardis_team($db);
?>

</div>

<div class=travel_sidebar>
<h2>Travel</h2>
<?php
    print_tardis($db);
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
