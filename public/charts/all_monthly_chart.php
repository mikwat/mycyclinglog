<?php
include_once("../charts/jpgraph-4.2.6/src/jpgraph.php");
include_once("../charts/jpgraph-4.2.6/src/jpgraph_bar.php");
require_once("../common/common.inc.php");

session_check();
$user_unit = $_SESSION['user_unit'];

$colors = array('#A3E6BC', '#DD9999');

define('ALL_MONTHLY_CHART', 'all_monthly_chart');

$row_list = cache_get(ALL_MONTHLY_CHART);
if ($row_list === false) {
  $query = "
    SELECT
      MONTH(event_date) AS month,
      YEAR(event_date) AS year,
      SUM(distance) AS distance
    FROM
      training_log
    WHERE is_ride = 'T' AND YEAR(event_date) >= (YEAR(NOW()) - 1)
    GROUP BY YEAR(event_date), MONTH(event_date)
    ORDER BY year, month";
  $result = db_query($query);
  $row_list = $result->fetch_all(MYSQLI_ASSOC);
  cache_save($row_list, ALL_MONTHLY_CHART);
  $result->close();
}
$ydata = array();
foreach ($row_list as $row) {
  $ydata[$row['year']][$row['month']] = $row['distance'];
  $ylabel[] = $row['month'];
}

$graph = new Graph(400, 200, "auto");
$graph->SetScale("textlin");
$graph->SetMarginColor("#DDDDDD");
$graph->xaxis->SetTickLabels($ylabel);
$graph->SetImgFormat("PNG");

$barplot = array();
$index = 0;
foreach ($ydata as $year => $data) {
  unset($month_data);
  $end_month = ($year == date('Y'))? date('n') + 1 : 12;
  for ($month = 1; $month <= $end_month; $month++) {
    $month_data[$month - 1] = ($data[$month] > 0)? $data[$month] : 0;
  }

  $bar = new BarPlot($month_data);
  //$bar->SetLegend($year);
  $bar->SetFillColor($colors[$index++]);

  $barplot[] = $bar;
}

$bargroup = new GroupBarPlot($barplot);
$graph->Add($bargroup);

$graph->img->SetMargin(60,20,20,40);
$graph->title->Set(_('Monthly Distance'));
$graph->xaxis->SetTitle(_('Month'), "middle");
$graph->xaxis->SetTitlemargin(10);
$graph->yaxis->SetTitle(_('Distance')." (".$user_unit.")", "middle");
$graph->yaxis->SetTitlemargin(45);
//$graph->legend->SetShadow(false);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
