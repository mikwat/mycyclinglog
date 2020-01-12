<?php
include_once("common/common.inc.php");

if (empty($_GET['rid'])) {
  header("Location: index.php");
  exit();
}

$sid = session_check();
$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];
$rid = $_GET['rid'];

$query = "
  SELECT
    r.rid,
    r.uid,
    r.name,
    r.url,
    r.notes,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_route r LEFT OUTER JOIN training_log l ON r.rid = l.rid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE r.rid = ".db_quote($rid, 'integer')."
  GROUP BY r.rid, r.uid, r.name, r.url, r.notes";
$result = db_query($query);
$route_row = $result->fetch_assoc();
$result->close();

$is_owner = false;
$owner_username = "";
if ($sid && $uid == $route_row['uid']) {
  $is_owner = true;
}
else {
  $types = array('username' => 'text');
  $result = db_select('training_user', $types, 'uid = '.db_quote($route_row['uid'], 'integer'));
  $owner_username = $result->fetch_assoc()['username'];
  $result->close();
}

$HEADER_TITLE = _('Route Detail')." : ".export_clean($route_row['name']);
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2">
      <?php if ($is_owner === false) { ?>
        <a href="/profile/<?php echo urlencode($owner_username) ?>"><?php echo export_clean($owner_username) ?></a>
      <?php } ?>
      <?php echo _('Route Detail') ?>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo export_clean($route_row['name']) ?>
    </td>
    <td class="title inr">
      <?php if ($is_owner === true) { ?>
        <a href="/routes.php?rid=<?php echo $route_row['rid'] ?>"><img src="images/icon_edit.gif" border="0" alt="<?php echo _('Edit') ?>"/></a>
      <?php } ?>
    </td>
  </tr>
  <?php if (!empty($route_row['notes'])) { ?>
  <tr>
    <td colspan="2">
      <?php echo html_string_format($route_row['notes']) ?>
    </td>
  </tr>
  <?php } ?>
  <?php if (!empty($route_row['url'])) { ?>
  <tr>
    <td colspan="2">
      <?php echo html_string_format($route_row['url']) ?>
    </td>
  </tr>
  <?php } ?>
  <tr>
    <td colspan="2" class="cgray">
      <?php echo unit_format($route_row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
      <?php echo unit_format($route_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
    </td>
  </tr>
</table>

<?php include("common/user_recent.php"); ?>

</td>
<td width="50%" class="cell">

<?php if (validate_route($route_row['url'])) { ?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td>
      <?php display_route($route_row) ?>
    </td>
  </tr>
</table>
<?php } ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
