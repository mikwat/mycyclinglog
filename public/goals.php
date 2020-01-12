<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$GLOBALS['ERROR_MSG'] = [];

if ($_POST['form_type'] == "goal" && check_form_key($_POST['form_key'])) {
  $gid = $_POST['gid'];
  $name = $_POST['name'];
  if (empty($name)) {
    $GLOBALS['ERROR_MSG'][] = _('Name is required.');
  }

  $is_ride = null;
  if ($_POST['is_ride'] == 'T' || $_POST['is_ride'] == 'F') {
    $is_ride = $_POST['is_ride'];
  }

  $start_date = null;
  $start_i = strtotime($_POST['start_date']);
  if ($start_i === -1) {
    $GLOBALS['ERROR_MSG'][] = _('Invalid date entered.');
  }
  else {
    $start_date = date("Y-m-d", $start_i);
  }

  $end_date = null;
  $end_i = strtotime($_POST['end_date']);
  if ($end_i === -1) {
    $GLOBALS['ERROR_MSG'][] = _('Invalid date entered.');
  }
  else {
    $end_date = date("Y-m-d", $end_i);
  }

  if ($start_i > $end_i) {
    $GLOBALS['ERROR_MSG'][] = _('Start Date must be before End Date.');
  }

  /*
   * Get distance.
   */
  $distance = $_POST['distance'];
  if (!is_numeric($distance)) {
    $GLOBALS['ERROR_MSG'][] = _('Distance is not a valid number.');
  }
  elseif ($distance <= 0) {
    $GLOBALS['ERROR_MSG'][] = _('Distance must be greater than 0.');
  }
  elseif ($user_unit == "km") {
    /*
     * Always insert distance in miles.
     */
    $distance = km_to_m($distance);
  }

  if (count($GLOBALS['ERROR_MSG']) == 0) {
    if (empty($gid)) {
      db_insert('training_goal', array('uid' => $uid, 'name' => $name, 'start_date' => $start_date, 'end_date' => $end_date, 'distance' => $distance, 'is_ride' => $is_ride),
        array('integer', 'text', 'timestamp', 'timestamp', 'float', 'text'));

      $GLOBALS['ERROR_MSG'][] = _('New goal created.');
    }
    else {
      db_update('training_goal', array('name' => $name, 'start_date' => $start_date, 'end_date' => $end_date, 'distance' => $distance, 'is_ride' => $is_ride),
        array('text', 'timestamp', 'timestamp', 'float', 'text'), 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));

      $GLOBALS['ERROR_MSG'][] = _('Changes saved.');
    }

    unset($_POST['gid']);
    unset($_POST['name']);
    unset($_POST['start_date']);
    unset($_POST['end_date']);
    unset($_POST['distance']);
    unset($_POST['is_ride']);
  }
}
elseif ($_POST['form_type'] == "delete_goal") {
  $gid = $_POST['gid'];
  db_delete('training_goal', 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));

  $GLOBALS['ERROR_MSG'][] = _('Goal removed.');

  unset($_POST['gid']);
}

