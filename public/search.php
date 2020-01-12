<?php
if ($_POST['q']) {
  header("Location: search.php?q=".urlencode($_POST['q']));
  exit();
}

include_once("common/common.inc.php");

session_check();
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = _('Search');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<form name="search_form" action="/search.php" method="POST">
<input type="hidden" name="form_type" value="search"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Search Users and Groups') ?></td>
  </tr>
  <tr>
    <td>
      <input type="text" name="q" size="35" class="formInput" value="<?php echo htmlspecialchars($_GET['q']) ?>"/>
    </td>
  </tr>
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
  if (!empty($_GET['q'])) {
    $q = preg_replace("/(\\\|\'|\")+/", " ", stripslashes($_GET['q']));
    $q_list = explode(" ", $q);

    /*
     * Users.
     */
    $like_clause = "";
    foreach ($q_list as $term) {
      if (!empty($like_clause)) {
        $like_clause .= " AND ";
      }
      $like_clause .= "(
        first_name LIKE ".db_quote("%$term%", 'text')." OR
        last_name LIKE ".db_quote("%$term%", 'text')." OR
        username LIKE ".db_quote("%$term%", 'text')." OR
        location LIKE ".db_quote("%$term%", 'text').")";
    }

    $query = "
      SELECT uid, ".SQL_NAME_NA." AS name, username, location
      FROM training_user
      WHERE $like_clause
      LIMIT 0, 30";
    $result = db_query($query);
    if ($result->num_rows == 0) { ?>
      <tr><td colspan="2"><?php echo _('No users found.') ?></td></tr>
    <?php } else { ?>
      <tr><td class="title" colspan="2"><?php echo _('Users') ?></td></tr>
      <?php
      while ($row = $result->fetch_assoc()) { ?>
        <tr><td colspan="2">
          <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
          <span class="clight"><?php echo export_clean($row['location']) ?></span>
        </td></tr>
        <?php
      }
    }
    $result->close();

    /*
     * Groups.
     */
    $like_clause = "";
    foreach ($q_list as $term) {
      if (!empty($like_clause)) {
        $like_clause .= " AND ";
      }
      $like_clause .= "(name LIKE ".db_quote("%$term%", 'text')." OR description LIKE ".db_quote("%$term%", 'text').")";
    }

    $query = "
      SELECT gid, name, description
      FROM training_group
      WHERE $like_clause
      LIMIT 0, 30";
    $result = db_query($query);
    if ($result->num_rows == 0) {
      ?>
      <tr><td colspan="2"><?php echo _('No groups found.') ?></td></tr>
      <?php
    } else {
      ?>
      <tr><td class="title" colspan="2"><?php echo _('Groups') ?></td></tr>
      <?php
      while ($row = $result->fetch_assoc()) { ?>
        <tr><td colspan="2">
          <a href="group_view.php?gid=<?php echo $row['gid'] ?>"><?php echo export_clean($row['name']) ?></a>
          <span class="clight"><?php echo truncate_string(export_clean($row['description']), 50) ?></span>
        </td></tr>
        <?php
      }
    }
    $result->close();

    /*
     * Tags by tag.
     */
    $like_clause = "";
    foreach ($q_list as $term) {
      if (!empty($like_clause)) {
        $like_clause .= " AND ";
      }
      $like_clause .= "(t.title LIKE ".db_quote("%$term%", 'text')." OR l.notes LIKE ".db_quote("%$term%", 'text').")";
    }

    $query = "
      SELECT
        u.uid,
        u.username,
        l.lid,
        l.event_date,
        l.distance,
        l.is_ride
      FROM
        training_log l INNER JOIN training_log_tag lt ON l.lid = lt.lid INNER JOIN
        training_tag t ON lt.tid = t.tid INNER JOIN
        training_user u ON l.uid = u.uid
      WHERE $like_clause
      LIMIT 0, 30";
    $result = db_query($query);
    if ($result->num_rows == 0) { ?>
      <tr><td colspan="2"><?php echo _('No rides found.') ?></td></tr>
      <?php
    } else {
      ?>
      <tr><td class="title" colspan="2"><?php echo _('Rides by Tag') ?></td></tr>
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
    }
    $result->close();
  } else { ?>
    <tr><td colspan="2"><?php echo _('Enter a search.') ?></td></tr>
  <?php } ?>
  <?php if (is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
