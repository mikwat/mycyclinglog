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
    g.name,
    g.start_date AS 'Start Date',
    g.end_date AS 'End Date',
    g.distance AS 'Goal Distance',
    g.is_ride AS 'Is Ride?',
    DATEDIFF(g.end_date, NOW()) + 1 AS 'Days More',
    DATEDIFF(g.end_date, g.start_date) + 1 AS 'Goal Days More',
    SUM(l.distance) AS 'Distance',
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS 'Avg Speed'
  FROM
    training_goal g LEFT OUTER JOIN
    training_log l ON g.uid = l.uid AND l.event_date >= g.start_date AND l.event_date <= g.end_date AND (g.is_ride IS NULL OR g.is_ride = l.is_ride) LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE
    g.uid = ".db_quote($uid, 'integer')."
  GROUP BY g.gid, g.name, g.start_date, g.end_date, g.distance
  ORDER BY g.start_date DESC, g.end_date DESC";

export_csv($query, $user_unit, 'goals');
?>
