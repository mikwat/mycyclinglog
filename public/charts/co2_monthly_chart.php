<?php
include_once("../charts/jpgraph-4.2.6/src/jpgraph.php");
include_once("../charts/jpgraph-4.2.6/src/jpgraph_bar.php");
require_once("../common/common.inc.php");

session_check();

$query = "
  SELECT
    MONTH(l.event_date) AS month,
    YEAR(l.event_date) AS year,
    AVG(u.mpg) AS mpg,
    SUM(l.distance) AS distance
  FROM
    training_user u INNER JOIN
    training_log l ON u.uid = l.uid INNER JOIN
    training_log_tag lt ON l.lid = lt.lid INNER JOIN
    training_tag t ON lt.tid = t.tid AND LOWER(t.title) = 'co2'
  WHERE l.is_ride = 'T' AND YEAR(l.event_date) > YEAR(NOW()) - 3
  GROUP BY YEAR(l.event_date), MONTH(l.event_date)
  ORDER BY year, month";
$result = db_query($query);
$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[$row['year']][$row['month']] = get_co2($row['distance'], $row['mpg']);
  $ylabel[] = $row['month'];
}
$result->close();

$graph = new Graph(400, 200, "auto");
$graph->SetScale("textlin");
$graph->SetMarginColor("#A3E6BC");
$graph->xaxis->SetTickLabels($ylabel);
$graph->SetImgFormat("PNG");

$barplot = array();
foreach ($ydata as $year => $data) {
  unset($month_data);
  $end_month = ($year == date('Y'))? date('n') + 1 : 12;
  for ($month = 1; $month <= $end_month; $month++) {
    $month_data[$month - 1] = ($data[$month] > 0)? $data[$month] : 0;
  }

  $bar = new BarPlot($month_data);
  $bar->SetLegend($year);
  $bar->SetFillColor("#08895B");

  $barplot[] = $bar;
}

$bargroup = new GroupBarPlot($barplot);
$graph->Add($bargroup);

$graph->img->SetMargin(60,20,20,40);
$graph->title->Set(_('Monthly Carbon Emission Savings'));
$graph->xaxis->SetTitle(_('Month'), "middle");
$graph->xaxis->SetTitlemargin(10);
$graph->yaxis->SetTitle(_('CO2 (tons)'), "middle");
$graph->yaxis->SetTitlemargin(45);
$graph->legend->SetShadow(false);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
