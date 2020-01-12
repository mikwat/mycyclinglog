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
  default:
    $attr = "distance";
    $title = _('Yearly Distance');
    $ytitle = _('Distance')." (".$user_unit.")";
    $is_distance = true;
    break;
}

$query = "
  SELECT
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
  WHERE ug.gid = ".db_quote($gid, 'integer')." AND a.is_ride = 'T'
  GROUP BY YEAR(a.event_date)
  ORDER BY year";
$result = db_query($query);
$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[] = ($is_distance)? unit_convert($row[$attr], $user_unit) : $row[$attr];
  $ylabel[] = $row['year'];
}
$result->close();

$graph = new Graph(500, 200, "auto");
//$graph->graph_theme = null;
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($ylabel);

$barplot = new BarPlot($ydata);
$barplot->value->Show();
//$barplot->SetFillColor($COLORS);

$graph->Add($barplot);

$graph->img->SetMargin(60,20,20,40);
$graph->title->Set($title);
$graph->xaxis->SetTitle(_('Year'), "middle");
$graph->xaxis->SetTitlemargin(10);
$graph->yaxis->title->Set($ytitle);
$graph->yaxis->SetTitlemargin(45);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
