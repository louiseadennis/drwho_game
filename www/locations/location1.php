<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate();

    $db = connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(1, $db);

// $phase = get_user_phase($db);
    $d100_roll = rand(1, 100);

?>
<html>
<head>
<title>Dr Who Game - Placeholder Location 1</title>

<link rel="stylesheet" href="../styles/default.css?v=12" type="text/css">

</head>
<body>
<?php
    print_header($db);
    print_standard_start($db);
?>
<div class=main>

<div class=location>
<img src=../assets/locations/location1.png>
<h2>I. M. Foreman, Scrapyard, Totter's Lane, 1963</h2>

</div>
</body>
</html>
