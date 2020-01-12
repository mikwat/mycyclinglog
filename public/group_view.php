<?php
include_once("common/common.inc.php");

if (empty($_GET['gid'])) {
  header("Location: index.php");
  exit();
}

$sid = session_check();
$user_unit = $_SESSION['user_unit'];
$gid = $_GET['gid'];

$query = "
  SELECT
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
  WHERE
    g.gid = ".db_quote($gid, 'integer')."
  GROUP BY
    g.name, g.description, g.link";
$result = db_query($query);
$g_row = $result->fetch_assoc();
$result->close();

$HEADER_TITLE = _('Group View')." : ".export_clean($g_row['name']);

include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr>

  <td width="66%">
  <table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="tbox">
    <tr>
      <td>
        <?php echo _('Group View') ?>
        |
        <a href="/group_detail.php?gid=<?php echo $gid ?>"><?php echo _('Recent Rides') ?></a>
        |
        <a href="/group_discussion.php?gid=<?php echo $gid ?>"><?php echo _('Discussion') ?></a>
        |
        <a href="/group_charts.php?gid=<?php echo $gid ?>"><?php echo _('Charts') ?></a>
      </td>
      <?php if ($sid) { ?>
        <td class="inr">
          <a href="/group_join.php?q=<?php echo export_clean($g_row['name']) ?>"><?php echo _('Join') ?> &raquo;</a>
        </td>
      <?php } ?>
    </tr>
  </table>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Group View') ?>:
        <?php echo export_clean($g_row['name']) ?>
        <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
      </td>
    </tr>
    <tr>
      <td class="cgray">
        <?php echo html_string_format($g_row['description']) ?>
      </td>
    </tr>
    <?php if (!empty($g_row['link'])) { ?>
      <tr>
        <td><?php echo html_string_format($g_row['link']) ?></td>
      </tr>
    <?php } ?>
    <tr>
      <td>
        <?php echo $g_row['members'] ?> <?php echo _('members have ridden') ?>
        <?php echo unit_format($g_row['distance'], $user_unit)." ".$user_unit." "._('at')." " ?>
        <?php echo unit_format($g_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <img src="/charts/group_monthly_chart.php?attr=distance&gid=<?php echo $gid ?>" border="0"/>
      </td>
    </tr>
    <?php
    $query = "
      SELECT
        u.uid,
        ".SQL_NAME." AS name,
        u.username,
        u.location,
        ug.admin,
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
      FROM
        training_user u INNER JOIN training_user_group ug ON u.uid = ug.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND (YEAR(l.event_date) = YEAR(".db_now().") OR l.event_date IS NULL) LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE
        ug.gid = ".db_quote($gid, 'integer')."
      GROUP BY u.uid
      ORDER BY distance DESC";
    $result = db_query($query);
    while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td class="title" colspan="2">
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
        <?php if ($row['admin'] == 'Y') { ?>
          <span class="cred">(<?php echo _('Group Admin') ?>)</span>
        <?php }
        $output = "";
        if ($row['distance'] > 0) {
          $output .= unit_format($row['distance'], $user_unit)." ".$user_unit;
        }
        else {
          $output .= _('Unknown distance');
        }
        if (!empty($row['time'])) {
          $output .= " ".sprintf(_('in %s hours'), $row['time']);
        }
        if ($row['avg_speed'] > 0) {
          $output .= " ".sprintf(_('at %s %s/h'), unit_format($row['avg_speed'], $user_unit), $user_unit);
        }
        echo export_clean($output);
        ?>
      </td>
    </tr>
    <?php }
    $result->close();
    ?>
  </table>

</td><td class="cell">

  <?php include("common/group_highlights.php") ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
