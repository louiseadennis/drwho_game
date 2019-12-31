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
    
?>
<html>
<head>
<title>Dr Who Game -
<?php
    echo $uname;
?>
 Transitions Test Rig</title>

<link rel="stylesheet" href="./styles/default.css?v=1" type="text/css">
</head>
<body>
<?php
    print_header_info_pages($db);
    ?>


<div class=main style="padding:1em">


<input type="hidden" name="last_action" value="profile_check">
<input type="submit" value="Back to Game" style="font-size:2em">
</form>
</p>
</body>
</head>
</html>
