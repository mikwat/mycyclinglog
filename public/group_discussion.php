<?php
include_once("common/common.inc.php");

$sid = session_check();

$gid = $_REQUEST['gid'];
if (!is_numeric($gid)) {
  header("Location: discussion.php");
  exit();
}

$result = db_select('training_group', array('name' => 'text', 'description' => 'text', 'link' => 'text'), 'gid = '.db_quote($gid, 'integer'));
list($g_name, $g_desc, $g_link) = $result->fetch_row();
$result->close();

if ($sid) {
  $uid = $_SESSION['uid'];

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_form_key($_POST['form_key'])) {
    $title = $_POST['title'];
    if (empty($title)) {
      $GLOBALS['ERROR_MSG'][] = _('Title is required.');
    }

    $body = $_POST['body'];
    if (empty($body)) {
      $GLOBALS['ERROR_MSG'][] = _('Message is required.');
    }

    $mid = $_POST['mid'];
    $gid = $_POST['gid'];

    if (count($GLOBALS['ERROR_MSG']) == 0) {
      if (empty($mid)) {
        $values = array(
          'uid' => $uid,
          'gid' => $gid,
          'title' => $title,
          'body' => $body
        );
        $types = array(
          'integer',
          'integer',
          'text',
          'text'
        );

        db_insert('training_group_message', $values, $types);

        // Send notifications.
        /***********************
        $email_subject = $_SESSION['username'].' '._('has posted a message on My Cycling Log');
        $email_body = $_SESSION['username'].' '._('has posted a message to the').' '.$g_name.' ';
        $email_body .= _('discussion board.')."\n\n==========\n";
        $email_body .= $title."\n\n".$body."\n==========\n\n";
        $email_body .= _('To read this message, follow the link below:')."\n";
        $email_body .= 'http://'.MCL_DOMAIN.'/group_discussion.php?gid='.$gid;

        $result = db_select('training_user_group', array('uid' => 'integer'), "email_updates='Y' AND gid=".db_quote($gid, 'integer'));
        while ($notification_uid = $result->fetch_row()[0]) {
          if ($notification_uid != $uid) {
            create_notification($notification_uid, $email_subject, $email_body);
          }
        }
        $result->close();
        ************************/
      }
      else {
        $query = "SELECT 1 FROM training_group_message WHERE mid = ".db_quote($mid, 'integer')." AND uid = ".db_quote($uid, 'integer');
        $result = db_query($query);
        if ($uid == 1 || $result->num_rows == 1) {
          $values = array(
            'title' => $title,
            'body' => $body
          );
          $types = array(
            'text',
            'text'
          );

          db_update('training_group_message', $values, $types, 'mid = '.db_quote($mid, 'integer'));
          $GLOBALS['ERROR_MSG'][] = _('Message updated.');
        }
        $result->close();
      }
    } else {
      $edit = true;
    }
  }
}

$HEADER_TITLE = _('Group Discussion')." : ".stripslashes($g_name);
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="tbox">
  <tr>
    <td>
      <a href="/group_view.php?gid=<?php echo $gid ?>"><?php echo _('Group View') ?></a>
      |
      <a href="/group_detail.php?gid=<?php echo $gid ?>"><?php echo _('Recent Rides') ?></a>
      |
      <?php echo _('Discussion') ?>
      |
      <a href="/group_charts.php?gid=<?php echo $gid ?>"><?php echo _('Charts') ?></a>
    </td>
  </tr>
