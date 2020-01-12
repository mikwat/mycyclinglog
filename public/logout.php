<?php
session_start();
session_unset();
session_destroy();

setcookie("mcl_u", "", time() - 60*60, '/');
setcookie("mcl_p", "", time() - 60*60, '/');
setcookie("mcl_r", "", time() - 60*60, '/');

header("Location: index.php");
?>
