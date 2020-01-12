<?php
include_once("common/common.inc.php");

if (!is_numeric($_GET['uid'])) {
  header("Location: index.php");
  exit();
}
elseif (is_numeric($_GET['lid'])) {
  header("Location: ride_detail.php?uid=".$_GET['uid']."&lid=".$_GET['lid']);
  exit();
}

$uid = $_GET['uid'];
$types = array(
  'username' => 'text'
);
$result = db_select('training_user', $types, 'uid = '.db_quote($uid, 'integer'));
if ($result->num_rows == 0) {
  header("Location: index.php");
  exit();
}
$user_row = $result->fetch_row()[0];
header("Location: profile/".urlencode($user_row['username']));
?>