$HEADER_TITLE = _('Goals');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<script type="text/javascript">
function doDelete(id) {
  var d = document.getElementById('delete_'+id);
  overlib(d.innerHTML, STICKY, WIDTH, 125, LEFT);
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">
<?php
$query = "
  SELECT
    g.gid,
    g.name,
    g.start_date,
    g.end_date,
    g.distance AS goal_distance,
    g.is_ride,
    DATEDIFF(g.end_date, NOW()) + 1 AS days_more,
    DATEDIFF(g.end_date, g.start_date) + 1 AS goal_days_more,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_goal g LEFT OUTER JOIN
    training_log l ON g.uid = l.uid AND l.event_date >= g.start_date AND l.event_date <= g.end_date AND (g.is_ride IS NULL OR g.is_ride = l.is_ride) LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE
    g.uid = ".db_quote($uid, 'integer')."
  GROUP BY g.gid, g.name, g.start_date, g.end_date, g.distance
  ORDER BY g.start_date DESC, g.end_date DESC";
$result = db_query($query);
$goals_current = array();
$goals_past = array();
while ($row = $result->fetch_assoc()) {
  if (strtotime($row['end_date']) >= time()) {
    $goals_current[] = $row;
  }
  else {
    $goals_past[] = $row;
  }
}
$result->close();
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('My Goals') ?></td>
  </tr>
</table>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="cgray">
      <?php echo _('Click the <img src="images/icon_edit.gif" border="0" alt="Edit" align="absmiddle"/> icon next to a goal to edit it.') ?>
    </td>
  </tr>
  <?php foreach (array('current' => $goals_current, 'past' => $goals_past) as $key => $goals) { ?>
  <tr><td class="title" colspan="3"><?php echo ($key == 'current') ? _('Current Goals') : _('Past Goals') ?></td></tr>
  <?php if (empty($goals)) { ?>
    <tr><td colspan="3"><?php echo _('No goals defined.') ?></td></tr>
  <?php }
  foreach ($goals as $row) { ?>
    <?php if ($row['days_more'] > 0 && $row['days_more'] <= $row['goal_days_more']) { ?>
      <tr class="highlight">
    <?php } else { ?>
      <tr>
    <?php } ?>
      <td>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox">
          <tr>
            <td class="head">
              <a href="/goal_detail.php?gid=<?php echo $row['gid'] ?>"><?php echo export_clean($row['name']) ?></a>
            </td>
            <td class="inr">
              <a href="/goals.php?gid=<?php echo $row['gid'] ?>"><img src="images/icon_edit.gif" border="0" alt="Edit"/></a>
              <div id="delete_<?php echo $row['gid'] ?>" style="display: none">
                <form action="/goals.php" method="post">
                  <input type="hidden" name="form_type" value="delete_goal"/>
                  <input type="hidden" name="gid" value="<?php echo $row['gid'] ?>"/>
                  <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                    <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                    <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                  </tr></table>
                </form>
              </div>
              <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['gid'] ?>)">
                <img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="cgray">
              <?php echo unit_format($row['goal_distance'], $user_unit)." ".$user_unit ?>
              <?php echo sprintf(_('between %s and %s'), date_format_nice($row['start_date']), date_format_nice($row['end_date'])) ?>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="cgray">
              <?php
              if ($row['is_ride'] == "T") {
                echo " ["._('Cycling')."]";
              }
              elseif ($row['is_ride'] == "F") {
                echo " ["._('Other')."]";
              }
              else {
                echo " ["._('All')."]";
              }
              ?>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="title">
              <?php echo _('Progress') ?>:
              <?php echo ($row['goal_distance'] > 0)? number_format(100 * $row['distance'] / $row['goal_distance'], 0) : "0" ?>%
            </td>
          </tr>
          <tr>
            <td colspan="2" class="cgray">
              <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
              <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
            </td>
          </tr>
          <?php if ($row['days_more'] > 0) { ?>
            <tr>
              <td colspan="2">
                <ul>
                  <?php
                  $distance_more = $row['goal_distance'] - $row['distance'];
                  if ($distance_more < 0) {
                    $distance_more = 0;
                  }
                  echo "<li>".sprintf(_('A distance of <b>%s</b> remains.'), unit_format($distance_more, $user_unit)." ".$user_unit)."</li>";

                  if ($row['avg_speed'] > 0) {
                    $hours_more = $distance_more / $row['avg_speed'];
                    echo "<li>".sprintf(_('At current average speed, <b>%s hours</b> of ride time required.'), number_format($hours_more, 1))."</li>";
                  }

                  $days_more = ($row['days_more'] > $row['goal_days_more'])? $row['goal_days_more'] : $row['days_more'];
                  if ($days_more > 0) {
                    $daily_distance_more = $distance_more / $days_more;
                    echo "<li>".sprintf(_('An average daily distance of <b>%s</b> required.'), unit_format($daily_distance_more, $user_unit)." ".$user_unit)."</li>";
                  }

                  // goal/(end date - start date + 1) * (today - end date + 1)
                  if ($row['goal_days_more'] > 0) {
                    $target_distance = ($row['goal_distance'] / $row['goal_days_more']) * ($row['goal_days_more'] - $row['days_more'] + 1);
                    echo "<li>".sprintf(_('You should have completed a distance of <b>%s</b> by today.'), unit_format($target_distance, $user_unit)." ".$user_unit)."</li>";
                  }
                  ?>
                </ul>
              </td>
            </tr>
          <?php } ?>
        </table>
      </td>
    </tr>
  <?php } ?>
  <?php } ?>
</table>

</td>
<td width="50%" class="cell">

