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
    ".SQL_NAME." AS to_name,
    u.username AS to_username,
    u.location AS to_location
  FROM
    training_user_message um INNER JOIN
    training_user u ON um.to_uid = u.uid
  WHERE
    um.mid = ".db_quote($mid, 'integer')." AND
    um.from_uid = ".db_quote($uid, 'integer');
$result = db_query($query);
if ($result->num_rows == 0) {
  $result->close();
  header("Location: mail.php");
  exit();
}
$row = $result->fetch_assoc();
$result->close();

$HEADER_TITLE = _('View Sent Mail');
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
        <?php echo _('View Sent Mail') ?>
      </td>
    </tr>
    <tr>
      <td class="title">
        <?php echo _('To') ?>:
      </td>
    </tr>
    <tr>
      <td>
        <a href="/profile/<?php echo urlencode($row['to_username']) ?>" title="<?php echo $row['to_name'] ?>"><?php echo $row['to_username'] ?></a>
        <span class="cgray"><?php echo $row['to_location'] ?></span>
      </td>
    </tr>
    <tr>
      <td class="title"><?php echo _('Date') ?>:</td>
    </tr>
    <tr>
      <td>
        <?php echo datetime_format_nice($row['entry_date']) ?>
    <tr>
      <td class="title"><?php echo _('Title') ?>:</td>
    </tr>
    <tr>
      <td>
        <?php echo html_string_format($row['title']) ?>
      </td>
    </tr>
    <tr>
      <td class="title"><?php echo _('Message') ?>:</td>
    </tr>
    <tr>
      <td>
        <?php echo html_string_format($row['body']) ?>
      </td>
    </tr>
  </table>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
