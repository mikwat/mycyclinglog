<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

require_once('methods/ride_new.php');
require_once('methods/ride_list.php');
require_once('methods/bike_new.php');
require_once('methods/bike_list.php');
?>
