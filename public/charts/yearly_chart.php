<?php
include_once("jpgraph-4.2.6/src/jpgraph.php");
include_once("jpgraph-4.2.6/src/jpgraph_bar.php");
include_once("../common/common.inc.php");

session_check();

$width = ($_GET['width'] > 0)? $_GET['width'] : 700;
$height = ($_GET['height'] > 0)? $_GET['height'] : 300;
$uid = $_GET['uid'];

$result = db_select('training_user', array('unit' => 'text'), 'uid = '.db_quote($uid, 'integer'));
$user_unit = $result->fetch_row()[0];
$result->close();

$is_ride = true;
$is_distance = false;
$attr = $_GET['attr'];
switch ($attr) {
  case "time":
    $title = _('Yearly Time');
    $ytitle = _('Time (hours)');
    break;
  case "rides":
    $title = _('Yearly Rides');
    $ytitle = _('Rides');
    break;
  case "avg_speed":
    $title = _('Yearly Avg Speed');
    $ytitle = _('Avg Speed')." (".$user_unit._('/h').")";
    $is_distance = true;
    break;
  case "weight":
    $title = _('Yearly Avg Weight');
    $ytitle = _('Avg Weight');
    $is_ride = false;
    break;
  case "calories":
    $title = _('Yearly Calories');
    $ytitle = _('Calories');
    $is_ride = false;
    break;
  case "elevation":
    $title = _('Yearly Elevation');
    $ytitle = _('Elevation');
    $is_ride = false;
    break;
  default:
    $attr = "distance";
    $title = _('Yearly Distance');
    $ytitle = _('Distance')." (".$user_unit.")";
    $is_distance = true;
    break;
}

$ride_clause = "";
if ($is_ride) {
  $ride_clause = "AND l.is_ride = 'T'";
}

$query = "
  SELECT
    YEAR(l.event_date) AS year,
    SUM(TIME_TO_SEC(l.time)) / 3600 AS time,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
    COUNT(*) AS rides,
    AVG(l3.weight) AS weight,
    SUM(l.calories) AS calories,
    SUM(l.elevation) AS elevation
  FROM
    training_log l LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0 LEFT OUTER JOIN
    training_log l3 ON l.lid = l3.lid AND l3.weight > 0
  WHERE l.uid = ".db_quote($uid, 'integer')." $ride_clause
  GROUP BY YEAR(l.event_date)
  ORDER BY year";
$result = db_query($query);

if ($result->num_rows == 0) {
  $result->close();
  exit;
}

$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[] = ($is_distance)? unit_convert($row[$attr], $user_unit) : $row[$attr];
  $ylabel[] = $row['year'];
}
$result->close();

$graph = new Graph($width, $height, "auto");    
$graph->graph_theme = null;
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($ylabel);

$barplot = new BarPlot($ydata);
$barplot->value->Show();
//$barplot->SetFillColor($COLORS);

$graph->Add($barplot);

$graph->img->SetMargin(60,20,20,40);
$graph->title->Set($title);
$graph->xaxis->title->Set(_('Year'));
$graph->yaxis->title->Set($ytitle);
$graph->yaxis->SetTitlemargin(45);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
