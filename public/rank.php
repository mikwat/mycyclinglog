<?php
include_once("common/common.inc.php");

$sid = session_check();
$user_unit = $_SESSION['user_unit'];
$uid = $_SESSION['uid'];

$HEADER_TITLE = _('Rank');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr>
  <td>

  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Rank: Top 100') ?>
        <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
      </td>
    </tr>
    <?php
    $query = "
      SELECT
        u.uid,
        ".SQL_NAME." AS name,
        u.username,
        u.location,
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
      FROM
        training_user u INNER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND (YEAR(l.event_date) = YEAR(".db_now().") OR l.event_date IS NULL) LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      GROUP BY u.uid
      ORDER BY distance DESC
      LIMIT 100";
    $result = db_query($query);
    $rank = 1;
    while ($row = $result->fetch_assoc()) { ?>
    <?php if ($uid == $row['uid']) { ?>
      <tr class="highlight">
    <?php } else { ?>
      <tr>
    <?php } ?>
      <td class="title" colspan="2">
        <?php echo $rank++ ?>.
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
        <?php
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
    <?php
    }
    $result->close();
    ?>
  </table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
