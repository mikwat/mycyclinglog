<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$user_unit = $_SESSION['user_unit'];
$gid = ($_POST)? $_POST['gid'] : $_GET['gid'];
$uid = $_SESSION['uid'];

$ERROR_MSG = [];

if ($_POST['form_type'] == "leave") {
  db_delete('training_user_group', 'uid = '.db_quote($uid, 'integer').' AND gid = '.db_quote($gid, 'integer'));
  header("Location: groups.php");
  exit();
}

$group_admin = false;
if (!empty($gid)) {
  $query = "
    SELECT 1
    FROM training_group g INNER JOIN training_user_group ug ON g.gid = ug.gid
    WHERE g.gid = ".db_quote($gid, 'integer')."
      AND ug.uid = ".db_quote($uid, 'integer')."
      AND ug.admin = 'Y'";
  $result = db_query($query);
  if ($result->num_rows == 1) {
    $group_admin = true;
  } else {
    header("Location: groups.php");
    exit();
  }
  $result->close();
}

if ($_POST['form_type'] == "group_delete") {
  if (!empty($gid) && $group_admin === true) {
    $where_clause = 'gid = '.db_quote($gid, 'integer');
    db_delete('training_group', $where_clause);
    db_delete('training_user_group', $where_clause);

    header("Location: groups.php");
    exit();
  }
}
elseif ($_POST['form_type'] == "group_edit") {
  $group_name = $_POST['name'];
  if (empty($group_name)) {
    $ERROR_MSG[] = _('Group name is required.');
  }

  $password = $_POST['password'];
  if (empty($password)) {
    $ERROR_MSG[] = _('Group password is required.');
  }

  $description = $_POST['description'];
  $link = $_POST['link'];

  if (count($ERROR_MSG) == 0) {
    if (empty($gid)) {
      $values = array(
        'name' => $group_name,
        'password' => $password,
        'description' => $description,
        'link' => $link
      );
      $types = array(
        'text',
        'text',
        'text',
        'text'
      );

      db_insert('training_group', $values, $types);
      if (count($ERROR_MSG) == 0) {
        $gid = db_insert_id();

        $values = array(
          'uid' => $uid,
          'gid' => $gid,
          'admin' => 'Y'
        );
        $types = array(
          'integer',
          'integer',
          'text'
        );

        db_insert('training_user_group', $values, $types);
        $ERROR_MSG[] = _('New group added.');
      }
    }
    elseif ($group_admin === true) {
      $values = array(
        'name' => $group_name,
        'password' => $password,
        'description' => $description,
        'link' => $link
      );
      $types = array(
        'text',
        'text',
        'text',
        'text'
      );

      db_update('training_group', $values, $types, 'gid = '.db_quote($gid, 'integer'));

      $ERROR_MSG[] = _('Group updated.');
    }
  }
}
elseif ($_POST['form_type'] == "user_delete") {
  $m_uid = $_POST['uid'];
  if (!empty($gid) && !empty($m_uid) && $group_admin === true) {
    db_delete('training_user_group', 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($m_uid, 'integer').' AND admin != '.db_quote('Y', 'text'));
    if (count($ERROR_MSG) == 0) {
      $ERROR_MSG[] = _('User removed from group.');
    }
  }
}

if (!empty($gid)) {
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
}

$HEADER_TITLE = (!empty($gid))? _('Edit Group') : _('Add Group');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<script type="text/javascript">
function doDelete(uid) {
  var d = document.getElementById('delete_'+uid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
function doGroupDelete() {
  var d = document.getElementById('group_delete');
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<form name="group_form" action="/group_edit.php" method="POST">
<input type="hidden" name="form_type" value="group_edit"/>
<input type="hidden" name="gid" value="<?php echo $gid ?>"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo (!empty($gid))? _('Edit Group') : _('Add Group') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="name" size="25" class="formInput" value="<?php echo stripslashes($g_row['name']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Password') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="password" size="25" class="formInput" value="<?php echo stripslashes($g_row['password']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Link') ?>:</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="link" size="25" class="formInput" value="<?php echo stripslashes($g_row['link']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Description') ?>:</td>
  </tr>
  <tr>
    <td>
      <textarea name="description" class="formArea"><?php echo stripslashes($g_row['description']) ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="title">
      <input type="submit" value="<?php echo ($gid)? _('UPDATE') : _('ADD') ?>" class="btn"/>
      <a href="/groups.php"><?php echo _('Cancel') ?></a>
    </td>
  </tr>
  <?php if ($_POST['form_type'] == "group_edit" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

		</td>
		<td width="50%" class="cell">

<?php if ($gid) { ?>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head" colspan="2"><?php echo _('Remove Group Members') ?></td>
    </tr>
    <?php
    $query = "
      SELECT
        u.uid,
        ".SQL_NAME." AS name,
        u.username,
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
        ug.admin
      FROM
        training_user u INNER JOIN training_user_group ug ON u.uid = ug.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' AND (YEAR(l.event_date) = YEAR(".db_now().") OR l.event_date IS NULL) LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE ug.gid = ".db_quote($gid, 'integer')."
      GROUP BY u.uid
      ORDER BY distance DESC";
    $result = db_query($query);
    while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="0" class="noborbox">
          <tr>
            <td><a href="/profile/<?php echo urlencode($row['username']) ?>" title="<?php echo export_clean($row['name']) ?>"><?php echo export_clean($row['username']) ?></a></td>
            <td width="2"><img src="/images/spacer.gif" width="2"/></td>
            <td>
              <?php if ($row['admin'] == 'N') { ?>
              <div id="delete_<?php echo $row['uid'] ?>" style="display: none">
                <form action="/group_edit.php" method="post">
                  <input type="hidden" name="form_type" value="user_delete"/>
                  <input type="hidden" name="uid" value="<?php echo $row['uid'] ?>"/>
                  <input type="hidden" name="gid" value="<?php echo $gid ?>"/>
                  <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                    <td><input type="submit" value="<?php echo _('REMOVE') ?>" class="btn"/></td>
                    <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                  </tr></table>
                </form>
              </div>
              <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['uid'] ?>)"><img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
              <?php } else { ?>
                <span class="cgray">(<?php echo _('Group Admin') ?>)</span>
              <?php } ?>
            </td>
          </tr>
        </table>
      </td>
      <td class="inr">
        <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
        <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
      </td>
    </tr>
  <?php
  }
  $result->close();
  ?>
  <?php if ($_POST['form_type'] == "user_delete" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
  </table>

  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head" colspan="2">
        <?php echo _('Delete Group') ?>
      </td>
    </tr>
    <tr>
      <td><span class="cgray"><?php echo _('Are you sure you want to delete this group?  This action cannot be undone.') ?></span></td>
    </tr>
    <tr>
      <td class="title">
        <div id="group_delete" style="display: none">
          <form action="/group_edit.php" method="post">
            <input type="hidden" name="form_type" value="group_delete"/>
            <input type="hidden" name="gid" value="<?php echo $gid ?>"/>
            <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
              <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
              <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
            </tr></table>
          </form>
        </div>
        <input type="button" value="<?php echo _('DELETE') ?>" class="btn" onclick="doGroupDelete()"/>
      </td>
    </tr>
  </table>
<?php } ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
