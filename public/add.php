<?php
include_once("common/util/calendar.inc.php");
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$GLOBALS['ERROR_MSG'] = [];
$show_update = false;

if ($_POST['r'] == 2 && check_form_key($_POST['form_key'])) {
  $date_i = strtotime($_POST['event_date']);
  if ($date_i === -1) {
    $GLOBALS['ERROR_MSG'][] = _('Invalid date entered.');
  }
  else {
    $date = date("Y-m-d", $date_i);
  }

  $is_ride = $_POST['is_ride'];
  if (empty($is_ride)) {
    $GLOBALS['ERROR_MSG'][] = _('Type is required.');
  }

  /*
   * Get time.
   */
  $h = empty($_POST['h']) ? "00" : $_POST['h'];
  $m = empty($_POST['m']) ? "00" : $_POST['m'];
  $s = empty($_POST['s']) ? "00" : $_POST['s'];

  if ($h < 0) {
    $GLOBALS['ERROR_MSG'][] = _('Hours must be greater than or equal to 0.');
  }

  if ($m > 59) {
    $GLOBALS['ERROR_MSG'][] = _('Minutes must be less than 60.');
  }
  elseif ($m < 0) {
    $GLOBALS['ERROR_MSG'][] = _('Minutes must be greater than or equal to 0.');
  }

  if ($s > 59) {
    $GLOBALS['ERROR_MSG'][] = _('Seconds must be less than 60.');
  }
  elseif ($s < 0) {
    $GLOBALS['ERROR_MSG'][] = _('Seconds must be greater than or equal to 0.');
  }

  if ($h != "00" || $m != "00" || $s != "00") {
    $time = $h.":".$m.":".$s;
  }
  else {
    $time = null;
  }

  /*
   * Get distance.
   */
  $distance = '';
  if (!empty($_POST['distance'])) {
    $distance = $_POST['distance'];
    if (!is_numeric($distance)) {
      $GLOBALS['ERROR_MSG'][] = _('Distance is not a valid number.');
    }
    elseif ($distance < 0) {
      $GLOBALS['ERROR_MSG'][] = _('Distance must be greater than or equal to 0.');
    }
    elseif ($user_unit == "km") {
      /*
       * Always insert distance in miles.
       */
      $distance = km_to_m($distance);
    }
  }

  $notes = $_POST['notes'];
  $heart_rate = $_POST['heart_rate'];

  $max_speed = null;
  if (!empty($_POST['max_speed'])) {
    $max_speed = $_POST['max_speed'];
    if (!is_numeric($max_speed)) {
      $GLOBALS['ERROR_MSG'][] = _('Maximum speed is not a valid number.');
    }
    elseif ($max_speed < 0) {
      $GLOBALS['ERROR_MSG'][] = _('Maximum speed must be greater than or equal to 0.');
    }
    elseif ($user_unit == "km") {
      $max_speed = km_to_m($max_speed);
    }
  }

  $avg_cadence = null;
  if (is_numeric($_POST['avg_cadence'])) {
    $avg_cadence = $_POST['avg_cadence'];
  }

  $weight = null;
  if (is_numeric($_POST['weight'])) {
    $weight = $_POST['weight'];
  }

  $calories = null;
  if (is_numeric($_POST['calories'])) {
    $calories = $_POST['calories'];
  }

  $elevation = null;
  if (is_numeric($_POST['elevation'])) {
    $elevation = $_POST['elevation'];
  }

  $tag_str = $_POST['tags'];
  $tags = null;
  if (!empty ($tag_str)) {
    $tags = parse_tag_str($tag_str);
  }

  $rid = $_POST['rid'];
  $bid = $_POST['bid'];
  if (count($GLOBALS['ERROR_MSG']) == 0) {
    $values = array(
      'uid' => $uid,
      'bid' => $bid,
      'rid' => $rid,
      'event_date' => $date,
      'is_ride' => $is_ride,
      'time' => $time,
      'distance' => $distance,
      'notes' => $notes,
      'max_speed' => $max_speed,
      'heart_rate' => $heart_rate,
      'avg_cadence' => $avg_cadence,
      'weight' => $weight,
      'calories' => $calories,
      'elevation' => $elevation
    );
    $types = array(
      'integer',
      'integer',
      'integer',
      'timestamp',
      'text',
      'time',
      'float',
      'text',
      'float',
      'text',
      'float',
      'float',
      'float',
      'float'
    );

    $lid = $_POST['lid'];
    if (is_numeric($lid)) {
      db_update('training_log', $values, $types, 'uid = '.db_quote($uid, 'integer').' AND lid = '.db_quote($lid, 'integer'));
    }
    else {
      db_insert('training_log', $values, $types);
      $lid = db_insert_id();
    }

    /*
     * Update tags.
     */
    if ($tags != null && count($GLOBALS['ERROR_MSG']) == 0) {
      db_delete('training_log_tag', 'lid = '.db_quote($lid, 'integer'));
      foreach ($tags as $t) {
        if (!empty($t) && strlen($t) < 64) {
          $result = db_select('training_tag', array('tid' => 'integer'), 'title = '.db_quote($t, 'text'));
          if ($result->num_rows > 0) {
            $tid = $result->fetch_row()[0];
          } else {
            db_insert('training_tag', array('title' => $t), array('text'));
            $tid = db_insert_id();
          }
          $result->close();

          db_insert('training_log_tag', array('lid' => $lid, 'tid' => $tid), array('integer', 'integer'));
        }
      }
    }

    if (count($GLOBALS['ERROR_MSG']) == 0) {
      $GLOBALS['ERROR_MSG'][] = _('Changes saved.');
    }
  }
  else {
    $event_row['lid'] = $_POST['lid'];
    $event_row['bid'] = $_POST['bid'];
    $event_row['event_date'] = $_POST['event_date'];
    $event_row['is_ride'] = $_POST['is_ride'];
    $event_row['h'] = $_POST['h'];
    $event_row['m'] = $_POST['m'];
    $event_row['s'] = $_POST['s'];
    $event_row['distance'] = $_POST['distance'];
    $event_row['notes'] = $_POST['notes'];
    $event_row['max_speed'] = $_POST['max_speed'];
    $event_row['heart_rate'] = $_POST['heart_rate'];
    $event_row['avg_cadence'] = $_POST['avg_cadence'];
    $event_row['weight'] = $_POST['weight'];
    $event_row['calories'] = $_POST['calories'];
    $event_row['elevation'] = $_POST['elevation'];
    $event_row['tags'] = $_POST['tags'];
    $event_row['rid'] = $_POST['rid'];

    $show_update = !empty($_POST['lid']);
  }
}
elseif ($_POST['r'] == 1) {
  $lid = $_POST['lid'];
  db_delete('training_log', 'lid = '.db_quote($lid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  db_delete('training_log_tag', 'lid = '.db_quote($lid, 'integer'));

  $GLOBALS['ERROR_MSG'][] = _('Event removed.');
}
elseif ($_GET['lid']) {
  $lid = $_GET['lid'];
  $query = "
      SELECT
        l.lid,
        l.bid,
        l.rid,
        l.event_date,
        l.is_ride,
        l.time,
        l.distance,
        l.notes,
        l.max_speed,
        l.heart_rate,
        l.avg_cadence,
        l.weight,
        l.calories,
        l.elevation
      FROM
        training_log l
      WHERE
        l.uid = ".db_quote($uid, 'integer')." AND
        l.lid = ".db_quote($lid, 'integer');
  $result = db_query($query);
  $event_row = $result->fetch_assoc();
  $result->close();

  $time = $event_row['time'];
  if (!empty ($time)) {
    $time_a = explode(":", $time);
    $event_row['h'] = $time_a[0];
    $event_row['m'] = $time_a[1];
    $event_row['s'] = $time_a[2];
  }

  $event_row['tags'] = "";
  $query = "
    SELECT t.title
    FROM training_log_tag lt INNER JOIN training_tag t ON lt.tid = t.tid
    WHERE lt.lid = ".db_quote($lid, 'integer');
  $result = db_query($query);
  while ($t = $result->fetch_row()[0]) {
    $event_row['tags'] .= $t." ";
  }
  $result->close();

  $show_update = true;
}

if (!empty($_POST['next_url'])) {
  header("Location: ".$_POST['next_url']);
}

$HEADER_TITLE = ($show_update) ? _('Edit Event') : _('Add Event');
include_once ("common/header.php");

$HEADER_TITLE = _('Add');
include_once ("common/tabs.php");

/*
 * event_date setup
 */
$dt = $event_row['event_date'];
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
      START_WEEKDAY:<?php echo $_SESSION['week_start'] ?>
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

  document.forms['add_form'].event_date.value = month + '/' + day + '/' + year;
}
YAHOO.util.Event.addListener(window, 'load', eventDateCalInit);
YAHOO.util.Event.addListener(
  ['selMonth_eventDateCal', 'selDay_eventDateCal', 'selYear_eventDateCal'],
  'change',
  function() { mclCalUpdate.apply(eventDateCal) }
);

function doDelete(lid) {
  var d = document.getElementById('delete_'+lid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
function addTag(t) {
  document.forms['add_form'].tags.value += " " + t;
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<form name="add_form" action="/add.php" method="POST">
<input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
<input type="hidden" name="r" value="2"/>
<input type="hidden" name="lid" value="<?php echo $event_row['lid'] ?>"/>
<input type="hidden" name="event_date" value="<?php echo date("m/d/Y", strtotime($dt)) ?>"/>
<?php
if (is_numeric($_GET['rs']) || is_numeric($_POST['rs'])) {
  ?><input type="hidden" name="rs" value="<?php echo ($_GET)? $_GET['rs'] : $_POST['rs'] ?>"/><?php
}
if (!empty($_GET['next_url']) || !empty($_POST['next_url'])) {
  ?><input type="hidden" name="next_url" value="<?php echo ($_GET)? $_GET['next_url'] : $_POST['next_url'] ?>"/><?php
}
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox"><tr><td width="50%">

<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo ($show_update)? _('Edit Event') : _('Add Event') ?>
      <span class="hint"><?php echo _('Fields marked with an asterisk (*) are mandatory.') ?></span>
    </td>
  </tr>
  <tr>
    <td class="title">
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox">
        <tr>
          <td width="50">
      <?php echo _('Date') ?>: <br/>
    </td>
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
      <div style="text-align: center"><span class="hint">
        <?php echo _('Change day of week start on <a href="/account.php">Account &raquo;</a> page.') ?>
      </span></div>
          </td>
        </tr>
       </table>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Type') ?>: *
      <span class="hint"><?php echo _('Choose whether this entry will affect your cycling statistics.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('Cycling') ?>:
      <input type="radio" name="is_ride" value="T" <?php if (empty($event_row['is_ride']) || $event_row['is_ride'] == "T") echo "checked"; ?> />
      <?php echo _('Other') ?>:
      <input type="radio" name="is_ride" value="F" <?php if ($event_row['is_ride'] == "F") echo "checked"; ?> />
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Tags') ?>:
      <span class="hint"><?php echo _('Use tags to classify each entry.') ?></span>
      <a href="javascript:void(0)" onmouseover="overlib('<div><b><?php echo _('Help') ?></b></div><div><?php echo _('Separate each tag with a space or enclose multiple words in quotes.') ?></div>', WIDTH, 100)"
         onmouseout="nd()"><?php echo _('HELP') ?></a>
    </td>
  </tr>
  <tr>
    <td>
      <input type="text" name="tags" size="50" class="formInput" value="<?php echo stripslashes($event_row['tags']) ?>"/>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('Recent tags') ?>:
      <?php
      $query = "
        SELECT
          t.title
        FROM
          training_log l INNER JOIN
          training_log_tag lt ON l.lid = lt.lid INNER JOIN
          training_tag t ON lt.tid = t.tid
        WHERE
          l.uid = ".db_quote($uid, 'integer')."
        GROUP BY t.title
        ORDER BY MAX(l.event_date) DESC
        LIMIT 5";
      $result = db_query($query);
      while ($recent_tag = export_clean($result->fetch_row()[0])) { ?>
        <a href="javascript:void(0)" onclick="addTag('<?php echo $recent_tag ?>')"><?php echo $recent_tag ?></a>
      <?php }
      $result->close();
      ?>
    </td>
  </tr>
  <tr>
    <td>
      <?php
      echo _('Common tags').":\n";
      $COMMON_TAGS = array(_('road'), _('mountain'), _('training'), _('competition'), _('commute'));
      foreach ($COMMON_TAGS as $t) {
        echo "<a href=\"javascript:void(0)\" onclick=\"addTag('".$t."')\">".$t."</a>\n";
      }
      echo "<a href=\"javascript:void(0)\" onclick=\"addTag('co2')\" class=\"green\">co2</a>\n";
      ?>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Bike') ?>:
      <span class="hint"><?php echo _('Manage your bikes on the <a href="/bikes.php">Bikes &raquo;</a> page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <select name="bid" class="formInput">
        <option value="">--</option>
      <?php
      $query = "
        SELECT bid, CONCAT(make,' ',model) AS name, is_default
        FROM training_bike
        WHERE enabled = 'T' AND uid = ".db_quote($uid, 'integer')."
        ORDER BY name";
      $result = db_query($query);
      while ($b_row = $result->fetch_assoc()) { ?>
        <option value="<?php echo $b_row['bid'] ?>" <?php if ($b_row['bid'] == $event_row['bid'] || (empty($event_row) && $b_row['is_default'] == 'T')) { echo "selected"; } ?>>
          <?php echo export_clean($b_row['name']) ?>
        </option>
      <?php }
      $result->close();
      ?>
      </select>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Time') ?>:</td></tr>
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="2"><tr>
        <td class="tah12"><?php echo _('Hours') ?>:</td>
        <td><img src="/images/spacer.gif" width="4"/></td>
        <td>
          <input type="text" name="h" size="3" class="formInput" value="<?php echo $event_row['h'] ?>"/>
        </td>
        <td class="tah12"><?php echo _('Minutes') ?>:</td>
        <td><img src="/images/spacer.gif" width="4"/></td>
        <td>
          <input type="text" name="m" size="3" class="formInput" value="<?php echo $event_row['m'] ?>"/>
        </td>
        <td class="tah12"><?php echo _('Seconds') ?>:</td>
        <td><img src="/images/spacer.gif" width="4"/></td>
        <td>
          <input type="text" name="s" size="3" class="formInput" value="<?php echo $event_row['s'] ?>"/>
        </td>
      </tr></table>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Distance') ?>:
      <span class="hint"><?php echo _('Your default unit can be changed on the <a href="/account.php" class="t">Account &raquo;</a> page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0"><tr>
        <td>
          <input type="text" name="distance" size="5" class="formInput" value="<?php if ($event_row['distance'] > 0) { echo unit_format($event_row['distance'], $user_unit); } ?>"/>
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
    <td>
      <input type="submit" value="<?php echo ($show_update)? _('UPDATE') : _('ADD') ?>" class="btn"/>
      <?php
      $cancel_url = "/add.php";
      if (!empty($_POST['next_url']) || !empty($_GET['next_url'])) {
        $cancel_url = ($_POST) ? $_POST['next_url'] : $_GET['next_url'];
      }
      ?>
      <a href="<?php echo $cancel_url ?>"><?php echo _('Cancel') ?></a>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
</table>

</td><td width="50%">

<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Additional Information') ?>
      <span class="hint"><?php echo _('Optional') ?></span>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('More') ?>:</td></tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td><?php echo _('Avg/Max Heart Rate') ?>:</td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="heart_rate" size="5" class="formInput" value="<?php echo stripslashes($event_row['heart_rate']) ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td><?php echo _('Max Speed') ?>:</td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="max_speed" size="5" class="formInput" value="<?php if ($event_row['max_speed'] > 0) { echo unit_format($event_row['max_speed'], $user_unit); } ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td><?php echo _('Cadence') ?>:</td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="avg_cadence" size="5" class="formInput" value="<?php echo stripslashes($event_row['avg_cadence']) ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td><?php echo _('Weight') ?>:</td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="weight" size="5" class="formInput" value="<?php echo stripslashes($event_row['weight']) ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td><?php echo _('Calories') ?>:</td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="calories" size="5" class="formInput" value="<?php echo stripslashes($event_row['calories']) ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="2" cellpadding="0" class="noborbox">
        <tr>
          <td>
            <?php echo _('Elevation') ?>:
            <span class="hint"><?php echo _('Vertical feet or meters climbed.') ?></span>
          </td>
          <td><img src="/images/spacer.gif" width="4"/></td>
          <td>
            <input type="text" name="elevation" size="5" class="formInput" value="<?php echo stripslashes($event_row['elevation']) ?>"/>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr><td class="title">
    <?php echo _('Notes') ?>:
    <span class="hint"><?php echo _('HTML tags will NOT be rendered. Preface links with "http://".') ?></span>
  </td></tr>
  <tr>
    <td>
      <textarea name="notes" class="formArea"><?php echo stripslashes($event_row['notes']) ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Route') ?>:
      <span class="hint"><?php echo _('Manage your routes on the <a href="/routes.php">Routes &raquo;</a> page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <select name="rid" class="formInput">
        <option value="">--</option>
        <?php
        $query = "
          SELECT rid, name, url
          FROM training_route
          WHERE uid = ".db_quote($uid, 'integer')." AND (enabled = 'T' OR rid = ".db_quote($event_row['rid'], 'integer').")
          ORDER BY name";
        $result = db_query($query);
        while ($r_row = $result->fetch_assoc()) { ?>
          <option value="<?php echo $r_row['rid'] ?>" <?php if ($r_row['rid'] == $event_row['rid']) { echo "selected"; } ?>>
            <?php echo export_clean($r_row['name']) ?>
          </option>
        <?php }
        $result->close();
        ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Historic Data') ?>
      <span class="hint"><?php echo _('Load your historic data on the <a href="/import.php">Import &raquo;</a> page.') ?></span>
    </td>
  </tr>
</table>

</td></tr></table>
</form>

<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td>

<?php
$rs_size = 10;
include("common/user_list.php");
?>

</td></tr></table>


<?php include_once("common/footer.php"); ?>
