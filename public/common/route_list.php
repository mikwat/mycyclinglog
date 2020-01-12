<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$user_unit = $_SESSION['user_unit'];
if (!empty($_GET['uid'])) {
  $uid = $_GET['uid'];
}
else {
  $uid = $_SESSION['uid'];
}

$query = "
  SELECT
    r.rid,
    r.name,
    r.url,
    r.notes,
    r.enabled,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_route r LEFT OUTER JOIN training_log l ON r.rid = l.rid LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE r.uid = ".db_quote($uid, 'integer')."
  GROUP BY r.rid, r.name, r.url, r.notes, r.enabled
  ORDER BY r.enabled, SUM(l.distance) DESC, r.name";
$result = db_query($query);
$routes_enabled = array();
$routes_disabled = array();
while ($row = $result->fetch_assoc()) {
  if ($row['enabled'] == 'T') {
    $routes_enabled[] = $row;
  }
  else {
    $routes_disabled[] = $row;
  }
}
$result->close();
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('My Routes') ?></td>
  </tr>
</table>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="cgray">
      <?php echo _('Click the <img src="images/icon_edit.gif" border="0" alt="Edit" align="absmiddle"/> icon next to a route to view and edit it.') ?>
    </td>
  </tr>
  <?php foreach (array('enabled' => $routes_enabled, 'disabled' => $routes_disabled) as $key => $routes) { ?>
  <tr><td class="title" colspan="3"><?php echo ($key == 'enabled') ? _('Active Routes') : _('Inactive Routes') ?></td></tr>
  <?php if (empty($routes)) { ?>
  <tr><td colspan="3"><?php echo _('No routes defined.') ?></td></tr>
  <?php } ?>
  <?php foreach ($routes as $row) { ?>
  <tr>
    <td>
      <table width="100%" border="0" cellspacing="0" cellpadding="0" class="noborbox">
        <tr>
          <td align="left">
            <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['name']) ?></a>
          </td>
          <td class="inr">
            <a href="/routes.php?rid=<?php echo $row['rid'] ?>"><img src="images/icon_edit.gif" border="0" alt="Edit"/></a>
            <div id="delete_<?php echo $row['rid'] ?>" style="display: none">
              <form action="/routes.php" method="post">
                <input type="hidden" name="form_type" value="delete_route"/>
                <input type="hidden" name="rid" value="<?php echo $row['rid'] ?>"/>
                <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                  <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                  <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                </tr></table>
              </form>
            </div>
            <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['rid'] ?>)">
              <img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
          </td>
        </tr>
        <?php if (!empty($row['notes'])) { ?>
        <tr>
          <td colspan="2">
            <?php echo html_string_format($row['notes']) ?>
          </td>
        </tr>
        <?php } ?>
        <?php if (!empty($row['url'])) { ?>
        <tr>
          <td colspan="2">
            <?php echo html_string_format($row['url']) ?>
          </td>
        </tr>
        <?php } ?>
        <tr>
          <td colspan="2" class="cgray">
            <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
            <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <?php } ?>
  <?php } ?>
</table>
