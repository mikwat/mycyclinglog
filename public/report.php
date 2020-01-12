<?php
include_once("common/util/calendar.inc.php");
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = _('Report');
include_once("common/header.php");
include_once("common/tabs.php");

if ($_GET['start_date']) {
  $start_date = strtotime($_GET['start_date']);
}
else {
  $start_date = strtotime("-7 days");
}

if ($_GET['end_date']) {
  $end_date = strtotime($_GET['end_date']);
}
else {
  $end_date = time();
}
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td colspan="2">

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tinbox"><tr><td>
  <?php echo _('Data') ?>
  |
  <a href="/report_calendar.php"><?php echo _('Calendar') ?></a>
  |
  <a href="/report_charts.php"><?php echo _('Charts') ?></a>
</td></tr></table>

  </td></tr><tr><td>

<link rel="stylesheet" type="text/css" href="/css/calendar.css"/>
<style type="text/css">
.yui-calendar .calnavleft {
  background: url("/images/ico_prev.gif") no-repeat;
  width: 12px;
  height: 12px;
}
.yui-calendar .calnavright {
  background: url("/images/ico_next.gif") no-repeat;
  width: 12px;
  height: 12px;
}
</style>
<script type="text/javascript" src="/js/calendar-min.js"></script>
<script type="text/javascript">
<?php
$DOW_NAMES = array(_('Su'), _('Mo'), _('Tu'), _('We'), _('Th'), _('Fr'), _('Sa'));
$MONTH_NAMES = array(_('January'), _('February'), _('March'), _('April'), _('May'), _('June'), _('July'), _('August'), _('September'), _('October'), _('November'), _('December'));
?>
var MONTHS_LONG = [<?php $first = true; foreach ($MONTH_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
var WEEKDAYS_SHORT = [<?php $first = true; foreach ($DOW_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
var startDateCal, endDateCal;
function calInit() {
  startDateCal = new YAHOO.widget.Calendar("startDateCal", "start_date_cal", { pagedate: "<?php echo date("m/Y", $start_date) ?>", selected:"<?php echo date("m/d/Y", $start_date) ?>", START_WEEKDAY:<?php echo $_SESSION['week_start'] ?> });
  endDateCal = new YAHOO.widget.Calendar("endDateCal", "end_date_cal", { pagedate: "<?php echo date("m/Y", $end_date) ?>", selected:"<?php echo date("m/d/Y", $end_date) ?>", START_WEEKDAY:<?php echo $_SESSION['week_start'] ?> });

  startDateCal.cfg.setProperty("MONTHS_LONG", MONTHS_LONG);
  startDateCal.cfg.setProperty("WEEKDAYS_SHORT", WEEKDAYS_SHORT);
  endDateCal.cfg.setProperty("MONTHS_LONG", MONTHS_LONG);
  endDateCal.cfg.setProperty("WEEKDAYS_SHORT", WEEKDAYS_SHORT);

  startDateCal.render();
  endDateCal.render();

  startDateCal.selectEvent.subscribe(startDateCalSelect, startDateCal, true);
  endDateCal.selectEvent.subscribe(endDateCalSelect, endDateCal, true);
}
function startDateCalSelect(type, args, obj) {
  var dates = args[0];
  var date = dates[0];
  var year = date[0], month = date[1], day = date[2];

  document.forms['report_form'].start_date.value = month + '/' + day + '/' + year;
}
function endDateCalSelect(type, args, obj) {
  var dates = args[0];
  var date = dates[0];
  var year = date[0], month = date[1], day = date[2];

  document.forms['report_form'].end_date.value = month + '/' + day + '/' + year;
}
YAHOO.util.Event.addListener(window, "load", calInit);
</script>

<form name="report_form" action="/report.php" method="GET">
<input type="hidden" name="action" value="report"/>
<input type="hidden" name="start_date" value="<?php echo date("m/d/Y", $start_date) ?>"/>
<input type="hidden" name="end_date" value="<?php echo date("m/d/Y", $end_date) ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Custom Date Range') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Start Date') ?>: *</td>
  </tr>
  <tr>
    <td>
      <div id="start_date_cal"></div>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('End Date') ?>: *</td>
  </tr>
  <tr>
    <td>
      <div id="end_date_cal"></div>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('SUBMIT') ?>" class="btn"/>
      <a href="/report.php"><?php echo _('All Time') ?></a>
    </td>
  </tr>
</table>
</form>

<?php if ($_GET['action'] == "report") { ?>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="nobor">
    <tr>
      <td style="background-color: #EAEAEA"><?php echo _('Custom date range selected.') ?></td>
    </tr>
  </table>
<?php } ?>

  </td><td class="cell">

<?php
if ($_GET['action'] == "report") {
  $date_i = strtotime($_GET['start_date']);
  if ($date_i === -1) {
    $GLOBALS['ERROR_MSG'][] = _('Invalid start date.');
  }
  else {
    $start_date = date("Y-m-d", $date_i);
  }

  $date_i = strtotime($_GET['end_date']);
  if ($date_i === -1) {
    $GLOBALS['ERROR_MSG'][] = _('Invalid end date.');
  }
  else {
    $end_date = date("Y-m-d", $date_i);
  }
  ?>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Custom Date Range') ?>:
        <?php echo date("m/d/Y", strtotime($start_date))." - ".date("m/d/Y", strtotime($end_date)) ?>
      </td>
    </tr>
  </table>
  <table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
    <tr>
      <td class="title"></td>
      <td class="title"><?php echo _('Time') ?></td>
      <td class="title"><?php echo _('Distance') ?> (<?php echo $user_unit ?>)</td>
      <td class="title"><?php echo _('Avg Speed') ?> (<?php echo $user_unit ?>/h)</td>
      <td class="title"><?php echo _('Ride Count') ?></td>
    </tr>

<?php } else { ?>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('All Time') ?>
    </td>
  </tr>
</table>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="title"><?php echo _('Period') ?></td>
    <td class="title"><?php echo _('Time') ?></td>
    <td class="title"><?php echo _('Distance') ?> (<?php echo $user_unit ?>)</td>
    <td class="title"><?php echo _('Avg Speed') ?> (<?php echo $user_unit ?>/h)</td>
    <td class="title"><?php echo _('Ride Count') ?></td>
  </tr>
  <?php
  $query = "
    SELECT
      MAX(MONTHNAME(a.event_date)) AS month,
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      MONTH(NOW()) = MONTH(a.event_date) AND
      YEAR(NOW()) = YEAR(a.event_date)";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('Month of') ?> <?php echo $row['month'] ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      MAX(MONTHNAME(a.event_date)) AS month,
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      MONTH(SUBDATE(NOW(), INTERVAL 1 MONTH)) = MONTH(a.event_date) AND
      YEAR(SUBDATE(NOW(), INTERVAL 1 MONTH)) = YEAR(a.event_date)";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('Month of') ?> <?php echo $row['month'] ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      WEEK(NOW(),1) = WEEK(a.event_date,1) AND
      a.event_date > SUBDATE(NOW(), INTERVAL 7 DAY)";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('This Week') ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      WEEK(SUBDATE(NOW(), INTERVAL 7 DAY),5) = WEEK(a.event_date,5) AND
      YEAR(SUBDATE(NOW(), INTERVAL 7 DAY)) = YEAR(a.event_date)";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('Previous Week') ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      DATE_SUB(".db_now().", INTERVAL 7 DAY) < a.event_date";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('Previous 7 Days') ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(a.time)) AS time,
      SUM(a.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log a LEFT OUTER JOIN
      training_log c ON a.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE
      a.is_ride = 'T' AND
      a.uid = ".db_quote($uid, 'integer')." AND
      DATE_SUB(".db_now().", INTERVAL 30 DAY) < a.event_date";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td>
      <?php echo _('Previous 30 Days') ?>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td class="title" colspan="5">
      <?php echo _('Totals by Tag') ?>
    </td>
  </tr>
  <?php
  /*
   * Tag totals.
   */
  $query = "
    SELECT
      t.title,
      u.mpg,
      SUM(TIME_TO_SEC(l.time)) AS time,
      SUM(l.distance) AS distance,
      SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_user u INNER JOIN
      training_log l ON u.uid = l.uid INNER JOIN
      training_log_tag lt ON l.lid = lt.lid INNER JOIN
      training_tag t ON lt.tid = t.tid LEFT OUTER JOIN
      training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
    WHERE
      u.uid = ".db_quote($uid, 'integer');
  if ($_GET['action'] == "report") {
    $query .= " AND l.event_date BETWEEN ".db_quote($start_date, 'timestamp')." AND ".db_quote($end_date, 'timestamp');
  }

  $query .= " GROUP BY t.title";
  $result = db_query($query);
  while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td>
        <a href="/tag.php?t=<?php echo export_clean($row['title']) ?>&s=me"><?php echo export_clean($row['title']) ?></a>
      </td>
      <td>
        <?php echo seconds_to_time($row['time']) ?>
      </td>
      <td>
        <?php echo unit_format($row['distance'], $user_unit) ?>
      </td>
      <td>
        <?php echo unit_format($row['avg_speed'], $user_unit) ?>
      </td>
      <td>
        <?php echo number_format($row['rides'], 0) ?>
      </td>
    </tr>
    <?php if ($row['title'] == 'co2') { ?>
      <tr class="green">
        <td colspan="5">
          <?php echo _('CO<sub>2</sub> Emissions') ?>:
          <?php echo get_co2($row['distance'], $row['mpg']) ?> <?php echo _('tons') ?>
        </td>
      </tr>
    <?php } ?>
  <?php }
  $result->close();
  ?>
  <tr>
    <td class="title" colspan="5">
      <?php echo _('Totals by Bike') ?>
    </td>
  </tr>
  <?php
  /*
   * Bike totals.
   */
  $query = "
    SELECT
      b.bid,
      CONCAT(b.make,' ',b.model) AS bike,
      SUM(TIME_TO_SEC(l.time)) AS time,
      SUM(l.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log l INNER JOIN
      training_bike b ON l.bid = b.bid LEFT OUTER JOIN
      training_log c ON l.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE l.uid = ".db_quote($uid, 'integer');
  if ($_GET['action'] == "report") {
    $query .= " AND l.event_date BETWEEN ".db_quote($start_date, 'timestamp')." AND ".db_quote($end_date, 'timestamp');
  }

  $query .= " GROUP BY b.bid, b.make, b.model";
  $result = db_query($query);
  while ($row = $result->fetch_assoc()) {
  ?>
  <tr>
    <td>
      <a href="/bikes.php?bid=<?php echo $row['bid'] ?>"><?php echo export_clean($row['bike']) ?></a>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php }
  $result->close();
  ?>
  <tr>
    <td class="title" colspan="5">
      <?php echo _('Totals by Route') ?>
    </td>
  </tr>
  <?php
  /*
   * Route totals.
   */
  $query = "
    SELECT
      r.rid,
      r.name,
      SUM(TIME_TO_SEC(l.time)) AS time,
      SUM(l.distance) AS distance,
      SUM(c.distance) / (SUM(TIME_TO_SEC(c.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log l INNER JOIN
      training_route r ON l.rid = r.rid LEFT OUTER JOIN
      training_log c ON l.lid = c.lid AND c.time > 0 AND c.distance > 0
    WHERE l.uid = ".db_quote($uid, 'integer');
  if ($_GET['action'] == "report") {
    $query .= " AND l.event_date BETWEEN ".db_quote($start_date, 'timestamp')." AND ".db_quote($end_date, 'timestamp');
  }

  $query .= " GROUP BY r.rid, r.name";
  $result = db_query($query);
  while ($row = $result->fetch_assoc()) {
  ?>
  <tr>
    <td>
      <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['name']) ?></a>
    </td>
    <td>
      <?php echo seconds_to_time($row['time']) ?>
    </td>
    <td>
      <?php echo unit_format($row['distance'], $user_unit) ?>
    </td>
    <td>
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
    </td>
    <td>
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php }
  $result->close();

  /*
   * Grand totals.
   */
  ?>
  <tr>
    <td class="title" colspan="5">
      <?php echo _('Totals') ?>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(l.time)) AS time,
      SUM(l.distance) AS distance,
      SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log l LEFT OUTER JOIN
      training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
    WHERE
    l.is_ride = 'T' AND
    l.uid = ".db_quote($uid, 'integer');
  if ($_GET['action'] == "report") {
    $query .= " AND l.event_date BETWEEN ".db_quote($start_date, 'timestamp')." AND ".db_quote($end_date, 'timestamp');
  }

  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td class="title"><?php echo _('All Cycling') ?></td>
    <td>
      <b><?php echo seconds_to_time($row['time']) ?></b>
    </td>
    <td>
      <b><?php echo unit_format($row['distance'], $user_unit) ?></b>
    </td>
    <td>
      <b><?php echo unit_format($row['avg_speed'], $user_unit) ?></b>
    </td>
    <td>
      <b><?php echo number_format($row['rides'], 0) ?></b>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      SUM(TIME_TO_SEC(l.time)) AS time,
      SUM(l.distance) AS distance,
      SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
      COUNT(*) AS rides
    FROM
      training_log l LEFT OUTER JOIN
      training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
    WHERE l.uid = ".db_quote($uid, 'integer');
  if ($_GET['action'] == "report") {
    $query .= " AND l.event_date BETWEEN ".db_quote($start_date, 'timestamp')." AND ".db_quote($end_date, 'timestamp');
  }

  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr>
    <td class="title">
      <?php echo _('All') ?>
    </td>
    <td>
      <b><?php echo seconds_to_time($row['time']) ?></b>
    </td>
    <td>
      <b><?php echo unit_format($row['distance'], $user_unit) ?></b>
    </td>
    <td>
      <b><?php echo unit_format($row['avg_speed'], $user_unit) ?></b>
    </td>
    <td>
      <b><?php echo number_format($row['rides'], 0) ?></b>
    </td>
  </tr>
</table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