</table>
<?php
if ($sid) {
  if (is_numeric($_GET['mid'])) {
    $mid = $_GET['mid'];
    $result = db_select('training_group_message', array('mid' => 'integer', 'title' => 'text', 'body' => 'text'),
      'mid = '.db_quote($mid, 'integer').' AND gid = '.db_quote($gid, 'integer'));
    $m_row = $result->fetch_assoc();
    $result->close();

    $edit = true;
  }

  /*
   * Begin group members only.
   */
  $result = db_select('training_user_group', array('uid' => 'integer'), 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
  $is_member = $result->num_rows > 0;
  $result->close();
  if ($is_member) { ?>
    <script type="text/javascript">
    function showEdit() {
      show('edit_div');
    }
    function hideEdit() {
      <?php if ($edit === true) { ?>
        window.location.href = '/group_discussion.php?gid=<?php echo $gid ?>';
      <?php } else { ?>
        hide('edit_div');
      <?php } ?>
    }
    </script>

    <div id="edit_div" style="display: <?php echo ($edit === true)? 'block' : 'none' ?>">

    <form name="message_form" action="/group_discussion.php" method="POST">
    <input type="hidden" name="mid" value="<?php echo $m_row['mid'] ?>"/>
    <input type="hidden" name="gid" value="<?php echo $gid ?>"/>
    <input type="hidden" name="form_key" value="<?php echo make_form_key() ?>"/>
    <table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
      <tr>
        <td class="head">
          <?php
          if ($m_row) {
            echo _('Modify a message:');
          }
          else {
            echo _('Add a new message:');
          }
          ?>
        </td>
      </tr>
      <tr>
        <td class="title"><?php echo _('Title') ?>:</td>
      </tr>
      <tr>
        <td>
          <input type="text" name="title" size="25" class="formInput" value="<?php echo stripslashes($m_row['title']) ?>"/>
        </td>
      </tr>
      <tr>
        <td class="title"><?php echo _('Message') ?>:</td>
      </tr>
      <tr>
        <td>
          <textarea name="body" class="messageArea"><?php echo stripslashes($m_row['body']) ?></textarea>
        </td>
      </tr>
      <tr>
        <td>
          <input type="submit" value="<?php echo ($m_row)? _('SAVE') : _('ADD') ?>" class="btn"/>
          <b><a href="javascript:void(0)" onclick="hideEdit()"><?php echo _('Cancel') ?></a></b>
        </td>
      </tr>
      <?php if (is_error()) { ?>
        <tr><td><?php print_error() ?></td></tr>
      <?php } ?>
    </table>
    </form>

    </div>
  <?php
  }
}
/*
 * End group members only.
 */
$query = "
  SELECT COUNT(*)
  FROM training_group_message
  WHERE gid = ".db_quote($gid, 'integer');
$result = db_query($query);
$max = $result->fetch_row()[0];
$result->close();

$size = (isset($_GET['size']) && $_GET['size'] > 0)? $_GET['size'] : 10;
$start = (isset($_GET['start']) && $_GET['start'] > 0)? $_GET['start'] : 0;

$query = "
  SELECT
    m.mid,
    m.uid,
    ".SQL_NAME." AS name,
    u.username,
    u.location,
    m.title,
    m.body,
    m.entry_date
  FROM training_group_message m, training_user u
  WHERE m.uid = u.uid AND m.gid = ".db_quote($gid, 'integer')."
  ORDER BY entry_date DESC
  LIMIT ".db_quote($start, 'integer').", ".db_quote($size, 'integer');
$result = db_query($query);
?>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <table border="0" cellspacing="0" cellpadding="0" class="noborbox"><tr>
        <td class="head">
          <?php echo _('Group Discussion') ?>:
          <?php echo export_clean($g_name) ?>
        </td>
        <td width="4"><img src="/images/spacer.gif" width="4"/></td>
        <td>
          <a href="/rss/group_discussion.php?gid=<?php echo $gid ?>"><img src="/images/rss.png" border="0" width="14" height="14" align="middle" alt="RSS"/></a>
        </td>
      </tr></table>
    </td>
    <td class="inr">
      <?php if ($is_member) { ?>
        <a href="javascript:void(0)" onclick="showEdit()"><?php echo _('Add a New Message') ?> &raquo;</a>
      <?php } ?>
    </td>
  </tr>
  <?php
  $row_count = 0;
  while ($row = $result->fetch_assoc()) { ?>
    <tr <?php if ($row['uid'] == 1) { echo "class='highlight'"; } ?>>
      <td class="title">
        <?php if ($uid == 1 || $row['uid'] == $uid) { ?>
          <a href="/group_discussion.php?gid=<?php echo $gid ?>&mid=<?= $row['mid'] ?>"><?php echo datetime_format_nice($row['entry_date']) ?></a>
        <?php } else { ?>
          <?php echo datetime_format_nice($row['entry_date']) ?>:
        <?php } ?>
        <b><?php echo stripslashes($row['title']) ?></b>
      </td>
      <td class="titler">
        <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
        <span class="cgray"><?php echo export_clean($row['location']) ?></span>
      </td>
    </tr>
    <tr <?php if ($row['uid'] == 1) { echo "class='highlight'"; } ?>>
      <td colspan="2">
        <?php echo html_string_format($row['body']) ?>
      </td>
    </tr>
  <?php }
  $result->close();
  ?>
</table>

<table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="title" width="50%">
      <?php if ($start >= $size) { ?>
        <a href="<?php echo $_SERVER['PHP_SELF'].'?gid='.$gid.'&start=0' ?>"><?php echo _('Begin') ?></a> |
        <a href="<?php echo $_SERVER['PHP_SELF'].'?gid='.$gid.'&start='.($start - $size) ?>">&laquo; <?php echo _('Previous') ?></a>
      <?php } ?>
    </td>
    <td class="titler" width="50%">
      <?php if ($start + $size < $max) { ?>
        <a href="<?php echo $_SERVER['PHP_SELF'].'?gid='.$gid.'&start='.($start + $size) ?>"><?php echo _('Next') ?> &raquo;</a> |
        <a href="<?php echo $_SERVER['PHP_SELF'].'?gid='.$gid.'&start='.($max - $size) ?>"><?php echo _('End') ?></a>
      <?php } ?>
    </td>
  </tr>
</table>

</td>
</tr></table>

<?php include_once("common/footer.php"); ?>
