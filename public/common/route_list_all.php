<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$user_unit = $_SESSION['user_unit'];

/*
 * Limit clause setup.
 */
if (empty($rs_size) || !is_numeric($rs_size)) {
  $rs_size = 5;
}

if (is_numeric($_REQUEST['rs'])) {
  $rs = $_REQUEST['rs'];
} else {
  $rs = 0;
}

$rs_prev = $rs - $rs_size;
$rs_next = $rs + $rs_size;

$query = "
  SELECT COUNT(*)
  FROM training_route";
$result = db_query($query);
$rs_max = $result->fetch_row()[0];
$result->close();

if ($rs_next >= $rs_max) {
  $rs_next = -1;
}

/*
 * Query.
 */
$query = "
  SELECT
    u.uid,
    ".SQL_NAME." AS name,
    u.username,
    u.location,
    r.rid,
    r.name AS route_name,
    r.url,
    r.notes
  FROM
    training_route r INNER JOIN
    training_user u ON r.uid = u.uid
  ORDER BY created_on DESC
  LIMIT $rs, $rs_size";
$result = db_query($query);
?>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('All Routes') ?></td>
  </tr>
  <?php
  if ($result->num_rows == 0) { ?>
    <tr><td colspan="3"><?php echo _('Nothing to display.') ?></td></tr>
  <?php }
  while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td class="title">
        <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['route_name']) ?></a>
      </td>
      <td class="title inr">
        <?php if ($sid) { ?>
          <!-- TODO: Add COPY link. -->
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
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
  <?php
  }
  $result->close();
  ?>
  <tr>
    <td class="title">
      <?php echo ($rs + 1)." - ".($rs + (($rs_max < $rs_size)? $rs_max : $rs_size))." of ".$rs_max ?>
    </td>
    <?php
    $nav_link = $_SERVER['PHP_SELF']."?";
    ?>
    <td class="title inr">
      <?php if ($rs_prev >= 0) { ?>
        <a href="<?php echo $nav_link ?>rs=0"><?php echo _('Start') ?></a>
        |
        <a href="<?php echo $nav_link ?>rs=<?php echo $rs_prev ?>">&laquo; <?php echo _('Previous') ?></a>
      <?php } ?>
      <?php if ($rs_next >= 0) { ?>
        |
        <a href="<?php echo $nav_link ?>rs=<?php echo $rs_next ?>"><?php echo _('Next') ?> &raquo;</a>
      <?php } ?>
    </td>
  </tr>
</table>
