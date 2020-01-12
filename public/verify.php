<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: index.php?a=".$_GET['a']);
}

include_once("common/common.inc.php");

$GLOBALS['ERROR_MSG'] = [];

if ($_GET && $_GET['a']) {
  $auth_code = $_GET['a'];

  $result =& db_select('training_user', array('uid' => 'integer'), 'auth_code = '.db_quote($auth_code, 'text'));
  $uid = $result->fetch_row()[0];

  if ($uid > 0) {
    db_update('training_user', array('enabled' => 'T'), array('text'), 'uid = '.db_quote($uid, 'integer'));
    $GLOBALS['ERROR_MSG'][] = _('Your email address has been verified. Please login.');
  }
  else {
    $GLOBALS['ERROR_MSG'][] = _('There was an error verifying your email address.');
  }
}
?>
