<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

$user_unit = $_SESSION['user_unit'];

if (!isset($user_list_show_footer)) {
  $user_list_show_footer = true;
}

$lid = false;
if (is_numeric($_GET['lid']) || is_numeric($_GET['lid'])) {
  $lid = ($_GET)? $_GET['lid'] : $_POST['lid'];
}

if (empty($rs_size) || !is_numeric($rs_size)) {
  $rs_size = 5;
}

if (is_numeric($_REQUEST['rs'])) {
  $rs = $_REQUEST['rs'];
} elseif (isset($HIGHLIGHT_LID) && $HIGHLIGHT_LID > 0) {
  $query = "
    SELECT COUNT(*)
    FROM training_log
    WHERE uid = ".db_quote($uid, 'integer')." AND event_date > (
      SELECT event_date
      FROM training_log
      WHERE lid = $HIGHLIGHT_LID
    )";
  $result = db_query($query);
  $rs = $result->fetch_row()[0];
  $result->close();
}
else {
  $rs = 0;
}

$where = "l.uid = ".db_quote($uid, 'integer');
if (!empty($_GET['rid'])) {
  $where .= " AND l.rid = ".db_quote($_GET['rid'], 'integer');
}

$rs_prev = $rs - $rs_size;
$rs_next = $rs + $rs_size;

$query = "
  SELECT COUNT(*)
  FROM training_log l
  WHERE ".$where;
$result = db_query($query);
$rs_max = $result->fetch_row()[0];
$result->close();

if ($rs_next >= $rs_max) {
  $rs_next = -1;
}

$query = "
  SELECT
    l.lid,
    l.event_date,
    l.distance,
    l.time,
    l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS avg_speed,
    l.notes,
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
    training_log l LEFT OUTER JOIN
    training_bike b ON l.bid = b.bid LEFT OUTER JOIN
    training_comment c ON l.lid = c.lid LEFT OUTER JOIN
    training_route r ON l.rid = r.rid
  WHERE ".$where."
  GROUP BY
    l.lid, l.event_date, l.distance, l.time, l.notes, l.heart_rate, l.max_speed, l.avg_cadence, l.weight,
    l.calories, l.elevation, l.is_ride, b.make, b.model
  ORDER BY l.event_date DESC, l.last_modified DESC
  LIMIT $rs, $rs_size";
