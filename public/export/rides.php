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
    l.event_date AS 'Date',
    l.is_ride AS 'Cycling',
    l.time AS 'Time',
    l.distance AS 'Distance',
    l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS 'Avg Speed',
    l.heart_rate AS 'Heart Rate',
    l.max_speed AS 'Max Speed',
    l.avg_cadence AS 'Avg Cadence',
    l.weight AS 'Weight',
    l.calories AS 'Calories',
    l.elevation AS 'Elevation',
    l.notes AS 'Notes',
    r.name AS 'Route Name',
    r.url AS 'Route Link',
    r.notes AS 'Route Notes',
    CONCAT(b.make, ' ', b.model) AS 'Bike'
  FROM
    training_log l LEFT OUTER JOIN
    training_bike b ON l.bid = b.bid LEFT OUTER JOIN
    training_route r ON l.rid = r.rid
  WHERE l.uid = ".db_quote($uid, 'integer')."
  ORDER BY event_date DESC, l.last_modified DESC";

export_csv($query, $user_unit, 'rides');
?>
