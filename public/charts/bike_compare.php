<?php
include_once("jpgraph-4.2.6/src/jpgraph.php");
include_once("jpgraph-4.2.6/src/jpgraph_bar.php");
include_once("../common/common.inc.php");

session_check();

$query = "
  SELECT
    make,
    COUNT(*) AS count
  FROM training_bike
  GROUP BY make
  ORDER BY count DESC
  LIMIT 5";
$result = db_query($query);
$ydata = array();
while ($row = $result->fetch_assoc()) {
  $ydata[] = $row['count'];
  $ylabel[] = truncate_string(ucfirst($row['make']), 15);
}
$result->close();

$graph = new Graph(500, 200, "auto");
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($ylabel);

$barplot = new BarPlot($ydata);
$barplot->value->Show();
$barplot->value->SetFormat('%d');

$graph->Add($barplot);

$graph->img->SetMargin(40,20,20,40);
$graph->title->Set(_('Bike Tally'));

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Stroke();
?>
