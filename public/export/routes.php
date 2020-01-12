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
    r.name,
    r.url,
    r.notes,
    r.enabled AS 'Enabled?',
    SUM(l.distance) AS 'Distance',
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS 'Avg Speed'
  FROM
    training_route r LEFT OUTER JOIN training_log l ON r.rid = l.rid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE r.uid = ".db_quote($uid, 'integer')."
  GROUP BY r.rid, r.name, r.url, r.notes, r.enabled
  ORDER BY r.enabled, SUM(l.distance) DESC, r.name";

export_csv($query, $user_unit, 'routes');
?>
