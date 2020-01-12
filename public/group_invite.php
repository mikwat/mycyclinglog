<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$user_unit = $_SESSION['user_unit'];
$gid = $_REQUEST['gid'];
$uid = $_SESSION['uid'];

$ERROR_MSG = [];

if (empty($gid)) {
  header("Location: groups.php");
  exit();
}

$query = "
  SELECT name
  FROM training_group g INNER JOIN training_user_group ug ON g.gid = ug.gid
  WHERE g.gid = ".db_quote($gid, 'integer')."
    AND ug.uid = ".db_quote($uid, 'integer')."
    AND ug.admin = 'Y'";
$result = db_query($query);
if ($result->num_rows == 0) {
  header("Location: groups.php");
  exit();
} else {
  list($group_name) = $result->fetch_row();
}
$result->close();

if ($_POST['form_type'] == "invite") {
  $email = $_POST['email'];
  if (validate_email($email)) {
    $result = db_select('training_group', array('name' => 'text', 'password' => 'text'), 'gid = '.db_quote($gid, 'integer'));
    list($group_name, $password) = $result->fetch_row();

    $result = db_select('training_user', array('email' => 'text', 'first_name' => 'text', 'last_name' => 'text'), 'uid = '.db_quote($uid, 'integer'));
    list($from_email, $from_first, $from_last) = $result->fetch_row();

    $values = array(
      'uid' => $uid,
      'gid' => $gid,
      'email' => $email
    );
    $types = array(
      'integer',
      'integer',
      'text'
    );
    db_insert('training_invite', $values, $types);
    $iid = db_insert_id();

    $body = sprintf(_('You have been invited to join the %s group, please click the following link:'), $group_name)."
https://".MCL_DOMAIN."/?r=i&iid=$iid&g=$gid&p=".$password."

"._('This invitation was sent to you by')." ".$from_first." ".$from_last." <".$from_email.">. "._('Please do not reply to this message.')."

"._('Thank you').",
"._('The My Cycling Log Team');
    if (aws_send_mail($email, _('My Cycling Log Group Invitation'), $body)) {
      $ERROR_MSG[] = _('Invitation sent successfully.');
      unset($_POST['email']);
    }
    else {
      $ERROR_MSG[] = _('An error occured while sending invitation.');
    }
  }
  else {
    $ERROR_MSG[] = _('Email address invalid.');
  }
}

$query = "
  SELECT
    g.gid,
    g.name,
    g.password,
    g.description,
    g.link
  FROM
    training_group g INNER JOIN
    training_user_group ug ON g.gid = ug.gid
  WHERE
    ug.uid = ".db_quote($uid, 'integer')." AND
    ug.gid = ".db_quote($gid, 'integer')." AND
    ug.admin = 'Y'";
$result = db_query($query);
$g_row = array();
if ($result->num_rows > 0) {
  $g_row = $result->fetch_assoc();
}
$result->close();

$HEADER_TITLE = _('Group Invite');
include_once("common/header.php");
include_once("common/tabs.php");
?>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

  <form name="group_form" action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
  <input type="hidden" name="form_type" value="invite"/>
  <input type="hidden" name="gid" value="<?php echo $gid ?>"/>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head"><?php echo _('Invite to') ?> <a href="/group_view.php?gid=<?php echo $gid ?>"><?php echo $group_name ?></a></td>
    </tr>
    <tr>
      <td>
        <?php echo _('Send link:') ?>
        <a href="https://<?php echo MCL_DOMAIN ?>/?g=<?php echo $gid ?>&p=<?php echo export_clean($g_row['password']) ?>">
          https://<?php echo MCL_DOMAIN ?>/?g=<?php echo $gid ?>&p=<?php echo export_clean($g_row['password']) ?></a>

        <br/><?php echo _('OR') ?><br/>

        <?php echo _('Use the email form below:') ?>
      </td>
    </tr>
    <tr>
      <td class="title"><?php echo _('Email') ?>: *</td>
    </tr>
    <tr>
      <td>
        <input type="text" name="email" size="25" class="formInput" value="<?php echo stripslashes($_POST['email']) ?>"/>
      </td>
    </tr>
    <tr>
      <td><input type="submit" value="<?php echo _('INVITE') ?>" class="btn"/></td>
    </tr>
    <?php if ($_POST['form_type'] == "invite" && is_error()) { ?>
      <tr><td><?php print_error() ?></td></tr>
    <?php } ?>
  </table>
  </form>

    </td>
    <td width="50%" class="cell">

  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head" colspan="2">
        <?php echo _('Invitation History') ?>
      </td>
    </tr>
    <tr>
      <td class="title"><?php echo _('Email') ?></td>
      <td class="title"><?php echo _('Sent') ?></td>
      <td class="title"><?php echo _('Status') ?></td>
    </tr>
    <?php
    $query = "
      SELECT
        i.iid,
        i.sent_date,
        i.email,
        i.accepted,
        u.uid,
        ".SQL_NAME." AS name,
        u.username,
        u.location
      FROM
        training_invite i LEFT OUTER JOIN
        training_user u ON i.new_uid = u.uid
      WHERE
        i.uid = ".db_quote($uid, 'integer')." AND
        i.gid = ".db_quote($gid, 'integer')."
      ORDER BY i.sent_date";
    $result = db_query($query);
    if ($result->num_rows == 0) { ?>
      <tr><td colspan="2"><?php echo _('None') ?>.</td></tr>
    <?php }
    while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td>
          <?php echo export_clean($row['email']) ?>
        </td>
        <td>
          <?php echo datetime_format($row['sent_date']) ?>
        </td>
        <td>
          <?php
          if ($row['accepted'] == "N") {
            echo _('Outstanding');
          } else {
            echo _('Accepted').":<br/>";
            echo "<a href='/profile/".urlencode($row['username'])."' title='".$row['name']."'>".$row['username']."</a><br/>";
            echo "<span class='cgray'>".$row['location']."</span>";
          }
          ?>
        </td>
      </tr>
    <?php } ?>
  </table>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
