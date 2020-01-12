<?php
// Run every day.
require_once(__DIR__."/../public/common/common.inc.php");

error_log("Start clearing cookie...");

$days = getenv('DAYS') ?: 14;
db_update('training_user', array('ext_cookie' => null), array('text'),
  'last_login < DATE_SUB('.db_now().', INTERVAL '.db_quote($days, 'integer').' DAY) AND ext_cookie IS NOT NULL');

if (count($GLOBALS['ERROR_MSG']) > 0) {
  error_log("Error clearing cookies: ".$GLOBALS['ERROR_MSG']);
} else {
  error_log("Done clearing cookie.");
}
?>
