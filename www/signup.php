<?php // signup.php
# Connect to DB

require_once('./config/MySQL.php');
require_once('./config/accesscontrol.php');
require_once('utilities.php');

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);

# $mysql = mysqli_connect($mysql_host, $mysql_user, $mysql_password, $mysql_database);
# if (!mysqli_select_db($mysql, $mysql_database))
#   showerror($mysql);
if ($db -> connect_errno > 0) {
   die('Unable to connect to database [' . $mysql_host . $mysql_user .  $mysql_password . $mysql_database . $db->connect_error . ']');
   }

$uname = mysqlclean($_POST, "loginUsername", 10, $db);
$email = mysqlclean($_POST, "loginEmail", 100, $db);
$pwd = mysqlclean($_POST, "loginPassword", 10, $db);
$cpwd = mysqlclean($_POST, "cloginPassword", 10, $db);

if ($uname=='' or $email=='' or $pwd == '' or $cpwd == '') {
    $message = "One or more required fields were left blank";
    header("Location: signup_form.php?msg=$message");
    exit;
} else if ($pwd != $cpwd) {
    $message = "Your passwords weren't equal.  Please check";
    header("Location: signup_form.php?msg=$message");
    exit;
} else if (VerifyMailAddress($email)) {
    // Check for existing user with the new id
    $sql = "SELECT * FROM users WHERE uname = $uname";
    $result = $db->query($sql);
    if (!$result) {	
        //  IIUC $pass contains both encrypted password and a randomly generated salt.
	// Update: apparently not.  or at least salt is appended to password, not stored separatedy.
	$pass = crypt($pwd);

	$sql = "INSERT INTO users (name,email,password,salt) VALUES ('$uname', '$email', '$pass','kjhasfo2AWU')";
	if (!$db->query($sql)) {
            $message = "Database Error: " . $db->errno . " : " . $db->error;
	    header("Location: signup_form.php?msg=$message");
	    exit;
	} else {	
	    header("Location: login_form.php");
	    exit;
        }
    } else {
      $message = "This user name is already taken";
      header("Location: signup_form.php?msg=$message");
      exit;
    }

} else {
  $message = "This is not a valid Email Address $email";
  header("Location: signup_form.php?msg=$message");
  exit;
}

function VerifyMailAddress($address) 
{
   if(filter_var($address, FILTER_VALIDATE_EMAIL))
      return true;
   else
     return false;
}
?>