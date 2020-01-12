<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$user_unit = $_SESSION['user_unit'];
$days = 30;
$rs_size = 5;

define('RECENT_RIDE_LIST', 'recent_ride_list');
define('RECENT_TAG_LIST', 'recent_tag_list');
define('RECENT_RS_MAX', 'recent_rs_max');

/* must use isset */
if (isset($_GET['rs']) && is_numeric($_GET['rs']) === true) {
  $rs = $_GET['rs'];
  $use_cache = false;
}
else {
  $rs = 0;
  $use_cache = true;
}

/*
 * Begin cache block.
 */
$ride_list = cache_get(RECENT_RIDE_LIST);
if ($use_cache && $ride_list !== false) {
  $tag_list = cache_get(RECENT_TAG_LIST);
  $rs_max = cache_get(RECENT_RS_MAX);
}
else {
  $query = "
    SELECT COUNT(*)
    FROM training_log
    WHERE event_date BETWEEN DATE_SUB(".db_now().", INTERVAL 7 DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY)";
  $result = db_query($query);
  $rs_max = $result->fetch_row()[0];

  $query = "
    SELECT
      u.uid,
      l.lid,
      l.event_date,
      ".SQL_NAME." AS name,
      u.username,
      u.location,
      l.time, l.distance, l.notes,
      l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS avg_speed,
      l.heart_rate,
      l.max_speed,
      l.avg_cadence,
      l.weight,
      l.calories,
      l.elevation,
      l.is_ride,
      b.bid,
      CONCAT(b.make,' ',b.model) AS bike,
      COUNT(c.cid) AS comments,
      l.rid,
      r.name AS route_name
    FROM
      training_log l INNER JOIN training_user u ON l.uid = u.uid LEFT OUTER JOIN
      training_bike b ON l.bid = b.bid LEFT OUTER JOIN
      training_comment c ON l.lid = c.lid LEFT OUTER JOIN
      training_route r ON l.rid = r.rid
    WHERE
      l.event_date BETWEEN DATE_SUB(".db_now().", INTERVAL ".db_quote($days, 'integer')." DAY) AND DATE_ADD(".db_now().", INTERVAL 1 DAY)
    GROUP BY
      u.uid, l.lid, l.event_date, u.first_name, u.last_name, u.username, u.location, l.distance, l.time, l.notes, l.heart_rate,
      l.max_speed, l.avg_cadence, l.weight, l.calories, l.elevation, l.is_ride, b.make, b.model
    ORDER BY l.event_date DESC, l.last_modified DESC
    LIMIT ".db_quote($rs, 'integer').", ".db_quote($rs_size, 'integer');
  $result = db_query($query);
  $ride_list = $result->fetch_all(MYSQLI_ASSOC);

  $tag_list = array();
  foreach ($ride_list as $row) {
    $t_query = "
      SELECT t.title
      FROM training_log_tag lt INNER JOIN training_tag t ON lt.tid = t.tid
        AND lt.lid = ".$row['lid'];
    $t_result = db_query($t_query);
    $tags = $t_result->fetch_all(MYSQLI_ASSOC);
    $tag_list[$row['lid']] = $tags;
  }

  if ($use_cache) {
    cache_save($ride_list, RECENT_RIDE_LIST);
    cache_save($tag_list, RECENT_TAG_LIST);
    cache_save($rs_max, RECENT_RS_MAX);
  }
}

$rs_prev = $rs - $rs_size;
$rs_next = $rs + $rs_size;

