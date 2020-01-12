<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];
$GLOBALS['ERROR_MSG'] = [];

if (isset($_GET['form_type']) && $_GET['form_type'] == "default") {
  $bid = $_GET['bid'];
  db_update('training_bike', array('is_default' => 'F'), array('text'), 'uid = '.db_quote($uid, 'integer'));
  db_update('training_bike', array('is_default' => 'T'), array('text'), 'bid = '.db_quote($bid, 'integer').' AND uid = '.db_quote($uid, 'integer'));

  header("Location: bikes.php");
}
elseif ($_POST['form_type'] == "bike" && check_form_key($_POST['form_key'])) {
  $bid = $_POST['bid'];
  $make = $_POST['make'];
  if (empty($make)) {
    $GLOBALS['ERROR_MSG'][] = _('Make is required.');
  }

  $model = $_POST['model'];
  if (empty($model)) {
    $GLOBALS['ERROR_MSG'][] = _('Model is required.');
  }

  $year = $_POST['year'];
  if (!empty($year) && !is_numeric($year)) {
    $GLOBALS['ERROR_MSG'][] = _('Year must be numeric.');
  }

  $enabled = $_POST['enabled'];
  if (empty($enabled)) {
    $enabled = 'F';
  }

  if (!isset($GLOBALS['ERROR_MSG']) || count($GLOBALS['ERROR_MSG']) == 0) {
    $values = array(
      'uid' => $uid,
      'make' => $make,
      'model' => $model,
      'year' => $year,
      'enabled' => $enabled
    );
    $types = array(
      'integer',
      'text',
      'text',
      'integer',
      'text'
    );

    if (empty($bid)) {
      db_insert('training_bike', $values, $types);
      $GLOBALS['ERROR_MSG'][] = _('New bike created.');
    }
    else {
      db_update('training_bike', $values, $types, 'bid = '.db_quote($bid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
      $GLOBALS['ERROR_MSG'][] = _('Changes saved.');
    }

    unset($_POST);
  }
}
elseif ($_POST['form_type'] == "delete_bike") {
  $bid = $_POST['bid'];
  db_delete('training_bike', 'bid = '.db_quote($bid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  db_update('training_log', array('bid' => null), array('integer'), 'bid = '.db_quote($bid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  $GLOBALS['ERROR_MSG'][] = _('Bike removed.');
}

$HEADER_TITLE = _('Bikes');
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

<?php
$query = "
  SELECT
    b.bid,
    b.make,
    b.model,
    b.year,
    b.enabled,
    b.is_default,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_bike b LEFT OUTER JOIN training_log l ON b.bid = l.bid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE b.uid = ".db_quote($uid, 'integer')."
  GROUP BY b.bid, b.make, b.model, b.year, b.enabled, b.is_default";
$result = db_query($query);
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('My Bikes') ?></td>
  </tr>
</table>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="title"><?php echo _('Make and Model') ?></td>
    <td class="title"><?php echo _('Year') ?></td>
    <td class="title"><?php echo _('Active') ?></td>
    <td></td>
  </tr>
  <?php if ($result->num_rows == 0) { ?>
    <tr><td colspan="3"><?php echo _('You have no bikes. Add a bike below.') ?></td></tr>
  <?php }
  while ($row = $result->fetch_assoc()) { ?>
  <tr>
    <td>
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox">
        <tr>
          <td align="left">
            <a href="/bike_detail.php?bid=<?php echo $row['bid'] ?>"><?php echo export_clean($row['make']." ".$row['model']) ?></a>
          </td>
          <td class="inr">
            <a href="/bikes.php?bid=<?php echo $row['bid'] ?>"><img src="images/icon_edit.gif" border="0" alt="Edit"/></a>
            <div id="delete_<?php echo $row['bid'] ?>" style="display: none">
              <form action="/bikes.php" method="post">
                <input type="hidden" name="form_type" value="delete_bike"/>
                <input type="hidden" name="bid" value="<?php echo $row['bid'] ?>"/>
                <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                  <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                  <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                </tr></table>
              </form>
            </div>
            <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['bid'] ?>)">
              <img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="cgray">
            <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at')." " ?>
            <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
          </td>
        </tr>
      </table>
    </td>
    <td>
      <?php echo $row['year'] ?>
    </td>
    <td>
      <?php echo ($row['enabled'] == 'T')? _('Yes') : _('No') ?>
    </td>
    <td>
      <div>
        <?php
        if ($row['is_default'] == 'T') {
          echo _('Default');
        } else { ?>
          <a href="/bikes.php?form_type=default&bid=<?php echo $row['bid'] ?>"><?php echo _('Set Default') ?></a>
        <?php } ?>
      </div>
      <div><a href="/bike_detail.php?bid=<?php echo $row['bid'] ?>"><?php echo _('Service Record') ?> &raquo;</a></div>
    </td>
  </tr>
  <?php }
  $result->close();
  ?>
</table>

</td>
<td width="50%" class="cell">

<?php
$row = array();
if (!empty($_GET['bid'])) {
  $bid = $_GET['bid'];

  $types = array(
    'make' => 'text',
    'model' => 'text',
    'year' => 'integer',
    'enabled' => 'text'
  );

  $result = db_select('training_bike', $types, 'bid = '.db_quote($bid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  $row = $result->fetch_assoc();
  $result->close();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $bid = $_POST['bid'];

  $row['make'] = $_POST['make'];
  $row['model'] = $_POST['model'];
  $row['year'] = $_POST['year'];
  $row['enabled'] = $_POST['enabled'];
} else {
  unset($bid);
  $row['enabled'] = 'T';
}
?>
<form name="bike_form" enctype="multipart/form-data" action="/bikes.php" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000"/>
<input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
<input type="hidden" name="form_type" value="bike"/>
<input type="hidden" name="bid" value="<?php echo $bid ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo (empty($bid))? _('Add Bike') : _('Edit Bike') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Make') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="make" size="25" class="formInput" value="<?php echo stripslashes($row['make']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Model') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="model" size="25" class="formInput" value="<?php echo stripslashes($row['model']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Year') ?>:</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="year" size="4" class="formInput" value="<?php echo $row['year'] ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Active') ?>:
      <span class="hint"><?php echo _('Inactive bikes will not appear on the Add page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('Yes') ?>
      <input type="radio" name="enabled" size="25" value="T"
        <?php if ($row['enabled'] == 'T') echo 'checked' ?>/>
      &nbsp;&nbsp;
      <?php echo _('No') ?>
      <input type="radio" name="enabled" size="25" value="F"
        <?php if ($row['enabled'] == 'F') echo 'checked' ?>/>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo (empty($bid))? _('ADD') : _('UPDATE') ?>" class="btn"/>
      <a href="/bikes.php"><?php echo _('Cancel') ?></a>
    </td>
  </tr>
  <?php if (isset($_POST['form_type']) && $_POST['form_type'] == "bike" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
