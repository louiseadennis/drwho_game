<?php

    require_once('../config/accesscontrol.php');
    require_once('../utilities.php');

    // Set up/check session and get database password etc.
    require_once('../config/MySQL.php');
    session_start();
    sessionAuthenticate();

    $db = connect_to_db ( $mysql_host, $mysql_user, $mysql_password, $mysql_database);
    check_location(2, $db);
    
    // Add the first Doctor and Susan at start.
    $char_id_list = get_value_from_users("char_id_list", $db);
    if ($char_id_list == 0 || $char_id_list == '') {
        update_users("char_id_list", "1,15", $db);
    }
    
    $tardis_team_list = get_value_from_users("tardis_team", $db);
    if ($tardis_team_list == 0 || $tardis_team_list == '') {
        update_users("tardis_team", "1,15", $db);
    }
    
    $barbara_collected = check_for_character('barbara', $db);
// $phase = get_user_phase($db);
 //   $d100_roll = rand(1, 100);

?>
<html>
<head>
<title>Dr Who Game - Placeholder</title>

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
<h2>Placeholder</h2>

<?php
    print_tardis_team($db);
?>

</div>
</div>
</body>
</html>