if ($rs_next >= $rs_max) {
  $rs_next = -1;
}
?>
<script type="text/javascript">
function showDetails(lid) {
  var d = document.getElementById('details_'+lid);
  overlib(d.innerHTML, WIDTH, -1);
}
</script>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Recent Rides') ?>
      <span class="cgray tah10"><?php echo sprintf(ngettext('Last %d day', 'Last %d days', $days), $days) ?></span>
    </td>
    <td class="head inr">
    	<a href="/rss/recent.php"><img src="/images/rss.png" border="0" width="14" height="14" align="middle" alt="RSS"/></a>
    </td>
  </tr>
  <?php foreach ($ride_list as $row) { ?>
    <tr>
      <td class="title">
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
      </td>
      <td class="title inr">
        <?php if (!empty($row['max_speed']) || !empty($row['heart_rate']) || !empty($row['avg_cadence']) || !empty($row['weight']) || !empty($row['elevation']) || !empty($row['calories'])) { ?>
        <div id="details_<?php echo $row['lid'] ?>" style="display: none">
          <table width="100%" border="0" cellspacing="0" cellpadding="2" class="noborbox">
            <?php if (!empty($row['max_speed'])) { ?>
            <tr>
              <td class="title"><?php echo _('Max Speed') ?>:</td>
              <td><?php echo unit_format($row['max_speed'], $user_unit)?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($row['heart_rate'])) { ?>
            <tr>
              <td class="title"><?php echo _('Heart Rate') ?>:</td>
              <td><?php echo export_clean($row['heart_rate']) ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($row['avg_cadence'])) { ?>
            <tr>
              <td class="title"><?php echo _('Avg Cadence') ?>:</td>
              <td><?php echo $row['avg_cadence'] ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($row['weight'])) { ?>
            <tr>
              <td class="title"><?php echo _('Weight') ?>:</td>
              <td><?php echo $row['weight'] ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($row['calories'])) { ?>
            <tr>
              <td class="title"><?php echo _('Calories') ?>:</td>
              <td><?php echo $row['calories'] ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($row['elevation'])) { ?>
            <tr>
              <td class="title"><?php echo _('Elevation') ?>:</td>
              <td><?php echo $row['elevation'] ?></td>
            </tr>
            <?php } ?>
          </table>
        </div>
        <a href="javascript:void(0)" onmouseover="return showDetails(<?php echo $row['lid'] ?>)" onmouseout="return nd()"><img src="images/icon_comments.gif" border="0" alt="<?php echo _('Details') ?>"/></a>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <?php
        $text = date_format_nice($row['event_date'])." - ";
        if ($row['distance'] > 0) {
          $text .= unit_format($row['distance'], $user_unit)." ".$user_unit;
        }
        else {
          $text .= _('Unknown distance');
        }

        if ($row['is_ride'] == "T") {
          $text .= " ["._('Cycling')."]";
        }

        echo "<a href='/ride_detail.php?uid=".$row['uid']."&lid=".$row['lid']."'>$text</a>";

        if (!empty($row['rid'])) { ?>
          <img src="/images/globe.gif" width="16" height="16" alt="<?php echo _('Route') ?>" align="absmiddle"/>
          <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['route_name']) ?></a>
        <?php } ?>
      </td>
    </tr>
    <tr>
    	<td colspan="2">
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

        if (!empty($row['bike'])) {
          $output .= " "._('on')." <a href='/bike_detail.php?bid=".$row['bid']."'>".export_clean($row['bike'])."</a>";
        }

        if ($row['is_ride'] == "T") {
          $output .= ". ["._('Cycling')."] ";
        }
        else {
          $output .= ". ";
        }

        echo $output;
        echo html_string_format($row['notes']);

        $tags = $tag_list[$row['lid']];
        if (!empty($tags)) {
          echo "<div>"._('Tags').":<span class='tag'>";
          foreach ($tags as $t) {
            $t = export_clean($t['title']);
            echo "<a href='/tag.php?t=$t'>$t</a> ";
          }
          echo "</span></div>";
        }

        echo "<div style='width: 100%; text-align: right'>";
        echo "<a href='/ride_detail.php?uid=".$row['uid']."&lid=".$row['lid']."'>";

        if ($row['comments'] == 0) {
          echo _('Add Comment');
        }
        else {
          echo sprintf(ngettext('%d Comment', '%d Comments', $row['comments']), $row['comments']);
        }

        echo "</a></div>";
        ?>
      </td>
    </tr>
  <?php } ?>
  <tr>
    <td class="title">
      <?php echo ($rs + 1)." - ".($rs + (($rs_max < $rs_size)? $rs_max : $rs_size))." of ".$rs_max ?>
    </td>
    <td class="title inr">
      <?php if ($rs_prev >= 0) { ?>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?rs=0"><?php echo _('Start') ?></a>
        |
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?rs=<?php echo $rs_prev ?>">&laquo; <?php echo _('Previous') ?></a>
      <?php } ?>
      <?php if ($rs_next >= 0) { ?>
        |
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?rs=<?php echo $rs_next ?>"><?php echo _('Next') ?> &raquo;</a>
      <?php } ?>
    </td>
  </tr>
</table>
