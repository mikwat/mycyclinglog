<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$GLOBALS['ERROR_MSG'] = [];

if ($_POST['form_type'] == "route" && check_form_key($_POST['form_key'])) {
  $rid = $_POST['rid'];
  $name = $_POST['name'];
  if (empty($name)) {
    $GLOBALS['ERROR_MSG'][] = _('Name is required.');
  }

  $url = $_POST['url'];
  if (!empty($url) && !validate_route($url)) {
    $GLOBALS['ERROR_MSG'][] = _('Link must refer to valid Bikely, GPSies, Cyclogz, MapMyRide, or BikeMap route.');
  }

  $enabled = $_POST['enabled'];
  if (empty($enabled)) {
    $enabled = 'F';
  }

  $notes = $_POST['notes'];

  if (count($GLOBALS['ERROR_MSG']) == 0) {
    if (empty($rid)) {
      db_insert('training_route',
        array('uid' => $uid, 'name' => $name, 'url' => $url, 'enabled' => $enabled, 'notes' => $notes),
        array('integer', 'text', 'text', 'text', 'text'));

      $GLOBALS['ERROR_MSG'][] = _('New route created.');
    }
    else {
      db_update('training_route',
        array('name' => $name, 'url' => $url, 'enabled' => $enabled, 'notes' => $notes),
        array('text', 'text', 'text', 'text'),
        'rid = '.db_quote($rid, 'integer').' AND uid = '.db_quote($uid, 'integer'));

      $GLOBALS['ERROR_MSG'][] = _('Changes saved.');
    }

    unset($_POST['name']);
    unset($_POST['url']);
    unset($_POST['enabled']);
    unset($_POST['notes']);
  }
}
elseif ($_POST['form_type'] == "delete_route") {
  $rid = $_POST['rid'];

  db_delete('training_route', 'rid = '.db_quote($rid, 'integer').' AND uid = '.db_quote($uid, 'integer'));

  $GLOBALS['ERROR_MSG'][] = _('Route removed.');
}

$HEADER_TITLE = _('Routes');
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
      <?php include("common/route_list.php") ?>
    </td>
    <td width="50%" class="cell">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="inr" colspan="2">
      <b><a href="/route_view.php"><?php echo _('View Other Routes') ?> &raquo;</a></b>
    </td>
  </tr>
</table>

<?php
if (!empty($_GET['rid'])) {
  $rid = $_GET['rid'];

  $result = db_select('training_route', array('rid' => 'integer', 'name' => 'text', 'url' => 'text', 'enabled' => 'text', 'notes' => 'text'),
    'rid = '.db_quote($rid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  $row = $result->fetch_assoc();
  $result->close();
}
else {
  $row = array();
  $row['rid'] = $_POST['rid'];
  $row['name'] = $_POST['name'];
  $row['url'] = $_POST['url'];
  $row['enabled'] = $_POST['enabled'];
  $row['notes'] = $_POST['notes'];
}
?>
<form name="route_form" action="/routes.php" method="POST">
<input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
<input type="hidden" name="form_type" value="route"/>
<input type="hidden" name="rid" value="<?php echo $row['rid'] ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo (empty($_GET['rid']))? _('Add Route') : _('Edit Route') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="name" size="40" class="formInput" value="<?php echo stripslashes($row['name']) ?>"/>
    </td>
  </tr>
  <tr><td class="title">
    <?php echo _('Notes') ?>:
    <span class="hint"><?php echo _('HTML tags will NOT be rendered. Preface links with "http://".') ?></span>
  </td></tr>
  <tr>
    <td>
      <textarea name="notes" class="formArea"><?php echo stripslashes($row['notes']) ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Link') ?>:
      <span class="hint">
        <?php echo _('Paste link to route from') ?>
        <a href="http://www.bikely.com" class="external" target="_blank">Bikely</a>,
        <a href="http://www.gpsies.com" class="external" target="_blank">GPSies</a>,
        <a href="http://www.cyclogz.com" class="external" target="_blank">Cyclogz</a>,
        <a href="http://www.everytrail.com" class="external" target="_blank">EveryTrail</a>,
        <a href="http://www.mapmyride.com" class="external" target="_blank">MapMyRide</a>,
        <a href="http://www.bikemap.net" class="external" target="_blank">BikeMap</a>,
        <a href="http://connect.garmin.com" class="external" target="_blank">GarminConnect</a>.
      </span>
    </td>
  </tr>
  <tr>
    <td>
      <input type="text" name="url" size="40" class="formInput" value="<?php echo stripslashes($row['url']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Active') ?>:
      <span class="hint"><?php echo _('Inactive routes will not appear on the Add page.') ?></span>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('Yes') ?>
      <input type="radio" name="enabled" size="25" value="T"
        <?php if ($row['enabled'] == 'T' || empty($row['enabled'])) echo 'checked' ?>/>
      &nbsp;&nbsp;
      <?php echo _('No') ?>
      <input type="radio" name="enabled" size="25" value="F"
        <?php if ($row['enabled'] == 'F') echo 'checked' ?>/>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo (empty($row['rid']))? _('ADD') : _('UPDATE') ?>" class="btn"/>
      <a href="/routes.php"><?php echo _('Cancel') ?></a>
    </td>
  </tr>
  <?php if ($_POST['form_type'] == "route" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

<?php if (validate_route($row['url'])) { ?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2">
      <?php echo _('Route') ?>
    </td>
  </tr>
  <tr>
    <td>
      <?php
      display_route($row);
      ?>
    </td>
  </tr>
</tabel>
<?php } ?>

<!-- begin ad tag (tile=2)-->
<script type="text/javascript" language="javascript" src="http://www2.glam.com/app/site/affiliate/viewChannelModule.act?mName=viewAdJs&affiliateId=19607415&adSize=300x250"></script>
<!-- End ad tag -->

</td></tr></table>

<?php include_once("common/footer.php"); ?>
