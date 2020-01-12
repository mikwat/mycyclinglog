<?php
if ($_POST['t']) {
  $url = "tag.php?t=".$_POST['t'];
  if ($_POST['tuid']) {
    $url .= "&tuid=".$_POST['tuid'];
  }
  elseif ($_POST['s']) {
    $url .= "&s=".$_POST['s'];
  }

  header("Location: $url");
}

include_once("common/common.inc.php");

$sid = session_check();
$user_unit = $_SESSION['user_unit'];

$tuid = $_GET['tuid'];

$HEADER_TITLE = _('Tags');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<form name="tag_form" action="/tag.php" method="POST">
<input type="hidden" name="form_type" value="search"/>
<input type="hidden" name="tuid" value="<?php echo $tuid ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Search Tags') ?></td>
  </tr>
  <tr>
    <td>
      <input type="text" name="t" size="35" class="formInput" value="<?php echo stripslashes($_GET['t']) ?>"/>
    </td>
  </tr>
  <?php if ($sid || $tuid) { ?>
  <tr>
    <td>
      <?php if ($sid) { ?>
        <?php echo _('Me') ?>:
        <input type="radio" name="s" value="me" <?php if ($_GET['s'] == 'me') echo "checked" ?> />
      <?php } elseif ($tuid) {
        $result = db_select('training_user', array('username' => 'text', 'location' => 'text'), 'uid = '.db_quote($tuid, 'integer'));
        $row = $result->fetch_assoc();
        $result->close();
        echo $row['username'];
        echo " <span class='cgray'>".$row['location']."</span>";
        ?>
        <input type="radio" name="s" value="me" <?php if ($_GET['s'] == 'me') echo "checked" ?> />
      <?php } ?>
      <?php echo _('Others') ?>:
      <input type="radio" name="s" value="all" <?php if (empty($_GET['s']) || $_GET['s'] == 'all') echo "checked" ?> />
    </td>
  </tr>
  <?php } ?>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('SEARCH') ?>" class="btn"/>
    </td>
  </tr>
</table>
</form>

</td>
<td width="50%" class="cell">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Results') ?>
    </td>
  </tr>
    <?php
    if (!empty($_GET['t'])) {
      $t = $_GET['t'];
      $s = $_GET['s'];

      $t = preg_replace("/(\\\|\'|\")+/", " ", stripslashes($t));
      $q_list = explode(" ", $t);

      /*
       * Tags by tag.
       */
      $where_clause = "";
      if ($tuid) {
        $where_clause = "u.uid = ".db_quote($tuid, 'integer');
      }
      elseif ($sid) {
        $uid = $_SESSION['uid'];
        if ($s == "me") {
          $where_clause = "u.uid = ".db_quote($uid, 'integer');
        }
        else {
          $where_clause = "u.uid <> ".db_quote($uid, 'integer');
        }
      }

      if (count($q_list) > 0) {
        if (!empty($where_clause)) {
          $where_clause .= " AND (";
        }
        else {
          $where_clause .= " (";
        }

        $first = true;
        foreach ($q_list as $term) {
          if (!$first && !empty($where_clause)) {
            $where_clause .= " OR ";
          }

          $where_clause .= "(t.title = ".db_quote($term, 'text').")";
          $first = false;
        }

        $where_clause .= ")";
      }

      /*
       * Summary
       */
      $query = "
        SELECT
          SUM(l.distance) AS distance,
          SUM(l.distance) / (SUM(TIME_TO_SEC(l.time)) / 3600.0) AS avg_speed,
          COUNT(l.lid) AS rides
        FROM
          training_log l INNER JOIN
          training_log_tag lt ON l.lid = lt.lid INNER JOIN
          training_tag t ON lt.tid = t.tid INNER JOIN
          training_user u ON l.uid = u.uid
        WHERE $where_clause";
      $result = db_query($query);
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        ?>
        <tr>
          <td class="title"><?php echo _('Total Distance') ?>:</td>
          <td class="title inr"><?php echo unit_format($row['distance'], $user_unit)." ".$user_unit; ?></td>
        </tr>
        <tr>
          <td class="title"><?php echo _('Total Avg Speed') ?>:</td>
          <td class="title inr"><?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h'); ?></td>
        </tr>
        <tr>
          <td class="title"><?php echo _('Total Entries') ?>:</td>
          <td class="title inr"><?php echo $row['rides'] ?></td>
        </tr>
      <?php
      }
      $result->close();

      /*
       * List
       */
      $query = "
        SELECT
          DISTINCT
          u.uid,
          u.username,
          l.lid,
          l.event_date,
          l.distance,
          l.is_ride,
          l.last_modified
        FROM
          training_log l INNER JOIN training_log_tag lt ON l.lid = lt.lid INNER JOIN
          training_tag t ON lt.tid = t.tid INNER JOIN
          training_user u ON l.uid = u.uid
        WHERE $where_clause
        ORDER BY l.last_modified DESC
        LIMIT 0, 30";
      $result = db_query($query);
      if ($result->num_rows == 0) { ?>
        <tr><td colspan="2"><?php echo _('No entries found.') ?></td></tr>
      <?php
      }
      else {
        ?>
        <tr>
          <td class="title" colspan="2"><?php echo _('Last 30 Entries') ?></td>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) { ?>
          <tr><td colspan="2">
            <?php
            $text = $row['username']." - ";
            $text .= date_format_nice($row['event_date'])." - ";
            if ($row['distance'] > 0) {
              $text .= unit_format($row['distance'], $user_unit)." ".$user_unit;
            }
            else {
              $text .= _('Unknown distance');
            }

            if ($row['is_ride'] == "T") {
              $text .= " ["._('Cycling')."]";
            }

            echo "<a href='/ride_detail.php?uid=".$row['uid']."&lid=".$row['lid']."'>".export_clean($text)."</a>";
            ?>
          </td></tr>
          <?php
        }
        $result->close();
      }
    }
    else { ?>
      <tr><td colspan="2"><?php echo _('Enter a search.') ?></td></tr>
    <?php } ?>
  <?php if (is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
