<?php
include_once("common/common.inc.php");

if (!is_numeric($_REQUEST['lid'])) {
  header("Location: index.php");
  exit();
}

$sid = session_check();
$user_unit = $_GET['user_unit'] ? $_GET['user_unit'] : $_SESSION['user_unit'];
$suid = $_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && empty($_GET['uid'])) {
  $result = db_select('training_log', array('uid' => 'integer'), 'lid='.db_quote($_GET['lid'], 'integer'));
  $uid = $result->fetch_assoc()['uid'];
  $result->close();
} else {
  $uid = ($_GET)? $_GET['uid'] : $_POST['uid'];
}
$lid = ($_GET)? $_GET['lid'] : $_POST['lid'];

$is_owner = false;
if ($suid == $uid) {
  $is_owner = true;
}

if ($sid && $_SERVER['REQUEST_METHOD'] == 'POST') {
  $cid = $_POST['cid'];
  $comment = $_POST['comment'];

  if (!empty($comment)) {
    if (!empty($cid)) {
      db_update('training_comment', array('comment' => $comment), array('text'), 'cid = '.db_quote($cid, 'integer').' AND uid = '.db_quote($suid, 'integer'));
    }
    else {
      $values = array(
        'lid' => $lid,
        'uid' => $suid,
        'comment' => $comment
      );
      $types = array(
        'integer',
        'integer',
        'text'
      );
      db_insert('training_comment', $values, $types);

      $query = '
        SELECT DISTINCT u.uid, u.email
        FROM training_comment c INNER JOIN training_user u ON c.uid = u.uid
        WHERE c.lid='.db_quote($lid, 'integer').' AND c.uid<>'.db_quote($suid, 'integer').' AND c.uid<>'.db_quote($uid, 'integer');
      $result = db_query($query);
      while ($comment_user = $result->fetch_assoc()) {
        $eid = create_event($comment_user['uid'], RELATED_COMMENT, truncate_string($comment, 128), "/ride_detail.php?uid=$uid&lid=$lid");
        email_comment($_SESSION['username'], $comment_user['email'], $comment, $uid, $lid, $eid);
      }
      $result->close();

      if ($uid != $suid) {
        $eid = create_event($uid, RELATED_COMMENT, truncate_string($comment, 128), "/ride_detail.php?uid=$uid&lid=$lid");
        $result = db_select('training_user', array('email' => 'text'), 'uid='.db_quote($uid, 'integer'));
        $to_email = $result->fetch_assoc()['email'];
        $result->close();
        email_comment($_SESSION['username'], $to_email, $comment, $uid, $lid, $eid);
      }
    }

    header("Location: ride_detail.php?uid=$uid&lid=$lid");
    exit();
  }
}
elseif (!empty($_GET['eid'])) {
  read_event($_GET['eid']);
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
    l.elevation,
    l.is_ride,
    CONCAT(b.make,' ',b.model) AS bike,
    l.rid,
    r.name AS route_name,
    r.url AS route_url,
    r.notes AS route_notes
  FROM
    training_log l LEFT OUTER JOIN
    training_bike b ON l.bid = b.bid LEFT OUTER JOIN
    training_route r ON l.rid = r.rid
  WHERE l.lid = ".db_quote($lid, 'integer');
$result = db_query($query);
$ride_row = $result->fetch_assoc();
$result->close();

// Build Facebook meta fields
$fb_title = 'Logged ';
if ($ride_row['distance'] > 0) {
  $fb_title .= unit_format($ride_row['distance'], $user_unit)." ".$user_unit;
} else {
  $fb_title .= _('unknown distance');
}
if (!empty($ride_row['time'])) {
  $fb_title .= " ".sprintf(_('in %s hours'), $ride_row['time']);
  $fb_title .= " ".sprintf(_('at %s %s/h'), unit_format($ride_row['avg_speed'], $user_unit), $user_unit);
}
$fb_title .= ' on My Cycling Log';
$fb_description = export_rss($ride_row['notes']);

$HEADER_TITLE = _('Ride Detail');
$HEADER_META = '<meta name="title" content="'.$fb_title.'" /><meta name="description" content="'.$fb_description.'" />';
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td width="50%">

  <table border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php
        $query = "
          SELECT
            ".SQL_NAME." AS name,
            u.username,
            u.location
          FROM training_user u
          WHERE u.uid = ".db_quote($uid, 'integer');
        $result = db_query($query);
        $row = $result->fetch_assoc();
        $result->close();
        ?>
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
      </td>
    </tr>
  </table>
  <table border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Ride Detail') ?>
      </td>
      <td class="inr">
        <?php if ($is_owner === true) { ?>
          <ul class="ride_buttons">
            <li>
              <a href="/add.php?lid=<?php echo $lid ?>&next_url=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>"><img src="/images/icon_edit.gif" border="0" alt="<?php echo _('Edit') ?>"/></a>
            </li>
          </ul>
        <?php } ?>
      </td>
    </tr>
    <?php
    $HIGHLIGHT_LID = $ride_row['lid'];
    ?>
    <tr>
      <td class="title">
        <?php
        echo date_format_nice($ride_row['event_date'])." - ";
        if ($ride_row['distance'] > 0) {
          echo unit_format($ride_row['distance'], $user_unit)." ".$user_unit;
        }
        else {
          echo _('Unknown distance');
        }

        if ($ride_row['is_ride'] == "T") {
          echo " ["._('Cycling')."]";
        }
        ?>
      </td>
      <td class="title inr">
        <?php if (!empty($ride_row['max_speed']) || !empty($ride_row['heart_rate']) || !empty($ride_row['avg_cadence']) ||
                  !empty($ride_row['weight']) || !empty($ride_row['elevation'])) { ?>
        <div id="details_<?php echo $ride_row['lid'] ?>" style="display: none">
          <table width="100%" border="0" cellspacing="0" cellpadding="2" class="noborbox">
            <?php if (!empty($ride_row['max_speed'])) { ?>
            <tr>
              <td class="title"><?php echo _('Max Speed') ?>:</td>
              <td><?php echo unit_format($ride_row['max_speed'], $user_unit)?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($ride_row['heart_rate'])) { ?>
            <tr>
              <td class="title"><?php echo _('Heart Rate') ?>:</td>
              <td><?php echo export_clean($ride_row['heart_rate']) ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($ride_row['avg_cadence'])) { ?>
            <tr>
              <td class="title"><?php echo _('Avg Cadence') ?>:</td>
              <td><?php echo $ride_row['avg_cadence'] ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($ride_row['weight'])) { ?>
            <tr>
              <td class="title"><?php echo _('Weight') ?>:</td>
              <td><?php echo $ride_row['weight'] ?></td>
            </tr>
            <?php } ?>
            <?php if (!empty($ride_row['elevation'])) { ?>
            <tr>
              <td class="title"><?php echo _('Elevation') ?>:</td>
              <td><?php echo $ride_row['elevation'] ?></td>
            </tr>
            <?php } ?>
          </table>
        </div>
        <a href="javascript:void(0)" onmouseover="return showDetails(<?php echo $ride_row['lid'] ?>)" onmouseout="return nd()">
          <img src="images/icon_comments.gif" border="0" alt="<?php echo _('Details') ?>"/></a>
        <?php } ?>
      </tr>
    </tr>
    <tr>
      <td colspan="2">
        <?php
        $output = "";
        if ($ride_row['distance'] > 0) {
          $output .= unit_format($ride_row['distance'], $user_unit)." ".$user_unit;
        }
        else {
          $output .= _('Unknown distance');
        }

        if (!empty($ride_row['time'])) {
          $output .= " ".sprintf(_('in %s hours'), $ride_row['time']);
        }

        if ($ride_row['avg_speed'] > 0) {
          $output .= " ".sprintf(_('at %s %s/h'), unit_format($ride_row['avg_speed'], $user_unit), $user_unit);
        }

        if (!empty($ride_row['bike'])) {
          $output .= " "._('on')." ".$ride_row['bike'];
        }

        if ($ride_row['is_ride'] == "T") {
          $output .= ". ["._('Cycling')."] ";
        }
        else {
          $output .= ". ";
        }

        echo export_clean($output);
        echo html_string_format($ride_row['notes']);

        $t_query = "
          SELECT t.title
          FROM training_log_tag lt INNER JOIN training_tag t ON lt.tid = t.tid
            AND lt.lid = ".db_quote($lid, 'integer');
        $t_result = db_query($t_query);
        if ($t_result->num_rows > 0) {
          echo "<div>"._('Tags').":<span class='tag'>";
          while ($t = export_clean($t_result->fetch_assoc()['title'])) {
            if ($sid) {
              echo "<a href='/tag.php?t=$t&s=me'>$t</a> ";
            }
            else {
              echo "<a href='/tag.php?t=$t'>$t</a> ";
            }
          }
          echo "</span></div>";
        }
        $t_result->close();
        ?>
      </td>
    </tr>
  </table>

  <?php if ($ride_row['rid']) { ?>
  <table border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Route') ?>
      </td>
    </tr>
    <?php if (!empty($ride_row['route_notes'])) { ?>
    <tr>
      <td>
        <?php echo html_string_format($ride_row['route_notes']) ?>
      </td>
    </tr>
    <?php } ?>
    <?php if (validate_route($ride_row['route_url'])) { ?>
    <tr>
      <td>
        <?php display_route($ride_row) ?>
      </td>
    </tr>
    <?php } ?>
  </tabel>
  <?php } ?>

  <table border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head" colspan="2">
        <?php echo _('Comments') ?>
      </td>
    </tr>
    <?php
    $query = "
      SELECT
        c.cid,
        c.uid,
        ".SQL_NAME." AS name,
        u.username,
        u.location,
        c.last_modified,
        c.comment
      FROM training_comment c INNER JOIN training_user u ON c.uid = u.uid
      WHERE c.lid = ".db_quote($lid, 'integer');
    $result = db_query($query);
    while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td class="title" width="50%">
          <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
          <span class="cgray"><?php echo export_clean($row['location']) ?></span>
        </td>
        <td class="title inr">
          <?php echo datetime_format_nice($row['last_modified']) ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <?php echo html_string_format($row['comment']) ?>
        </td>
      </tr>
    <?php }
    $result->close();
    ?>
    <tr>
      <td class="inr" colspan="2">
        <?php if ($sid) { ?>
          <a href="javascript:void(0)" onclick="showAdd()"><?php echo _('Add Comment') ?></a>
        <?php } else {
          echo sprintf(_('<a href="/index.php?next_url=%s">Login</a> to leave comments.'), urlencode($_SERVER['REQUEST_URI']));
        } ?>
      </td>
    </tr>
  </table>

  <script type="text/javascript">
  function showAdd() {
    show('add_div');
  }
  function hideAdd() {
    hide('add_div');
  }
  </script>
  <div id="add_div" style="display: none">
    <form name="comment_form" action="/ride_detail.php" method="POST">
    <input type="hidden" name="uid" value="<?php echo $uid ?>"/>
    <input type="hidden" name="lid" value="<?php echo $lid ?>"/>
    <table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
      <tr>
        <td class="title">
          <?php echo _('Add Comment') ?>
        </td>
      </tr>
      <tr>
        <td>
          <textarea name="comment" class="commentArea"></textarea>
        </td>
      </tr>
      <tr>
        <td>
          <input type="submit" value="<?php echo _('ADD') ?>" class="btn"/>
          <b><a href="javascript:void(0)" onclick="hideAdd()"><?php echo _('Cancel') ?></a></b>
        </td>
      </tr>
    </table>
    </form>
  </div>

</td><td class="cell">

  <?php include("common/user_recent.php"); ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
