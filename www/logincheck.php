<?php
require ('./config/accesscontrol.php');
require ('./config/MySQL.php');

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
if ($db -> connect_errno > 0) {
   die('Unable to connect to database [' . $mysql_host . $mysql_user .  $mysql_password . $mysql_database . $db->connect_error . ']');
   }

// Clean the data collected in the form
$loginUsername = mysqlclean($_POST, "loginUsername", 10, $db);
$loginPassword = mysqlclean($_POST, "loginPassword", 10, $db);

session_start();

// Authenticate the User
if (authenticateUser($db, $loginUsername, $loginPassword))
{
  // Register the loginUsername
  $_SESSION["loginUsername"] = $loginUsername;

  // Register the IP address that started this session
  $_SESSION["loginIP"] = $_SERVER["REMOTE_ADDR"];

  // Relocate back to the first page
  header("Location: main.php");
  exit;
} else {
  // The authentication failed
  $message = 
    "Could not connect to 52 Weeks of Primeval Game as '{$loginUsername}'";

  // Relocate back to login page
  header("Location: login_form.php?msg=$message");
  exit;
}
?>