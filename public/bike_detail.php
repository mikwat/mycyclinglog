<?php
include_once("common/common.inc.php");

if (empty($_GET['bid'])) {
  header("Location: index.php");
  exit();
}

$sid = session_check();
$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];
$bid = $_GET['bid'];

$query = "
  SELECT
    b.bid,
    b.uid,
    b.make,
    b.model,
    b.year,
    b.enabled,
    b.is_default,
    b.iid,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_bike b LEFT OUTER JOIN training_log l ON b.bid = l.bid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE b.bid = ".db_quote($bid, 'integer')."
  GROUP BY b.bid, b.make, b.model, b.year, b.enabled, b.is_default, b.iid";
$result = db_query($query);
$bike_row = $result->fetch_assoc();
$result->close();

$is_owner = false;
$owner_username = "";
if ($sid && $uid == $bike_row['uid']) {
  $is_owner = true;
}
else {
  $types = array('username' => 'text');
  $result = db_select('training_user', $types, 'uid = '.db_quote($bike_row['uid'], 'integer'));
  $owner_username = $result->fetch_assoc()['username'];
  $result->close();
}

if ($is_owner === true && $_POST['form_type'] == "bike_service") {
  $date_i = strtotime($_POST['service_date']);
  if ($date_i === -1) {
    $ERROR_MSG[] = _('Invalid date entered.');
  }
  else {
    $date = date("Y-m-d", $date_i);
  }

  /*
   * Get odometer.
   */
  $odometer = '';
  if (!empty($_POST['odometer'])) {
    $odometer = $_POST['odometer'];
    if (!is_numeric($odometer)) {
      $ERROR_MSG[] = _('Odometer is not a valid number.');
    }
    elseif ($odometer < 0) {
      $ERROR_MSG[] = _('Odometer must be greater than or equal to 0.');
    }
    elseif ($user_unit == "km") {
      /*
       * Always insert odometer in miles.
       */
      $odometer = km_to_m($odometer);
    }
  }

  $notes = $_POST['notes'];

  if (count($ERROR_MSG) == 0) {
    $values = array(
      'bid' => $bid,
      'service_date' => $date,
      'odometer' => $odometer,
      'notes' => $notes
    );
    $types = array(
      'integer',
      'timestamp',
      'float',
      'text'
    );

    $bsid = $_POST['bsid'];
    if (is_numeric($bsid)) {
      db_update('training_bike_service', $values, $types, 'bid = '.db_quote($bid, 'integer').' AND bsid = '.db_quote($bsid, 'integer'));
    }
    else {
      db_insert('training_bike_service', $values, $types);
      $bsid = db_insert_id();
    }

    unset($_POST['service_date']);
    unset($_POST['odometer']);
    unset($_POST['notes']);
  }
}
elseif ($is_owner === true && $_POST['form_type'] == "delete_bike_service") {
  $bsid = $_POST['bsid'];
  db_delete('training_bike_service', 'bsid='.db_quote($bsid, 'integer').' AND bid='.db_quote($bid, 'integer'));
}

