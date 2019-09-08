<html>
<head>
<title>Log in to Doctor Who Game</title>
<link rel="stylesheet" href="styles/default.css" type="text/css">
</head>
<body>
<div class=main>
<center>
<h1 style="padding-top:10em">Log in to Doctor Who Game</h1>
<form method="POST" action="logincheck.php">
<?php
  if (isset($_GET['msg'])) {
     $msg = $_GET['msg'];
     echo '<p>' . $msg . '</p>';
  } 
?>
<table>
 <tr>
  <td>Enter your username:</td>
  <td><input type="text" size="10" name="loginUsername"></td>
 </tr>
 <tr>
  <td>Enter your password:</td>
  <td><input type ="password" size="10" name="loginPassword"></td>
 </tr>
</table>
<p><input type="submit" value="Log In">
</form>

<p>or <a href=signup_form.php>Sign Up</a></p>
</center>
</div>
</body>
</html>

