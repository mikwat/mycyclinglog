<?php
include_once("common/util/calendar.inc.php");
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = "Report : Calendar";
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td colspan="2">

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tinbox"><tr><td>
  <a href="/report.php"><?php echo _('Data') ?></a>
  |
  <?php echo _('Calendar') ?>
  |
  <a href="/report_charts.php"><?php echo _('Charts') ?></a>
</td></tr></table>
<?php
/*
 * Calendar
 */
if (is_numeric($_GET['m']) && is_numeric($_GET['y'])) {
  $cur_month = $_GET['m'];
  $cur_year = $_GET['y'];
}
else {
  $cur_month = date("n");
  $cur_year = date("Y");
}

if ($cur_month == 1) {
  $prev_month = 12;
  $prev_year = $cur_year - 1;
}
else {
  $prev_month = $cur_month - 1;
  $prev_year = $cur_year;
}

if ($prev_month == 1) {
  $pprev_month = 12;
  $pprev_year = $prev_year - 1;
}
else {
  $pprev_month = $prev_month - 1;
  $pprev_year = $prev_year;
}

$cal_start_date = $pprev_year."-".$pprev_month."-1";
$cal_end_date = $cur_year."-".$cur_month."-".date("j", mktime(0, 0, 0, $cur_month + 1, 0, $cur_year));
$query = "
  SELECT
    l.event_date,
    l.distance,
    l.time,
    l.is_ride
  FROM
    training_log l
  WHERE l.uid = ".db_quote($uid, 'integer')."
    AND l.event_date BETWEEN ".db_quote($cal_start_date, 'timestamp')." AND ".db_quote($cal_end_date, 'timestamp')."
  ORDER BY event_date DESC, last_modified DESC";
$result = db_query($query);

$dist_map = array();
$ride_map = array();
while ($row = $result->fetch_assoc()) {
  $date = $row['event_date'];

  /*
   * Populate dist_map
   */
  if (!isset($dist_map[$date])) {
    $dist_map[$date] = 0;
  }

  $dist_map[$date] += unit_format($row['distance'], $user_unit);

  /*
   * Populate ride_map
   */
  if (!isset($ride_map[$date])) {
    $ride_map[$date] = "";
  }

  $ride_map[$date] .= unit_format($row['distance'], $user_unit)." ".$user_unit." ";
  if ($row['distance'] > 0 && $row['time'] > 0) {
    $ride_map[$date] .= _('in')." ";
  }

  if ($row['time'] > 0) {
    $ride_map[$date] .= $row['time']." "._('hours').". ";
  }

  if ($row['is_ride'] == "T") {
    $ride_map[$date] .= " ["._('Cycling')."]<br/>";
  }
}
$result->close();

$query = "
  SELECT
    event_date AS date,
    COUNT(*) AS rides
  FROM
    training_log
  WHERE
    uid = ".db_quote($uid, 'integer')." AND
    event_date BETWEEN ".db_quote($cal_start_date, 'timestamp')." AND ".db_quote($cal_end_date, 'timestamp')."
  GROUP BY event_date";
$result = db_query($query);
while (list($date, $rides) = $result->fetch_row()) {
  if ($rides > 0 && $dist_map[$date] == 0) {
    $dist_map[$date] = -1;
  }
}
$result->close();

$cal = new Calendar("/report_calendar.php");
$cal->setDistanceMap($dist_map);
$cal->setRideMap($ride_map);
$cal->setStartDay($_SESSION['week_start']);
?>
<table cellspacing="0" cellpadding="0" border="0" style="padding-left: 2px">
  <tr>
    <td width="150">
      <?= $cal->getMonthView($pprev_month, $pprev_year, -1); ?>
    </td>
    <td width="2"><img src="/images/spacer.gif" width="2" height="2"/></td>
    <td width="150">
      <?= $cal->getMonthView($prev_month, $prev_year, 0); ?>
    </td>
    <td width="2"><img src="/images/spacer.gif" width="2" height="2"/></td>
    <td width="150">
      <?= $cal->getMonthView($cur_month, $cur_year, 1); ?>
    </td>
    <td width="2"><img src="/images/spacer.gif" width="2" height="2"/></td>
    <td>

<table cellspacing="0" cellpadding="0" border="0" class="calbox">
  <tr>
    <td colspan="2" class="title"><?php echo _('Legend') ?></td>
  </tr>
  <tr>
    <td colspan="2">
      (<?php echo ($user_unit == 'km')? _('Kilometers') : _('Miles') ?>)
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr><td class="bg10">101+</td></tr>
  <tr><td class="bg9">91 - 100</td></tr>
  <tr><td class="bg8">81 - 90</td></tr>
  <tr><td class="bg7">71 - 80</td></tr>
  <tr><td class="bg6">61 - 70</td></tr>
  <tr><td class="bg5">51 - 60</td></tr>
</table>
    </td><td width="50%" valign="top">
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr><td class="bg4">41 - 50</td></tr>
  <tr><td class="bg3">31 - 40</td></tr>
  <tr><td class="bg2">21 - 30</td></tr>
  <tr><td class="bg1">11 - 20</td></tr>
  <tr><td class="bg0">1 - 10</td></tr>
  <tr><td class="bg">today</td></tr>
</table>
    </td>
  </tr>
  <tr><td class="bg01" colspan="2"><?php echo _('Unknown') ?></td></tr>
</table>

    </td>
  </tr>
</table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