$HEADER_TITLE = _('Bike Detail')." : ".export_clean($bike_row['make'].' '.$bike_row['model']);
include_once("common/header.php");
include_once("common/tabs.php");
?>
<script type="text/javascript">
function doDelete(id) {
  var d = document.getElementById('delete_'+id);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2">
      <?php if ($is_owner === false) { ?>
        <a href="/profile/<?php echo urlencode($owner_username) ?>"><?php echo export_clean($owner_username) ?></a>
      <?php } ?>
      <?php echo _('Bike Detail') ?>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo export_clean($bike_row['make'].' '.$bike_row['model']) ?>
    </td>
    <td class="title inr">
      <?php if ($is_owner === true) { ?>
        <a href="/bikes.php?bid=<?php echo $bike_row['bid'] ?>"><img src="images/icon_edit.gif" border="0" alt="<?php echo _('Edit') ?>"/></a>
      <?php } ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="cgray">
      <?php echo unit_format($bike_row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
      <?php echo unit_format($bike_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
    </td>
  </tr>
</table>

<?php include("common/user_recent.php"); ?>

</td>
<td class="cell">

<?php
if ($is_owner === true) {
  $row = array();
  if (!empty($_GET['bsid'])) {
    $bsid = $_GET['bsid'];

    $types = array(
      'service_date' => 'timestamp',
      'odometer' => 'float',
      'notes' => 'text'
    );

    $result = db_select('training_bike_service', $types, 'bsid = '.db_quote($bsid, 'integer').' AND bid = '.db_quote($bid, 'integer'));
    $row = $result->fetch_assoc();
    $result->close();
  } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bsid = $_POST['bsid'];

    $row['service_date'] = $_POST['service_date'];
    $row['odometer'] = $_POST['odometer'];
    $row['notes'] = $_POST['notes'];
  }
  else {
    unset($bsid);
  }

  /*
   * event_date setup
   */
  $dt = $row['service_date'];
  if (empty($dt)) {
    $dt = "NOW";
  }
  ?>
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
  <script type="text/javascript" src="/js/cal.js"></script>
  <script type="text/javascript">
  <?php
  $DOW_NAMES = array(_('Su'), _('Mo'), _('Tu'), _('We'), _('Th'), _('Fr'), _('Sa'));
  $MONTH_NAMES = array(_('January'), _('February'), _('March'), _('April'), _('May'), _('June'), _('July'), _('August'), _('September'), _('October'), _('November'), _('December'));
  ?>
  var MONTHS_LONG = [<?php $first = true; foreach ($MONTH_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
  var WEEKDAYS_SHORT = [<?php $first = true; foreach ($DOW_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
  var eventDateCal;
  function eventDateCalInit() {
    eventDateCal = new YAHOO.widget.Calendar(
      'eventDateCal',
      'event_date_cal',
      {
        pagedate: "<?php echo date("m/Y", strtotime($dt)) ?>",
        selected:"<?php echo date("m/d/Y", strtotime($dt)) ?>",
        START_WEEKDAY:1
      }
    );
    eventDateCal.cfg.setProperty('MONTHS_LONG', MONTHS_LONG);
    eventDateCal.cfg.setProperty('WEEKDAYS_SHORT', WEEKDAYS_SHORT);
    mclCalBuild.apply(eventDateCal);
    eventDateCal.render();
    eventDateCal.selectEvent.subscribe(mclCalOnSelect, eventDateCal, true);
    eventDateCal.selectEvent.subscribe(eventDateCalSelect, eventDateCal, true);
  }
  function eventDateCalSelect(type, args, obj) {
    var dates = args[0];
    var date = dates[0];
    var year = date[0], month = date[1], day = date[2];

    document.forms['bike_service_form'].service_date.value = month + '/' + day + '/' + year;
  }
  YAHOO.util.Event.addListener(window, 'load', eventDateCalInit);
  YAHOO.util.Event.addListener(
    ['selMonth_eventDateCal', 'selDay_eventDateCal', 'selYear_eventDateCal'],
    'change',
    function() { mclCalUpdate.apply(eventDateCal) }
  );
  </script>
  <form name="bike_service_form" action="/bike_detail.php?bid=<?php echo $bid ?>" method="POST">
  <input type="hidden" name="form_type" value="bike_service"/>
  <input type="hidden" name="bsid" value="<?php echo $bsid ?>"/>
  <input type="hidden" name="service_date" value="<?php echo date("m/d/Y", strtotime($dt)) ?>"/>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head"><?php echo (empty($bsid))? _('Add Service') : _('Edit Service') ?></td>
    </tr>
    <tr>
      <td class="title">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox">
          <tr>
            <td width="50"><?php echo _('Date') ?>: *</td>
            <td width="170"><div id="event_date_cal"></div></td>
            <td style="vertical-align: top">
              <div style="padding-bottom: 2px"><select id="selMonth_eventDateCal" name="selMonth_eventDateCal"></select></div>
              <div style="padding-bottom: 2px"><select id="selDay_eventDateCal" name="selDay_eventDateCal"></select></div>
              <div style="padding-bottom: 2px">
                <select id="selYear_eventDateCal" name="selYear_eventDateCal">
                  <?php
                  for ($y = 2005; $y <= date("Y") + 1; $y++) {
                    echo '<option value="'.$y.'">'.$y.'</option>';
                  }
                  ?>
                </select>
              </div>
            </td>
          </tr>
     	  </table>
      </td>
    </tr>
    <tr>
      <td class="title"><?php echo _('Odometer') ?>:</td>
    </tr>
    <tr>
      <td>
        <input type="text" name="odometer" size="25" class="formInput" value="<?php echo stripslashes($row['odometer']) ?>"/>
      </td>
    </tr>
    <tr>
      <td class="title">
        <?php echo _('Notes') ?>:
        <span class="hint"><?php echo _('HTML tags will NOT be rendered. Preface links with "http://".') ?></span>
      </td>
    </tr>
    <tr>
      <td>
        <textarea name="notes" class="formArea"><?php echo stripslashes($row['notes']) ?></textarea>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input type="submit" value="<?php echo (empty($bsid))? _('ADD') : _('UPDATE') ?>" class="btn"/>
        <a href="/bike_detail.php?bid=<?php echo $bid ?>"><?php echo _('Cancel') ?></a>
      </td>
    </tr>
    <?php if ($_POST['form_type'] == "bike_service" && is_error()) { ?>
      <tr><td colspan="2"><?php print_error() ?></td></tr>
    <?php } ?>
  </table>
  </form>
<?php } ?>

<?php
$query = "
  SELECT bs.bsid, bs.bid, bs.service_date, bs.odometer, bs.notes, SUM(l.distance) AS distance
  FROM training_bike_service bs LEFT OUTER JOIN training_log l ON bs.bid = l.bid AND l.event_date >= bs.service_date
  WHERE bs.bid = ".db_quote($bid, 'integer')."
  GROUP BY bs.bsid, bs.bid, bs.service_date, bs.odometer, bs.notes
  ORDER BY bs.service_date DESC";
$result = db_query($query);
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Service Record') ?></td>
  </tr>
</table>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="title"><?php echo _('Date') ?></td>
    <td class="title"><?php echo _('Odometer') ?></td>
    <td class="title"><?php echo _('Distance') ?></td>
    <td></td>
  </tr>
  <?php if ($result->num_rows == 0) { ?>
    <tr><td colspan="3"><?php echo _('Nothing to display.') ?></td></tr>
  <?php }
  while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?php echo date_format_nice($row['service_date']) ?></td>
      <td><?php echo unit_format($row['odometer'], $user_unit) ?></td>
      <td><?php echo unit_format($row['distance'], $user_unit) ?></td>
      <td class="inr">
        <a href="/bike_detail.php?bid=<?php echo $row['bid'] ?>&bsid=<?php echo $row['bsid'] ?>"><img src="images/icon_edit.gif" border="0" alt="Edit"/></a>
        <div id="delete_<?php echo $row['bsid'] ?>" style="display: none">
          <form action="/bike_detail.php?bid=<?php echo $row['bid'] ?>" method="post">
            <input type="hidden" name="form_type" value="delete_bike_service"/>
            <input type="hidden" name="bsid" value="<?php echo $row['bsid'] ?>"/>
            <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
              <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
              <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
            </tr></table>
          </form>
        </div>
        <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['bsid'] ?>)">
          <img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
      </td>
    </tr>
    <tr>
      <td colspan="4" class="plain cgray">
        <?php echo export_clean($row['notes']) ?>
      </td>
    </tr>
  <?php }
  $result->close();
  ?>
</table>

</td>
</tr></table>

<?php include_once("common/footer.php"); ?>
