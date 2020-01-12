<?php
include_once("../common/common.inc.php");
include_once("export.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$query = "
  SELECT
    um.title,
    um.body,
    um.entry_date AS 'Entry Date',
    um.`read`,
    ".SQL_NAME." AS 'From Name',
    u.username AS 'From Username',
    u.location AS 'From Location'
  FROM
    training_user_message um INNER JOIN
    training_user u ON um.from_uid = u.uid
  WHERE
    um.to_uid = ".db_quote($uid, 'integer')."
  ORDER BY um.entry_date DESC";

export_csv($query, $user_unit, 'mail-received');
?>
