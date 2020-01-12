<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

/* Number of days to include. */
$days = 30;
/* Number of days as a member before eligible. */
$member_days = 3;
/* Maximum number of days since last login. */
$down = 30;
/* Number of rides before eligible. */
$rides = 4;
/* Maximum possible avg speed. */
$max_avg = 30;
/* Maximum possible distance in single ride. */
$max_dist = 500;
/* Minimum number of group members. */
$min_group_members = 3;

define('DISTANCE_SINGLE_ROW', 'highlights_distance_single_row');
define('AVG_SPEED_ROW', 'highlights_avg_speed_row');
define('AVG_SPEED_GROUP_ROW', 'highlights_avg_speed_group_row');
define('MOST_RIDES', 'highlights_most_rides');
define('DISTANCE_ROW', 'highlights_distance_row');
define('DISTANCE_GROUP_ROW', 'highlights_distance_group_row');
define('COMMUTE_ROW', 'highlights_commute_row');

/*
 * Longest distance in a single ride.
 */
$distance_single_row = cache_get(DISTANCE_SINGLE_ROW);
if ($distance_single_row === false) {
  $query = "
    SELECT
      u.uid,
      ".SQL_NAME." AS name,
      u.username,
      u.location,
      MAX(l.distance) AS distance,
      COUNT(l.lid) AS rides,
      SUM(l.distance) / (SUM(TIME_TO_SEC(l.time)) / 3600.0) AS avg_speed
    FROM
      training_user u INNER JOIN training_log l ON l.uid = u.uid
    WHERE
      l.is_ride = 'T'
      AND (DATE_SUB(".db_now().", INTERVAL $member_days DAY) >= u.signup_date)
      AND (DATE_SUB(".db_now().", INTERVAL $down DAY) <= u.last_login)
      AND (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL $days DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
    GROUP BY u.uid, u.username
    HAVING COUNT(l.lid) >= $rides AND avg_speed < $max_avg AND distance < $max_dist
    ORDER BY distance DESC
    LIMIT 1";
  $result = db_query($query);
  $distance_single_row = $result->fetch_assoc();
  cache_save($distance_single_row, DISTANCE_SINGLE_ROW);
  $result->free();
}

/*
 * Highest avg speed.
 */
$avg_speed_row = cache_get(AVG_SPEED_ROW);
if ($avg_speed_row === false) {
  $query = "
    SELECT
      u.uid,
      ".SQL_NAME." AS name,
      u.username,
      u.location,
      SUM(l.distance) / (SUM(TIME_TO_SEC(l.time)) / 3600.0) AS avg_speed
    FROM
      training_user u INNER JOIN training_log l ON l.uid = u.uid
    WHERE
      l.is_ride = 'T'
      AND l.time > 0
      AND l.distance > 0
      AND (DATE_SUB(".db_now()." , INTERVAL $member_days DAY) >= u.signup_date)
      AND (DATE_SUB(".db_now().", INTERVAL $down DAY) <= u.last_login)
      AND (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL $days DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
    GROUP BY u.uid
    HAVING COUNT(l.lid) >= $rides AND avg_speed < $max_avg
    ORDER BY avg_speed DESC
    LIMIT 1";
  $result = db_query($query);
  $avg_speed_row = $result->fetch_assoc();
  cache_save($avg_speed_row, AVG_SPEED_ROW);
  $result->free();
}

$distance_group_row = cache_get(DISTANCE_GROUP_ROW);
if ($distance_group_row === false) {
  $query = "
    SELECT
      g.gid,
      g.name,
      SUM(l.distance) AS distance
    FROM
      training_log l INNER JOIN training_user u ON l.uid = u.uid INNER JOIN
      training_user_group ug ON u.uid = ug.uid INNER JOIN
      training_group g ON ug.gid = g.gid
    WHERE (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL $days DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
    GROUP BY g.name
    ORDER BY distance DESC
    LIMIT 1";
  $result = db_query($query);
  $distance_group_row = $result->fetch_assoc();
  cache_save($distance_group_row, DISTANCE_GROUP_ROW);
  $result->free();
}

/*
 * Commute.
 */
$commute_row = cache_get(COMMUTE_ROW);
if ($commute_row === false) {
  $query = "
    SELECT
      AVG(u.mpd) AS mpd,
      AVG(u.mpg) AS mpg,
      SUM(l.distance) AS distance,
      SUM(l.distance) / AVG(u.mpd) AS cost
    FROM
      training_user u INNER JOIN
      training_log l ON u.uid = l.uid INNER JOIN
      training_log_tag lt ON l.lid = lt.lid INNER JOIN
      training_tag t ON lt.tid = t.tid AND LOWER(t.title) = 'co2'
    WHERE l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL $days DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY)";
  $result = db_query($query);
  $commute_row = $result->fetch_assoc();
  cache_save($commute_row, COMMUTE_ROW);
  $result->free();
}
?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr><td class="head" colspan="2">
    <?php echo _('Highlights') ?>
    <span class="cgray tah10"><?php echo sprintf(ngettext('Last %d day', 'Last %d days', $days), $days) ?></span>
  </td></tr>
  <tr><td class="title" colspan="2">
    <?php echo _('CO2') ?>
    <span class="cgray tah10">(<?php echo _("tagged 'co2'") ?>)</span>
  </td></tr>
  <tr>
    <td>
      <?php echo _('Distance') ?>:
    </td>
    <td class="inr">
      <?php echo unit_format($commute_row['distance'], $user_unit) ?>
      <?php echo $user_unit ?>
    </td>
  </tr>
  <tr class="green">
    <td>
      <?php echo _('CO<sub>2</sub> Emissions') ?>:
    </td>
    <td class="inr">
      <?php echo get_co2($commute_row['distance'], $commute_row['mpg']) ?> <?php echo _('tons') ?>
    </td>
  </tr>
  <tr class="green">
    <td class="inr" colspan="2">
      <span class="cgray tah10">(<?php echo sprintf(_('based on site average %s MPG'), unit_format($commute_row['mpg'])) ?>)</span>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('Savings') ?>:
    </td>
    <td class="inr">
      $<?php echo unit_format($commute_row['cost'], $user_unit) ?>
    </td>
  </tr>
  <tr>
    <td class="inr" colspan="2">
      <span class="cgray tah10">(<?php echo sprintf(_('based on site average %s MP$'), unit_format($commute_row['mpd'])) ?>)</span>
    </td>
  </tr>

  <tr><td class="title" colspan="2"><?php echo _('Longest distance in a single ride') ?></td></tr>
  <tr>
    <td>
      <a href="/profile/<?php echo urlencode($distance_single_row['username']) ?>" title="<?php echo export_clean($distance_single_row['name']) ?>"><?php echo export_clean($distance_single_row['username']) ?></a>
      <span class="cgray"><?php echo export_clean($distance_single_row['location']) ?></span>
    </td>
    <td class="inr">
      <?php echo unit_format($distance_single_row['distance'], $user_unit) ?>
      <?php echo $user_unit ?>
    </td>
  </tr>

  <tr><td class="title" colspan="2"><?php echo _('Highest average speed') ?></td></tr>
  <tr>
    <td>
      <a href="/profile/<?php echo urlencode($avg_speed_row['username']) ?>" title="<?php echo export_clean($avg_speed_row['name']) ?>"><?php echo export_clean($avg_speed_row['username']) ?></a>
      <span class="cgray"><?php echo export_clean($avg_speed_row['location']) ?></span>
    </td>
    <td class="inr">
      <?php echo unit_format($avg_speed_row['avg_speed'], $user_unit) ?>
      <?php echo $user_unit ?>/h
    </td>
  </tr>

  <?php if ($most_rides) { ?>
    <tr><td class="title" colspan="2"><?php echo _('Most rides') ?></td>
    </tr>
    <?php foreach ($most_rides as $row) { ?>
    <tr>
      <td>
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
      </td>
      <td class="inr">
        <?php echo number_format($row['rides'], 0) ?>
      </td>
    </tr>
    <?php } ?>
  <?php } ?>

  <tr><td class="title" colspan="2"><?php echo _('Longest distance') ?> (<a href="/rank.php"><?php echo _('Top 100') ?></a>)</td></tr>
  <?php if ($distance_row) { ?>
    <tr>
      <td>
        <a href="/profile/<?php echo urlencode($distance_row['username']) ?>" title="<?php echo export_clean($distance_row['name']) ?>"><?php echo export_clean($distance_row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($distance_row['location']) ?></span>
      </td>
      <td class="inr">
        <?php echo unit_format($distance_row['distance'], $user_unit) ?>
        <?php echo $user_unit ?>
      </td>
    </tr>
  <?php } ?>
  <tr>
    <td>
      <a href="/group_view.php?gid=<?php echo $distance_group_row['gid'] ?>"><?php echo export_clean($distance_group_row['name']) ?></a>
    </td>
    <td class="inr">
      <?php echo unit_format($distance_group_row['distance'], $user_unit) ?>
      <?php echo $user_unit ?>
    </td>
  </tr>
</table>
