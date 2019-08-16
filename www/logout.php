<?php

session_start();
session_unset();
session_destroy();

header("Location: ".$game_url."/ login_form.php");
exit();
?>
