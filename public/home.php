<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$user_unit = $_SESSION['user_unit'];
$uid = $_SESSION['uid'];

$HEADER_TITLE = _('Home');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td style="width: 50%">
      <?php
      $query = "
        SELECT
          gr.grid,
          gr.note,
          g.name AS group_name,
          u.uid,
          ".SQL_NAME." AS name,
          u.username,
          u.location
        FROM
          training_group_request gr INNER JOIN
          training_user_group ug ON gr.gid = ug.gid INNER JOIN
          training_group g ON gr.gid = g.gid INNER JOIN
          training_user u ON gr.uid = u.uid
        WHERE
          ug.uid = ".db_quote($uid, 'integer')." AND
          ug.admin = 'Y' AND
          gr.status IS NULL";
      $result = db_query($query);
      if ($result->num_rows > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
          <tr>
            <td class="head" colspan="2">
              <img src="/images/alert.gif" alt="<?php echo _('Group Membership Requests') ?>" align="absmiddle"/>
              <?php echo _('Group Membership Requests') ?>
            </td>
          </tr>
          <tr>
            <td class="title"><?php echo _('Group') ?></td>
            <td class="title"><?php echo _('Requester') ?></td>
            <td class="title inr"><?php echo _('Action') ?></td>
          </tr>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td class="title"><?php echo $row['group_name'] ?></td>
              <td class="title">
                <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo $row['name'] ?>"><?php echo $row['username'] ?></a>
                <span class="cgray"><?php echo $row['location'] ?></span>
              </td>
              <td class="title inr">
                <a href="/group_join.php?form_type=group_response&grid=<?php echo $row['grid'] ?>&status=Accepted"><?php echo _('Accept') ?></a> |
                <a href="/group_join.php?form_type=group_response&grid=<?php echo $row['grid'] ?>&status=Denied"><?php echo _('Deny') ?></a>
              </td>
            </tr>
            <tr>
              <td colspan="3"><?php echo html_string_format($row['note']) ?></td>
            </tr>
          <?php } ?>
        </table>
      <?php }
      $result->close();
      ?>

      <?php
      $query = "SELECT COUNT(*) AS message_count FROM training_user_message WHERE to_uid = ".db_quote($uid, 'integer')." AND `read` = 'N'";
      $result = db_query($query);
      $new_messages = $result->fetch_assoc()['message_count'];
      if ($new_messages > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox" width="100%">
          <tr>
            <td class="head">
              <img src="/images/alert.gif" alt="<?php echo _('Alert') ?>" align="absmiddle"/>
              <a href="/mail.php"><?php echo _('New Mail') ?> &raquo;</a>
            </td>
          </tr>
        </table>
      <?php } ?>

      <?php
      $result = db_select('training_status', array('message' => 'text'), db_now().' BETWEEN start_date AND end_date');
      if ($result->num_rows > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox" width="100%">
          <tr>
            <td class="head">
              <img src="/images/alert.gif" alt="<?php echo _('System Notifications') ?>" align="absmiddle"/>
              <?php echo _('System Notifications') ?>
            </td>
          </tr>
        </table>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td>
                <?php echo $row['message'] ?>
              </td>
            </tr>
          <?php } ?>
        </table>
      <?php }
      $result->close();
      ?>

      <?php
      $result = db_select('training_event', array('eid' => 'integer', 'title' => 'text', 'link' => 'text'),
        'uid='.db_quote($uid, 'integer').' AND etid='.db_quote(RELATED_COMMENT, 'integer').' AND addressed='.db_quote('F', 'text'));
      if ($result->num_rows > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox" width="100%">
          <tr>
            <td class="head">
              <img src="/images/alert.gif" alt="<?php echo _('New Related Comments') ?>" align="absmiddle"/>
              <?php echo _('New Related Comments') ?>
            </td>
          </tr>
        </table>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="listbox" width="100%">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td>
                <?php echo "<a href='".$row['link']."&eid=".$row['eid']."'>".$row['title']."</a>"; ?>
              </td>
            </tr>
          <?php } ?>
        </table>
      <?php }
      $result->close();
      ?>

    </td>
    <td class="cell" style="width: 50%">
      <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
        <tr>
          <td class="head">Export Your Data</td>
        </tr>
      </table>
      <table align="center" border="0" cellspacing="0" cellpadding="4" class="listbox">
        <tr>
          <td>
            <ul>
              <li>
                <a href="/export/rides.php">Export Rides to CSV</a>
              </li>
              <li>
                <a href="/export/bikes.php">Export Bikes to CSV</a>
              </li>
              <li>
                <a href="/export/bike_service.php">Export Bike Service to CSV</a>
              </li>
              <li>
                <a href="/export/routes.php">Export Routes to CSV</a>
              </li>
              <li>
                <a href="/export/goals.php">Export Goals to CSV</a>
              </li>
              <li>
                <a href="/export/mail_received.php">Export Mail Received to CSV</a>
              </li>
              <li>
                <a href="/export/mail_sent.php">Export Mail Sent to CSV</a>
              </li>
            </ul>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <?php
      $group_recent_show_footer = false;
      $rs_size = 10;
      include("common/group_recent.php");
      ?>
    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
