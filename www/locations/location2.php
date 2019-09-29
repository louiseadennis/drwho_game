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
        ?>
        <i><p>The Nerva Beacon is an eerie place full of dead bodies from a mysterious plague that has infected the inhabitants.</p>
        
        <p>As the crew explore a Cybermat suddenly attacks!</p></i>
<?php
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
