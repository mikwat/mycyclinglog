<?php
header('Content-Type: text/html;charset=iso-8859-1');
include_once("common.inc.php");
?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <?php
  $query = "
    SELECT
      u.uid,
      ".SQL_NAME." AS name,
      u.username,
      u.location
    FROM
      training_online o INNER JOIN training_user u ON o.uid = u.uid";
  $result = db_query($query);
  if ($result->num_rows == 0) { ?>
    <tr>
      <td><?php echo _('Nobody online at this time') ?>.</td>
    </tr>
  <? }
  while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td>
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
      </td>
    </tr>
  <?php
  }
  $result->close();
  ?>
</table>
