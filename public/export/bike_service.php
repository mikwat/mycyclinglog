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
    bs.service_date AS 'Service Date',
    bs.odometer,
    bs.notes
  FROM
    training_bike_service bs INNER JOIN
    training_bike b ON bs.bid = b.bid
  WHERE b.uid = ".db_quote($uid, 'integer')."
  ORDER BY b.make, b.model, b.year, bs.service_date DESC";

export_csv($query, $user_unit, 'bike-service');
?>
