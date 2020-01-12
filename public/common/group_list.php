<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$user_unit = $_SESSION['user_unit'];

$query = "
  SELECT
    g.gid,
    g.name,
    g.description,
    g.link,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
    COUNT(DISTINCT u.uid) AS members
  FROM
    training_group g INNER JOIN training_user_group ug ON g.gid = ug.gid INNER JOIN
    training_user u ON ug.uid = u.uid LEFT OUTER JOIN
    training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND YEAR(l.event_date) = YEAR(".db_now().") LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  GROUP BY ug.gid
  ORDER BY distance DESC
  LIMIT 10";
$result = db_query($query);
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('All Groups by Distance') ?>
      <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
    </td>
  </tr>
  <?php while ($g_row = $result->fetch_assoc()) { ?>
    <tr>
      <td class="title">
        <a href="/group_view.php?gid=<?php echo $g_row['gid'] ?>">
          <?php echo export_clean($g_row['name']) ?></a>
      </td>
    </tr>
    <tr>
      <td class="cgray">
        <?php echo truncate_string(export_clean($g_row['description']), 100) ?>
      </td>
    </tr>
    <?php if (!empty($g_row['link'])) { ?>
      <tr>
        <td colspan="2"><?php echo html_string_format($g_row['link']) ?></td>
      </tr>
    <?php } ?>
    <tr>
      <td>
        <?php echo $g_row['members'] ?> <?php echo _('members have ridden') ?>
        <?php echo unit_format($g_row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
        <?php echo unit_format($g_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
      </td>
    </tr>
  <?php
  }
  $result->close();
  ?>
</table>
