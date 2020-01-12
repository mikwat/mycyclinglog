<?php
include_once("common/common.inc.php");

if (empty($_GET['gid'])) {
  header("Location: index.php");
  exit();
}

$sid = session_check();
$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];
$gid = $_GET['gid'];

$query = "
  SELECT
    g.uid,
    g.gid,
    g.name,
    g.start_date,
    g.end_date,
    g.distance AS goal_distance,
    g.is_ride,
    DATEDIFF(g.end_date, NOW()) + 1 AS days_more,
    DATEDIFF(g.end_date, g.start_date) + 1 AS goal_days_more,
    SUM(l.distance) AS distance,
    SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
  FROM
    training_goal g LEFT OUTER JOIN
    training_log l ON g.uid = l.uid AND l.event_date >= g.start_date AND l.event_date <= g.end_date AND (g.is_ride IS NULL OR g.is_ride = l.is_ride) LEFT OUTER JOIN
    training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
  WHERE
    g.gid = ".db_quote($gid, 'integer')."
  GROUP BY g.gid, g.name, g.start_date, g.end_date, g.distance";
$result = db_query($query);
$goal_row = $result->fetch_assoc();
$result->close();

$is_owner = false;
if ($sid && $uid == $goal_row['uid']) {
  $is_owner = true;
}

$HEADER_TITLE = _('Goal Detail')." : ".export_clean($goal_row['name']);
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2"><?php echo _('Goal Detail') ?></td>
  </tr>
  <tr>
    <td class="title">
      <?php echo export_clean($goal_row['name']) ?>
    </td>
    <td class="title inr">
      <?php if ($is_owner === true) { ?>
        <a href="/goals.php?gid=<?php echo $goal_row['gid'] ?>"><img src="images/icon_edit.gif" border="0" alt="<?php echo _('Edit') ?>"/></a>
      <?php } ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="cgray">
      <?php echo unit_format($goal_row['goal_distance'], $user_unit)." ".$user_unit ?>
      <?php echo sprintf(_('between %s and %s'), date_format_nice($goal_row['start_date']), date_format_nice($goal_row['end_date'])) ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="cgray">
      <?php
      if ($goal_row['is_ride'] == "T") {
        echo " ["._('Cycling')."]";
      }
      elseif ($goal_row['is_ride'] == "F") {
        echo " ["._('Other')."]";
      }
      else {
        echo " ["._('All')."]";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td class="title" colspan="2">
      <?php echo _('Progress') ?>:
      <?php echo ($goal_row['goal_distance'] > 0)? number_format(100 * $goal_row['distance'] / $goal_row['goal_distance'], 0) : "0" ?>%
    </td>
  </tr>
  <tr>
    <td colspan="2" class="cgray">
      <?php echo unit_format($goal_row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
      <?php echo unit_format($goal_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
    </td>
  </tr>
  <?php if ($goal_row['avg_speed'] > 0 || $goal_row['days_more'] > 0) { ?>
    <tr>
      <td colspan="2">
        <ul>
          <?php
          $distance_more = $goal_row['goal_distance'] - $goal_row['distance'];
          if ($distance_more < 0) {
            $distance_more = 0;
          }
          echo "<li>".sprintf(_('A distance of <b>%s</b> remains.'), unit_format($distance_more, $user_unit)." ".$user_unit)."</li>";

          if ($goal_row['avg_speed'] > 0) {
            $hours_more = $distance_more / $goal_row['avg_speed'];
            echo "<li>".sprintf(_('At current average speed, <b>%s hours</b> of ride time required.'), number_format($hours_more, 1))."</li>";
          }

          $days_more = ($goal_row['days_more'] > $goal_row['goal_days_more'])? $goal_row['goal_days_more'] : $goal_row['days_more'];
          if ($days_more) {
            $daily_distance_more = $distance_more / $days_more;
            echo "<li>".sprintf(_('An average daily distance of <b>%s</b> required.'), unit_format($daily_distance_more, $user_unit)." ".$user_unit)."</li>";
          }

          // goal/(end date - start date + 1) * (today - end date + 1)
          if ($row['goal_days_more'] > 0) {
            $target_distance = ($row['goal_distance'] / $row['goal_days_more']) * ($row['goal_days_more'] - $row['days_more'] + 1);
            echo "<li>".sprintf(_('You should have completed a distance of <b>%s</b> by today.'), unit_format($target_distance)." ".$user_unit)."</li>";
          }
          ?>
        </ul>
      </td>
    </tr>
  <?php } ?>
</table>

</td>
<td width="50%" class="cell">

<?php include("common/user_recent.php"); ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