<?php
if (!empty($_GET['gid'])) {
  $gid = $_GET['gid'];

  $result = db_select('training_goal', array('gid' => 'integer', 'name' => 'text', 'start_date' => 'timestamp', 'end_date' => 'timestamp', 'distance' => 'float', 'is_ride' => 'text'),
    'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  $row = $result->fetch_assoc();
  $result->close();
}
else {
  $row = array();
  $row['gid'] = $_POST['gid'];
  $row['name'] = $_POST['name'];
  $row['start_date'] = $_POST['start_date'];
  $row['end_date'] = $_POST['end_date'];
  $row['distance'] = $_POST['distance'];
  $row['is_ride'] = $_POST['is_ride'];
}

if (!empty($row['start_date'])) {
  $start_date = strtotime($row['start_date']);
}
else {
  $start_date = time();
}

if (!empty($row['end_date'])) {
  $end_date = strtotime($row['end_date']);
}
else {
  $end_date = strtotime("+1 month");
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
<script type="text/javascript">
<?php
$DOW_NAMES = array(_('Su'), _('Mo'), _('Tu'), _('We'), _('Th'), _('Fr'), _('Sa'));
$MONTH_NAMES = array(_('January'), _('February'), _('March'), _('April'), _('May'), _('June'), _('July'), _('August'), _('September'), _('October'), _('November'), _('December'));
?>
var MONTHS_LONG = [<?php $first = true; foreach ($MONTH_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
var WEEKDAYS_SHORT = [<?php $first = true; foreach ($DOW_NAMES as $m) { if (!$first) { echo ","; } echo '"'.$m.'"'; $first = false; } ?>];
var startDateCal, endDateCal;
function calInit() {
  startDateCal = new YAHOO.widget.Calendar("startDateCal", "start_date_cal",
    {
      pagedate: "<?php echo date("m/Y", $start_date) ?>",
      selected: "<?php echo date("m/d/Y", $start_date) ?>",
      START_WEEKDAY: 1
    });
  endDateCal = new YAHOO.widget.Calendar("endDateCal", "end_date_cal",
    {
      pagedate: "<?php echo date("m/Y", $end_date) ?>",
      selected: "<?php echo date("m/d/Y", $end_date) ?>",
      START_WEEKDAY: 1
    });

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

  document.forms['goal_form'].start_date.value = month + '/' + day + '/' + year;
}
function endDateCalSelect(type, args, obj) {
  var dates = args[0];
  var date = dates[0];
  var year = date[0], month = date[1], day = date[2];

  document.forms['goal_form'].end_date.value = month + '/' + day + '/' + year;
}
YAHOO.util.Event.addListener(window, "load", calInit);
</script>

<form name="goal_form" action="/goals.php" method="POST">
<input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
<input type="hidden" name="form_type" value="goal"/>
<input type="hidden" name="gid" value="<?php echo $row['gid'] ?>"/>
<input type="hidden" name="start_date" value="<?php echo date("m/d/Y", $start_date) ?>"/>
<input type="hidden" name="end_date" value="<?php echo date("m/d/Y", $end_date) ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo (empty($_GET['gid']))? _('Add Goal') : _('Edit Goal') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="name" size="40" class="formInput" value="<?php echo stripslashes($row['name']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Distance') ?>: *
      <span class="hint"><?php echo _('Your default unit can be changed on the <a href="/account.php" class="t">Account &raquo;</a> page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0"><tr>
        <td>
          <input type="text" name="distance" size="5" class="formInput" value="<?php if ($row['distance'] > 0) { echo unit_format($row['distance'], $user_unit); } ?>"/>
        </td>
        <td class="tah10">
          <?php echo ($user_unit == "km")? _('Kilometers') : _('Miles') ?>
        </td>
        <td>
          <img src="/images/spacer.gif" width="4" height="1"/>
        </td>
      </tr></table>
    </td>
  </tr>
  <tr>
  	<td class="title">
  	  <?php echo _('Type') ?>: *
  	</td>
  </tr>
  <tr>
    <td>
      <?php echo _('All') ?>:
      <input type="radio" name="is_ride" value="" <?php if (empty($row['is_ride'])) echo "checked"; ?> />
      <?php echo _('Cycling') ?>:
      <input type="radio" name="is_ride" value="T" <?php if ($row['is_ride'] == "T") echo "checked"; ?> />
      <?php echo _('Other') ?>:
      <input type="radio" name="is_ride" value="F" <?php if ($row['is_ride'] == "F") echo "checked"; ?> />
    </td>
  </tr>
  <tr>
    <td class="title">
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox"><tr>
        <td width="25%"><?php echo _('Start Date') ?>: *</td>
        <td class="inr"><div id="start_date_cal"></div></td>
      </tr></table>
    </td>
  </tr>
  <tr>
    <td class="title">
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox"><tr>
        <td width="25%"><?php echo _('End Date') ?>: *</td>
        <td class="inr"><div id="end_date_cal"></div></td>
      </tr></table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo (empty($row['gid']))? _('ADD') : _('UPDATE') ?>" class="btn"/>
      <a href="/goals.php"><?php echo _('Cancel') ?></a>
    </td>
  </tr>
  <?php if ($_POST['form_type'] == "goal" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
