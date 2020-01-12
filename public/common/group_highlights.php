<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

if (is_numeric($_GET['gid'])) {
  $gid = $_GET['gid'];
}
else {
  exit();
}

$user_unit = $_SESSION['user_unit'];
if (isset($_GET['unit']) && in_array($_GET['unit'], array('km', 'mi'))) {
  $user_unit = $_GET['unit'];
}
elseif (empty($user_unit)) {
  $user_unit = 'mi';
}

$section = array();
$show_all = true;
if (isset($_GET['custom']) && isset($_GET['section']) &&
    $_GET['custom'] == 'true' && is_array($_GET['section'])) {
  $section = $_GET['section'];
  $show_all = false;
}

if (!isset($EMBEDED)) {
  $EMBEDED = false;
}
?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2">
      <?php
      if ($EMBEDED === true) {
        echo _('My Cycling Log');
      }
      else {
        echo _('Group Highlights');
      }
      ?>
    </td>
  </tr>

  <?php if ($show_all || in_array("maxDistance", $section)) { ?>
  <?php
  $query = "
      SELECT
        u.uid,
        ".SQL_NAME." AS name,
        u.username,
        u.location,
        l.event_date,
        l.distance
      FROM
        training_user_group ug INNER JOIN
        training_user u ON ug.uid = u.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND YEAR(l.event_date) = YEAR(".db_now().") LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE
        ug.gid = ".db_quote($gid, 'integer')."
      ORDER BY distance DESC
      LIMIT 1";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Longest distance in a single ride') ?></td></tr>
  <tr>
    <td colspan="2">
      <?php echo date_format_std($row['event_date']) ?>
      <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo unit_format($row['distance'], $user_unit) ?>
      <?php echo $user_unit ?>
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("avgSpeed", $section)) { ?>
  <?php
  $query = "
    SELECT
      SUM(l.distance) / (SUM(TIME_TO_SEC(l.time)) / 3600.0) AS avg_speed
      FROM
        training_user_group ug INNER JOIN
        training_user u ON ug.uid = u.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND YEAR(l.event_date) = YEAR(".db_now().")
          AND l.time > 0 AND l.distance > 0
      WHERE
        ug.gid = ".db_quote($gid, 'integer');
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Overall average speed') ?></td></tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo unit_format($row['avg_speed'], $user_unit) ?>
      <?php echo $user_unit ?>/h
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("rides30", $section)) { ?>
  <?php
  $query = "
    SELECT
      COUNT(l.lid) AS rides
    FROM
      training_user_group ug INNER JOIN
      training_user u ON ug.uid = u.uid LEFT OUTER JOIN
      training_log l ON u.uid = l.uid AND l.is_ride = 'T'
    WHERE
      ug.gid = ".db_quote($gid, 'integer')." AND DATE_SUB(".db_now().", INTERVAL 30 DAY) < l.event_date";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Number of rides in last 30 days') ?></td></tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo number_format($row['rides'], 0) ?>
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("distance7", $section)) { ?>
  <?php
  $query = "
    SELECT
      SUM(l.distance) AS distance,
      SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
      SEC_TO_TIME(SUM(TIME_TO_SEC(l.time))) AS time
    FROM
      training_user_group ug INNER JOIN
      training_user u ON ug.uid = u.uid LEFT OUTER JOIN
      training_log l ON u.uid = l.uid AND l.is_ride = 'T' LEFT OUTER JOIN
      training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
    WHERE
      ug.gid = ".db_quote($gid, 'integer')." AND DATE_SUB(".db_now().", INTERVAL 7 DAY) < l.event_date";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();

  $output = unit_format($row['distance'], $user_unit)." ".$user_unit;
  if (!empty($row['time'])) {
    $output .= " ".sprintf(_('in %s hours'), $row['time']);
  }
  if ($row['avg_speed'] > 0) {
    $output .= " ".sprintf(_('at %s %s/h'), unit_format($row['avg_speed'], $user_unit), $user_unit);
  }
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Last 7 days') ?></td></tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo $output ?>
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("distance30", $section)) { ?>
    <?php
    $query = "
      SELECT
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
        SEC_TO_TIME(SUM(TIME_TO_SEC(l.time))) AS time
      FROM
        training_user_group ug INNER JOIN
        training_user u ON ug.uid = u.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE
        ug.gid = ".db_quote($gid, 'integer')." AND DATE_SUB(".db_now().", INTERVAL 30 DAY) < l.event_date";
    $result = db_query($query);
    $row = $result->fetch_assoc();
    $result->close();

    $output = unit_format($row['distance'], $user_unit)." ".$user_unit;
    if (!empty($row['time'])) {
      $output .= " ".sprintf(_('in %s hours'), $row['time']);
    }
    if ($row['avg_speed'] > 0) {
      $output .= " ".sprintf(_('at %s %s/h'), unit_format($row['avg_speed'], $user_unit), $user_unit);
    }
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Last 30 days') ?></td></tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo $output ?>
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("ytdDistance", $section)) { ?>
  <?php
  $query = "
    SELECT
      SUM(l.distance) AS distance,
      SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
      SEC_TO_TIME(SUM(TIME_TO_SEC(l.time))) AS time
    FROM
      training_user_group ug INNER JOIN
      training_user u ON ug.uid = u.uid LEFT OUTER JOIN
      training_log l ON u.uid = l.uid AND l.is_ride = 'T' LEFT OUTER JOIN
      training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
    WHERE
      ug.gid = ".db_quote($gid, 'integer')." AND YEAR(l.event_date) = YEAR(".db_now().")";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  $result->close();

  $output = unit_format($row['distance'], $user_unit)." ".$user_unit;
  if (!empty($row['time'])) {
    $output .= " ".sprintf(_('in %s hours'), $row['time']);
  }
  if ($row['avg_speed'] > 0) {
    $output .= " ".sprintf(_('at %s %s/h'), unit_format($row['avg_speed'], $user_unit), $user_unit);
  }
  ?>
  <tr><td class="title" colspan="2"><?php echo _('Since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></td></tr>
  <tr>
    <td colspan="2" class="inr">
      <?php echo $output ?>
    </td>
  </tr>
  <?php } ?>

  <?php if ($show_all || in_array("commute", $section)) { ?>
  <?php
  $query = "
    SELECT
      AVG(u.mpd) AS mpd,
      AVG(u.mpg) AS mpg,
      SUM(l.distance) AS distance,
      SUM(l.distance) / AVG(u.mpd) AS cost
    FROM
      training_user_group ug INNER JOIN
      training_user u ON ug.uid = u.uid INNER JOIN
      training_log l ON u.uid = l.uid INNER JOIN
      training_log_tag lt ON l.lid = lt.lid INNER JOIN
      training_tag t ON lt.tid = t.tid AND LOWER(t.title) = 'co2'
    WHERE
      ug.gid = ".db_quote($gid, 'integer')." AND YEAR(l.event_date) = YEAR(".db_now().")";
  $result = db_query($query);
  $row = $result->fetch_assoc();
  ?>
  <tr><td class="title" colspan="2">
    <?php echo _('CO2') ?>
  </td></tr>
  <tr><td class="cgray tah10" colspan="2">
    <?php echo _("Tagged 'co2' since") ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?>
  </td></tr>
  <tr>
    <td>
      <?php echo _('Distance') ?>:
    </td>
    <td class="inr">
      <?php echo unit_format($row['distance'], $user_unit) ?>
      <?php echo $user_unit ?>
    </td>
  </tr>
  <tr class="green">
    <td>
      <?php echo _('CO<sub>2</sub> Emissions') ?>:
    </td>
    <td class="inr">
      <?php echo get_co2($row['distance'], $row['mpg']) ?> <?php echo _('tons') ?>
    </td>
  </tr>
  <tr class="green">
    <td class="inr" colspan="2">
      <span class="cgray tah10">(<?php echo sprintf(_('based on %s MPG'), unit_format($row['mpg'])) ?>)</span>
    </td>
  </tr>
  <?php if ($result->num_rows == 0) { ?>
    <tr>
      <td colspan="2">
        <?php echo _('No cost savings') ?>.
      </td>
    </tr>
  <?php } else { ?>
    <tr>
      <td>
        <?php echo _('Savings') ?>:
      </td>
      <td class="inr">
        $<?php echo unit_format($row['cost'], $user_unit) ?>
      </td>
    </tr>
    <tr>
      <td class="inr" colspan="2">
        <span class="cgray tah10">(<?php echo sprintf(_('based on %s MP$'), unit_format($row['mpd'])) ?>)</span>
      </td>
    </tr>
  <?php }
  $result->close();
  ?>
  <?php } ?>
</table>
