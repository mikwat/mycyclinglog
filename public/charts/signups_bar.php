<?php
include_once("../common/common.inc.php");
require_once('sparkline/lib/Sparkline_Bar.php');

$sparkline = new Sparkline_Bar();
$sparkline->SetDebugLevel(DEBUG_NONE);
//$sparkline->SetDebugLevel(DEBUG_ERROR | DEBUG_WARNING | DEBUG_STATS | DEBUG_CALLS, '../log.txt');

if (isset($_GET['b'])) {
  $sparkline->SetColorHtml('background', $_GET['b']);
  $sparkline->SetColorBackground('background');
}

$sparkline->setBarWidth(4);
$sparkline->setBarSpacing(2);

if ($_GET['r']) {
  $where = "AND referrer = ".db_quote($_GET['r'], 'text');
}
else {
  $where = "AND YEAR(signup_date) > YEAR(now()) - 4";
}

$title = "Monthly Signups";
$query = "
  SELECT MONTH(signup_date) AS month, YEAR(signup_date) AS year, COUNT(*) as signups
  FROM training_user
  WHERE enabled = 'T' $where
  GROUP BY MONTH(signup_date), YEAR(signup_date)
  ORDER BY year, month";
$result = db_query($query);
$data = array();
$label = array();
while ($row = $result->fetch_assoc()) {
  $data[] = $row['signups'];
  $label[] = $row['month'];
}
$result->close();

$i = 0;
$sum = 0;
while (list(, $value) = each($data)) {
  if (ereg('^[0-9\.]+$', $value)) {
    $sum += $value;
    $sparkline->SetData($i++, $value);
  }
}

$sparkline->render($result->num_rows);
$sparkline->Output();
?>
