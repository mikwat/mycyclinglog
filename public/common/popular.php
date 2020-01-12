<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$argv = $_SERVER['argv'];
$user_unit = $argv[1];
$limit = 2;

define('LOCATION_LIST', 'popular_location_list');
define('TAG_LIST', 'popular_tag_list');
define('BIKE_LIST', 'popular_bike_list');
define('GROUP_LIST', 'popular_group_list');

$location_list = cache_get(LOCATION_LIST);
if ($location_list === false) {
  $query = "
    SELECT
      u.location,
      COUNT(*) AS count
    FROM
      training_log l INNER JOIN training_user u ON l.uid = u.uid
    WHERE
      (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL 1 DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
      AND l.last_modified > DATE_SUB(".db_now().", INTERVAL 1 DAY)
    GROUP BY u.location
    ORDER BY count DESC
    LIMIT $limit";
  $result = db_query($query);
  $location_list = $result->fetch_all(MYSQLI_ASSOC);
  cache_save($location_list, LOCATION_LIST);
}

$tag_list = cache_get(TAG_LIST);
if ($tag_list === false) {
  $query = "
    SELECT
      t.title,
      COUNT(*) AS count
    FROM
      training_log l INNER JOIN training_log_tag lt ON l.lid = lt.lid INNER JOIN
      training_tag t ON lt.tid = t.tid
    WHERE
      (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL 1 DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
      AND l.last_modified > DATE_SUB(".db_now().", INTERVAL 1 DAY)
    GROUP BY t.title
    ORDER BY count DESC
    LIMIT $limit";
  $result = db_query($query);
  $tag_list = $result->fetch_all(MYSQLI_ASSOC);
  cache_save($tag_list, TAG_LIST);
}

$bike_list = cache_get(BIKE_LIST);
if ($bike_list === false) {
  $query = "
    SELECT
      b.make,
      COUNT(*) AS count
    FROM
      training_log l INNER JOIN training_bike b ON l.bid = b.bid
    WHERE
      (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL 1 DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
      AND l.last_modified > DATE_SUB(".db_now().", INTERVAL 1 DAY)
    GROUP BY b.make
    ORDER BY count DESC
    LIMIT $limit";
  $result = db_query($query);
  $bike_list = $result->fetch_all(MYSQLI_ASSOC);
  cache_save($bike_list, BIKE_LIST);
}

$group_list = cache_get(GROUP_LIST);
if ($group_list === false) {
  $query = "
    SELECT
      g.gid,
      g.name,
      COUNT(*) AS count
    FROM
      training_log l INNER JOIN training_user u ON l.uid = u.uid INNER JOIN
      training_user_group ug ON u.uid = ug.uid INNER JOIN
      training_group g ON ug.gid = g.gid
    WHERE
      (l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL 1 DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY))
      AND l.last_modified > DATE_SUB(".db_now().", INTERVAL 1 DAY)
    GROUP BY g.name
    ORDER BY count DESC
    LIMIT $limit";
  $result = db_query($query);
  $group_list = $result->fetch_all(MYSQLI_ASSOC);
  cache_save($group_list, GROUP_LIST);
}
?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr><td class="head" colspan="2">
    <?php echo _('Most Popular') ?>
    <span class="cgray tah10"><?php echo _('Last 24 hours') ?></span>
  </td></tr>
  <tr>
    <td class="title"><?php echo _('Locations') ?></td>
    <td class="title inr"><span class="cgray tah10"><?php echo _('Ride Count') ?></span></td>
  </tr>
  <?php foreach ($location_list as $row) { ?>
    <tr>
      <td>
        <?php echo export_clean($row['location']) ?>
      </td>
      <td class="inr">
        <?php echo $row['count'] ?>
      </td>
    </tr>
  <?php } ?>

  <tr>
    <td class="title" colspan="2"><?php echo _('Tags') ?></td>
  </tr>
  <?php foreach ($tag_list as $row) { ?>
    <tr>
      <td>
        <a href="/tag.php?t=<?php echo stripslashes($row['title']) ?>"><?php echo export_clean($row['title']) ?></a>
      </td>
      <td class="inr">
        <?php echo $row['count'] ?>
      </td>
    </tr>
  <?php } ?>

  <tr>
    <td class="title" colspan="2"><?php echo _('Bikes') ?></td>
  </tr>
  <?php foreach ($bike_list as $row) { ?>
    <tr>
      <td>
        <?php echo ucfirst(export_clean($row['make']))." ".export_clean($row['model']) ?>
      </td>
      <td class="inr">
        <?php echo $row['count'] ?>
      </td>
    </tr>
  <?php } ?>

  <tr>
    <td class="title" colspan="2"><?php echo _('Groups') ?></td>
  </tr>
  <?php foreach ($group_list as $row) { ?>
    <tr>
      <td>
        <a href="/group_view.php?gid=<?php echo $row['gid'] ?>">
          <?php echo export_clean($row['name']) ?></a>
      </td>
      <td class="inr">
        <?php echo $row['count'] ?>
      </td>
    </tr>
  <?php } ?>
</table>