$result = db_query($query);
?>
<script type="text/javascript">
function showDetails(lid) {
  var d = document.getElementById('details_'+lid);
  overlib(d.innerHTML, WIDTH, 135, LEFT);
}
</script>
<table border="0" cellspacing="0" cellpadding="4" class="inbox" width="100%">
  <tr>
    <td class="head"><?php echo _('Recent Rides') ?></td>
    <td class="head inr">
      <a href="/rss/user_recent.php?uid=<?php echo $uid ?>"><img src="/images/rss.png" border="0" width="14" height="14" align="middle" alt="RSS"/></a>
    </td>
  </tr>
  <?php
  while ($row = $result->fetch_assoc()) {
    $has_details = !empty($row['max_speed']) || !empty($row['heart_rate']) || !empty($row['avg_cadence']) || !empty($row['weight']) || !empty($row['elevation']) || !empty($row['calories']);
    ?>
    <tr <?php if ($HIGHLIGHT_LID == $row['lid']) { echo "class='highlight'"; } ?>>
      <td class="title">
        <table border="0" cellspacing="0" cellpadding="0" class="noborbox">
          <tr>
            <td>
              <a href="/add.php?lid=<?php echo $row['lid'] ?>&rs=<?php echo $rs ?>">
                <img src="images/icon_edit.gif" border="0" alt="<?php echo _('Edit') ?>"/></a>
            </td>
            <td><img src="/images/spacer.gif" width="2"/></td>
            <td>
              <div id="delete_<?php echo $row['lid'] ?>" style="display: none">
                <form action="/add.php" method="post">
                  <input type="hidden" name="r" value="1"/>
                  <input type="hidden" name="lid" value="<?php echo $row['lid'] ?>"/>
                  <input type="hidden" name="rs" value="<?php echo $rs ?>"/>
                  <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                    <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                    <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                  </tr></table>
                </form>
              </div>
              <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['lid'] ?>)"><img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
            </td>
            <td><img src="/images/spacer.gif" width="2"/></td>
            <td>
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
              echo "<a href='/ride_detail.php?uid=$uid&lid=".$row['lid']."'>$text</a>";
              ?>
            </td>
          </tr>
        </table>
      </td>
      <td class="title inr">
        <?php if ($has_details) { ?>
          <div id="details_<?php echo $row['lid'] ?>" style="display: none">
            <table width="100%" border="0" cellspacing="0" cellpadding="2" class="noborbox">
              <?php if (!empty($row['max_speed'])) { ?>
              <tr>
                <td class="title"><?php echo _('Max Speed') ?>:</td>
                <td><?php echo unit_format($row['max_speed'], $user_unit)?></td>
              </tr>
              <?php
              }
              if (!empty($row['heart_rate'])) { ?>
              <tr>
                <td class="title"><?php echo _('Heart Rate') ?>:</td>
                <td><?php echo export_clean($row['heart_rate']) ?></td>
              </tr>
              <?php
              }
              if (!empty($row['avg_cadence'])) { ?>
              <tr>
                <td class="title"><?php echo _('Avg Cadence') ?>:</td>
                <td><?php echo $row['avg_cadence'] ?></td>
              </tr>
              <?php
              }
              if (!empty($row['weight'])) { ?>
              <tr>
                <td class="title"><?php echo _('Weight') ?>:</td>
                <td><?php echo $row['weight'] ?></td>
              </tr>
              <?php
              }
              if (!empty($row['calories'])) { ?>
              <tr>
                <td class="title"><?php echo _('Calories') ?>:</td>
                <td><?php echo $row['calories'] ?></td>
              </tr>
              <?php
              }
              if (!empty($row['elevation'])) { ?>
              <tr>
                <td class="title"><?php echo _('Elevation') ?>:</td>
                <td><?php echo $row['elevation'] ?></td>
              </tr>
              <?php } ?>
            </table>
          </div>
          <a href="javascript:void(0)" onmouseover="return showDetails(<?php echo $row['lid'] ?>)" onmouseout="return nd()"><img src="images/icon_comments.gif" border="0" alt="<?php echo _('Details') ?>"/></a>
        <?php } else { ?>
          &nbsp;
        <?php } ?>
      </td>
    </tr>
    <tr <?php if ($HIGHLIGHT_LID == $row['lid']) { echo "class='highlight'"; } ?>>
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

        $t_query = "
          SELECT t.title
          FROM training_log_tag lt INNER JOIN training_tag t ON lt.tid = t.tid
            AND lt.lid = ".$row['lid'];
        $t_result = db_query($t_query);
        if ($t_result->num_rows > 0) {
          echo "<div>"._('Tags').":<span class='tag'>";
          while ($t = export_clean($t_result->fetch_assoc()['title'])) {
            echo "<a href='/tag.php?t=$t'>$t</a> ";
          }
          echo "</span></div>";
        }
        $t_result->close();

        if ($row['comments'] > 0) {
          echo "<div style='width: 100%; text-align: right'>";
          echo "<a href='/ride_detail.php?uid=$uid&lid=".$row['lid']."'>";
          echo sprintf(ngettext('%d Comment', '%d Comments', $row['comments']), $row['comments']);
          echo "</a></div>";
        }
        ?>
      </td>
    </tr>
    <?php if (empty($_GET['rid']) && !empty($row['rid'])) { ?>
      <tr <?php if ($HIGHLIGHT_LID == $row['lid']) { echo "class='highlight'"; } ?>>
        <td colspan="2">
          <img src="/images/globe.gif" width="16" height="16" alt="<?php echo _('Route') ?>" align="absmiddle"/>
          <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['route_name']) ?></a>
        </td>
      </tr>
    <?php } ?>
  <?php }
  $result->close();
  ?>
  <?php if ($user_list_show_footer === true) { ?>
  <tr>
    <td class="title">
      <?php echo ($rs + 1)." - ".($rs + (($rs_max < $rs_size)? $rs_max : $rs_size))." of ".$rs_max ?>
    </td>
    <?php
    $nav_link = $_SERVER['PHP_SELF']."?";
    if ($lid !== false) {
      $nav_link .= "lid=".$lid."&";
    }
    if (!empty($_GET['rid'])) {
      $nav_link .= "rid=".export_clean($_GET['rid'])."&";
    }
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
  <?php } ?>
</table>
