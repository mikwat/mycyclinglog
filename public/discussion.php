<?php
include_once("common/common.inc.php");

$sid = session_check();

if ($sid) {
  $uid = $_SESSION['uid'];

  $_SESSION['new_posts'] = false;

  $GLOBALS['ERROR_MSG'] = [];

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

    if (count($GLOBALS['ERROR_MSG']) == 0) {
      if (empty($mid)) {
        $values = array(
          'uid' => $uid,
          'title' => $title,
          'body' => $body
        );
        $types = array(
          'integer',
          'text',
          'text'
        );

        db_insert('training_message', $values, $types);
      }
      else {
        $query = "SELECT 1 FROM training_message WHERE mid = ".db_quote($mid, 'integer')." AND uid = ".db_quote($uid, 'integer');
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

          db_update('training_message', $values, $types, 'mid = '.db_quote($mid, 'integer'));
          $GLOBALS['ERROR_MSG'][] = "Message updated.";
        }
        $result->close();
      }
    }
  }
}

$HEADER_TITLE = _('Discussion');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

<?php
if ($sid) {
  $query = "
    SELECT g.gid, g.name, g.description, MAX(gm.entry_date) AS last_entry
    FROM
      training_group g INNER JOIN training_user_group ug ON g.gid = ug.gid LEFT OUTER JOIN
      training_group_message gm ON g.gid = gm.gid
    WHERE ug.uid = ".db_quote($uid, 'integer')."
    GROUP BY g.gid, g.name, g.description";
  $result = db_query($query);
  if ($result->num_rows > 0) { ?>
    <table align="center" width="100%" border="0" cellspacing="1" cellpadding="4" class="tbox">
      <tr>
        <td>
          <?php echo _('Group Discussion Boards') ?>:
          <?php
          $first = true;
          while ($g_row = $result->fetch_assoc()) {
            if ($first === false) { ?> | <?php } ?>
            <a href="/group_discussion.php?gid=<?php echo $g_row['gid'] ?>"><?php echo export_clean($g_row['name']) ?></a>
            <?php
            $first = false;
            if (!empty($_SESSION['last_login']) && !empty($g_row['last_entry'])) {
              $entry_date = strtotime($g_row['last_entry']);
              $login_date = strtotime($_SESSION['last_login']);
              if ($entry_date > $login_date) { ?>
                <img src="/images/alert.gif" alt="<?php echo _('New Posts') ?>" align="absmiddle"/>
              <?php }
            }
          } ?>
        </td>
      </tr>
    </table>
  <?php
  }
  $result->close();
}

if ($sid && $uid == 1) {
  if (is_numeric($_GET['mid'])) {
    $mid = $_GET['mid'];
    $result = db_select('training_message', array('mid' => 'integer', 'title' => 'text', 'body' => 'text'), 'mid = '.db_quote($mid, 'integer'));
    $m_row = $result->fetch_assoc();
    $result->close();

    $edit = true;
  }
  ?>
  <script type="text/javascript">
  function showEdit() {
    show('edit_div');
  }
  function hideEdit() {
    <?php if ($edit === true) { ?>
      window.location.href = '/discussion.php';
    <?php } else { ?>
      hide('edit_div');
    <?php } ?>
  }
  </script>

  <div id="edit_div" style="display: <?php echo ($edit === true)? 'block' : 'none' ?>">

  <form name="message_form" action="/discussion.php" method="POST">
  <input type="hidden" name="mid" value="<?php echo $m_row['mid'] ?>"/>
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
        <input type="submit" value="<?= ($m_row)? _('SAVE') : _('ADD') ?>" class="btn"/>
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

$query = "
  SELECT COUNT(*)
  FROM training_message";
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
  FROM training_message m, training_user u
  WHERE m.uid = u.uid AND m.removed = 0
  ORDER BY entry_date DESC
  LIMIT ".db_quote($start, 'integer').", ".db_quote($size, 'integer');
$result = db_query($query);
?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <table border="0" cellspacing="0" cellpadding="0" class="noborbox"><tr>
        <td class="head"><?php echo _('General Discussion') ?></td>
        <td width="4"><img src="/images/spacer.gif" width="4"/></td>
        <td>
          <a href="/rss/discussion.php"><img src="/images/rss.png" border="0" width="14" height="14" align="middle" alt="RSS"/></a>
        </td>
      </tr></table>
    </td>
    <td class="inr">
      <?php if ($sid && $uid == 1) { ?>
        <a href="javascript:void(0)" onclick="showEdit()"><?php echo _('Add a New Message') ?> &raquo;</a>
      <?php } ?>
    </td>
  </tr>
<?php
$row_count = 0;
while ($row = $result->fetch_assoc()) { ?>
  <?php $row_count++ ?>
  <tr <?php if ($row['uid'] == 1 && $row_count == 1) { echo "class='highlight'"; } ?>>
    <td class="title">
      <?php if ($row['uid'] == $uid) { ?>
        <a href="/discussion.php?mid=<?php echo $row['mid'] ?>"><?php echo datetime_format_nice($row['entry_date']) ?></a>
      <?php } else { ?>
        <?php echo datetime_format_nice($row['entry_date']) ?>:
      <?php } ?>

      <?php if ($uid == 1) { ?>
        [
        <a href="/discussion.php?mid=<?php echo $row['mid'] ?>">Edit</a> |
        <a href="/private/moderate.php?form_type=delete_message&mid=<?php echo $row['mid'] ?>">Remove</a> |
        <a href="/private/moderate.php?form_type=ban_message&mid=<?php echo $row['mid'] ?>">Ban</a>
        ]
      <?php } ?>

      <b><?php echo export_clean($row['title']) ?></b>
    </td>
    <td class="titler">
      <a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a>
      <span class="cgray"><?php echo export_clean($row['location']) ?></span>
    </td>
  </tr>
  <tr <?php if ($row['uid'] == 1 && $row_count == 1) { echo "class='highlight'"; } ?>>
    <td colspan="2">
      <?php echo html_string_format($row['body']) ?>
    </td>
  </tr>
<?php
}
$result->close();
?>
</table>

<table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="title" width="50%">
      <?php if ($start >= $size) { ?>
        <a href="<?php echo $_SERVER['PHP_SELF'].'?start=0' ?>"><?php echo _('Begin') ?></a> |
        <a href="<?php echo $_SERVER['PHP_SELF'].'?start='.($start - $size) ?>">&laquo; <?php echo _('Previous') ?></a>
      <?php } ?>
    </td>
    <td class="titler" width="50%">
      <?php if ($start + $size < $max) { ?>
        <a href="<?php echo $_SERVER['PHP_SELF'].'?start='.($start + $size) ?>"><?php echo _('Next') ?> &raquo;</a> |
        <a href="<?php echo $_SERVER['PHP_SELF'].'?start='.($max - $size) ?>"><?php echo _('End') ?></a>
      <?php } ?>
    </td>
  </tr>
</table>

</td>
</tr></table>

<?php include_once("common/footer.php"); ?>
