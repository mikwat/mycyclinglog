<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$GLOBALS['ERROR_MSG'] = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_form_key($_POST['form_key'])) {
  $to_uid = $_POST['to_uid'];

  $result = db_select('training_user', array('email' => 'text'), 'uid='.db_quote($to_uid, 'integer'));
  if ($result->num_rows != 1) {
    $result->close();
    header("Location: mail.php");
    exit();
  }
  $to_user_row = $result->fetch_assoc();
  $result->close();

  $reply_mid = $_POST['reply_mid'];
  if (!empty($reply_mid)) {
    $result = db_select('training_user_message', array('title' => 'text'), 'mid='.db_quote($reply_mid, 'integer'));
    if ($result->num_rows != 1) {
      $result->close();
      header("Location: mail.php");
      exit();
    }
    $title = "Re: ".$result->fetch_assoc()["title"];
    $result->close();
  } else {
    $title = $_POST['title'];
    if (empty($title)) {
      $GLOBALS['ERROR_MSG'][] = _('Title is required.');
    }
  }

  $body = $_POST['body'];
  if (empty($body)) {
    $GLOBALS['ERROR_MSG'][] = _('Message is required.');
  }

  if (count($GLOBALS['ERROR_MSG']) == 0) {
    $values = array(
      'from_uid' => $uid,
      'to_uid' => $to_uid,
      'title' => $title,
      'body' => $body
    );
    $types = array(
      'integer',
      'integer',
      'text',
      'text'
    );

    db_insert('training_user_message', $values, $types);
    $mid = db_insert_id();

    // Send email.
    $email_subject = $_SESSION['username'].' '._('has sent you a message on My Cycling Log');
    $email_body = $_SESSION['username'].' '._('sent you a message.')."\n\n==========\n";
    $email_body .= $title."\n\n".$body."\n==========\n\n";
    $email_body .= _('To reply to this message, follow the link below:')."\n";
    $email_body .= 'https://'.MCL_DOMAIN.'/mail_view.php?mid='.$mid;
    $success = aws_send_mail($to_user_row['email'], $email_subject, $email_body);
    if (!$success) {
      error_log("Error sending mail notification email [To: ".$to_user_row['email']."]");
    }

    header("Location: mail_sent.php");
    exit();
  }
} else {
  $to_uid = $_GET['to_uid'];
  if (empty($to_uid)) {
    header("Location: mail.php");
    exit();
  }

  $reply_mid = $_GET['reply_mid'];
  if (!empty($reply_mid)) {
    $query = "SELECT 1 FROM training_user_message WHERE mid = ".db_quote($reply_mid, 'integer');
    $result = db_query($query);
    if ($result->num_rows == 0) {
      $result->close();
      // Message doesn't exist, can't reply to it.
      header("Location: mail.php");
      exit();
    }
    $result->close();
  }
}

$query = "
  SELECT username, ".SQL_NAME_NA." AS name, location
  FROM training_user
  WHERE uid = ".db_quote($to_uid, 'integer');
$result = db_query($query);
if ($result->num_rows == 0) {
  $result->close();
  header("Location: mail.php");
  exit();
}
$row = $result->fetch_assoc();
$result->close();

$HEADER_TITLE = _('Compose Mail');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

  <form name="message_form" action="/mail_new.php" method="POST">
  <input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
  <input type="hidden" name="to_uid" value="<?php echo $to_uid ?>"/>
  <input type="hidden" name="reply_mid" value="<?php echo $reply_mid ?>"/>
  <table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Compose Mail') ?>
      </td>
    </tr>
    <tr>
      <td class="title">
        <?php echo _('To') ?>:
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo $row['name'] ?>"><?php echo $row['username'] ?></a>
        <span class="cgray"><?php echo $row['location'] ?></span>
      </td>
    </tr>
    <tr>
      <td>
      </td>
    </tr>
    <?php if (empty($reply_mid)) { ?>
      <tr>
        <td class="title"><?php echo _('Title') ?>:</td>
      </tr>
      <tr>
        <td>
          <input type="text" name="title" size="25" class="formInput" value="<?php echo $_POST['title'] ?>"/>
        </td>
      </tr>
    <?php } ?>
    <tr>
      <td class="title"><?php echo _('Message') ?>:</td>
    </tr>
    <tr>
      <td>
        <textarea name="body" class="messageArea"><?php echo $_POST['body'] ?></textarea>
      </td>
    </tr>
    <tr>
      <td>
        <input type="submit" value="<?php echo _('SEND') ?>" class="btn"/>
        <b><a href="/mail.php"><?php echo _('Cancel') ?></a></b>
      </td>
    </tr>
    <?php if (is_error()) { ?>
      <tr><td><?php print_error() ?></td></tr>
    <?php } ?>
  </table>
  </form>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
