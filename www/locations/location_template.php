<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate(default_url());

    $db = connect_to_db( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(NUM, $db);
    
?>
<html>
<head>
<title>Dr Who Game - NAME</title>

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
   start_story(NUM, $db);
?>
</div>
<hr>

<div class=location>
<center>
<h2>NAME</h2>
<img src=../assets/locations/IMAGE.png alt="ALTTEXT" align=center>
</center>

<?php
    print_tardis_team($db);
?>

</div>

<div class=travel_sidebar>
<h2>Travel</h2>
<?php
    print_tardis($db);
    // print_transmat(2, $db);
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
