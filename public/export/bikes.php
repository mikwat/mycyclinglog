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
    b.make,
    b.model,
    b.year,
    b.enabled AS 'Enabled?',
    b.is_default AS 'Is Default?',
    SUM(l.distance) AS 'Distance',
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS 'Avg Speed'
  FROM
    training_bike b LEFT OUTER JOIN training_log l ON b.bid = l.bid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE b.uid = ".db_quote($uid, 'integer')."
  GROUP BY b.bid, b.make, b.model, b.year, b.enabled, b.is_default";

export_csv($query, $user_unit, 'bikes');
?>
