<?php
include_once("jpgraph-4.2.6/src/jpgraph.php");
include_once("jpgraph-4.2.6/src/jpgraph_bar.php");

include_once("../common/common.inc.php");

$gid = $_GET['gid'];
session_check();
$user_unit = $_SESSION['user_unit'];

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
  default:
    $attr = "distance";
    $title = _('Monthly Distance');
    $ytitle = _('Distance')." (".$user_unit.")";
    $is_distance = true;
    break;
}

$query = "
  SELECT
    MONTH(a.event_date) AS month,
    YEAR(a.event_date) AS year,
    SUM(TIME_TO_SEC(a.time)) / 3600 AS time,
    SUM(a.distance) AS distance,
    SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
    COUNT(*) AS rides
  FROM
    training_log a LEFT OUTER JOIN
    training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0 INNER JOIN
    training_user u ON a.uid = u.uid INNER JOIN
    training_user_group ug ON u.uid = ug.uid
  WHERE ug.gid = ".db_quote($gid, 'integer')." AND a.is_ride = 'T' AND YEAR(a.event_date) > YEAR(NOW()) - 3
  GROUP BY YEAR(a.event_date), MONTH(a.event_date)
  ORDER BY year, month";
$result = db_query($query);
$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[$row['year']][$row['month']] = ($is_distance)? unit_convert($row[$attr], $user_unit) : $row[$attr];
}
$result->close();

$graph = new Graph(500, 200, "auto");
//$graph->graph_theme = null;
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($MONTH_LABELS);

$i = 0;
$barplot = array();
foreach ($ydata as $year => $data) {
  unset($month_data);
  $end_month = ($year == date('Y'))? date('n') + 1 : 12;
  for ($month = 1; $month <= $end_month; $month++) {
    $month_data[$month - 1] = (isset($data[$month]) && $data[$month] > 0)? $data[$month] : 0;
  }

  $bar = new BarPlot($month_data);
  $bar->value->Show();
  $bar->SetLegend($year);
  $bar->SetFillColor($COLORS[$i++]);

  $barplot[] = $bar;
}

$bargroup = new GroupBarPlot($barplot);
$graph->Add($bargroup);

$graph->img->SetMargin(60, 75, 20, 40);
$graph->title->Set($title);
$graph->xaxis->SetTitle(_('Month'), "middle");
$graph->xaxis->SetTitlemargin(10);
$graph->yaxis->title->Set($ytitle);
$graph->yaxis->SetTitlemargin(45);
$graph->legend->SetShadow(false);
$graph->legend->Pos(0.01, 0.5, "right", "center");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
