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

$is_ride = true;
$is_distance = false;
$attr = $_GET['attr'];
switch ($attr) {
  case "time":
    $title = _('Monthly Time');
    $ytitle = _('Time (hours)');
    break;
  case "rides":
    $title = _('Monthly Rides');
    $ytitle = _('Rides');
    break;
  case "avg_speed":
    $title = _('Monthly Avg Speed');
    $ytitle = _('Avg Speed')." (".$user_unit._('/h').")";
    $is_distance = true;
    break;
  case "weight":
    $title = _('Monthly Avg Weight');
    $ytitle = _('Avg Weight'); $is_ride = false; break; case "calories": $title = _('Monthly Calories');
    $ytitle = _('Calories');
    $is_ride = false;
    break;
  case "elevation":
    $title = _('Monthly Elevation');
    $ytitle = _('Elevation');
    $is_ride = false;
    break;
  default:
    $attr = "distance";
    $title = _('Monthly Distance');
    $ytitle = _('Distance')." (".$user_unit.")";
    $is_distance = true;
    break;
}

$ride_clause = "";
if ($is_ride) {
  $ride_clause = "l.is_ride = 'T' AND";
}

$query = "
  SELECT
    MONTH(l.event_date) AS month,
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
  WHERE l.uid = ".db_quote($uid, 'integer')." AND $ride_clause YEAR(l.event_date) > YEAR(NOW()) - 3
  GROUP BY YEAR(l.event_date), MONTH(l.event_date)
  ORDER BY year, month";
$result = db_query($query);

if ($result->num_rows == 0) {
  exit;
}

$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[$row['year']][$row['month']] = ($is_distance === true)? unit_convert($row[$attr], $user_unit) : $row[$attr];
}
$result->close();

$graph = new Graph($width, $height, "auto");
$graph->graph_theme = null;
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($MONTH_LABELS);

$i = 0;
$barplot = array();
foreach ($ydata as $year => $data) {
  unset($month_data);
  $end_month = ($year == date('Y'))? date('n') + 1 : 12;
  for ($month = 1; $month <= $end_month; $month++) {
    $month_data[$month - 1] = ($data[$month] > 0)? $data[$month] : 0;
  }

  $bar = new BarPlot($month_data);
  $bar->value->Show();
  $bar->SetLegend($year);
  $bar->SetFillColor($COLORS[$i++]);

  //$min = min($month_data);
  //$bar->SetYBase($min);

  $barplot[] = $bar;
}

$bargroup = new GroupBarPlot($barplot);
$graph->Add($bargroup);

$graph->img->SetMargin(60, 20, 20, 80);
$graph->title->Set($title);
$graph->xaxis->SetTitle(_('Month'), "middle");
$graph->yaxis->title->Set($ytitle);
$graph->yaxis->SetTitlemargin(45);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->legend->Pos(0.85, 0.8);

$graph->Stroke();
?>
