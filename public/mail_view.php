<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];

$mid = $_GET['mid'];
if (empty($mid)) {
  header("Location: mail.php");
  exit();
}

// fetch message
$query = "
  SELECT
    um.mid,
    um.title,
    um.body,
    um.entry_date,
    um.from_uid,
    um.`read`,
    ".SQL_NAME." AS from_name, 
    u.username AS from_username,
    u.location AS from_location
  FROM
    training_user_message um INNER JOIN
    training_user u ON um.from_uid = u.uid
  WHERE
    um.mid = ".db_quote($mid, 'integer')." AND
    um.to_uid = ".db_quote($uid, 'integer');
$result = db_query($query);
if ($result->num_rows == 0) {
  header("Location: mail.php");
  exit();
}
$row = $result->fetch_assoc();
$result->close();

// mark as read
if ($row['read'] == 'N') {
  db_update('training_user_message', array('read' => 'Y'), array('text'), 'mid='.db_quote($mid, 'integer'));
}

$HEADER_TITLE = _('View Mail');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

    <table align="center" width="100%" border="0" cellspacing="1" cellpadding="4" class="tbox">
      <tr>
        <td>
          <a href="/mail.php"><?php echo _('Inbox') ?></a> | <a href="/mail_sent.php"><?php echo _('Sent') ?></a>
        </td>
      </tr>
    </table>

  <table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('View Mail') ?>
      </td>
      <td class="inr">
        <a href="/mail_new.php?to_uid=<?php echo $row['from_uid'] ?>&amp;reply_mid=<?php echo $row['mid'] ?>"><?php echo _('Reply') ?></a>
      </td>
    </tr>
    <tr>
      <td class="title" colspan="2">
        <?php echo _('From') ?>:
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <a href="/profile/<?php echo urlencode($row['from_username']) ?>" title="<?php echo $row['from_name'] ?>"><?php echo $row['from_username'] ?></a>
        <span class="cgray"><?php echo $row['from_location'] ?></span>
      </td>
    </tr>
    <tr>
      <td class="title" colspan="2"><?php echo _('Date') ?>:</td>
    </tr>
    <tr>
      <td colspan="2">
        <?php echo datetime_format_nice($row['entry_date']) ?>
    <tr>
      <td class="title" colspan="2"><?php echo _('Title') ?>:</td>
    </tr>
    <tr>
      <td colspan="2">
        <?php echo html_string_format($row['title']) ?>
      </td>
    </tr>
    <tr>
      <td class="title" colspan="2"><?php echo _('Message') ?>:</td>
    </tr>
    <tr>
      <td colspan="2">
        <?php echo html_string_format($row['body']) ?>
      </td>
    </tr>
  </table>
  
    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
