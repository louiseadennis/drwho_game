<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate(default_url());

    $db = connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(2, $db);
    
    // This is Revenge of the Cybermen - Story 1.
    start_story(1, $db);
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
<?php
   print_standard_start($db);
?>

<div class=location>
<h2>Nerva Beacon, 2873</h2>
<img src=../assets/locations/location2.png alt="Still of the Transmat Room in the Nerva Beacon.">

<?php
    print_tardis_team($db);
?>

</div>
</div>
</body>
</html>
