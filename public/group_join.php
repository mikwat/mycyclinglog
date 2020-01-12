<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$ERROR_MSG = [];

if ($_POST['form_type'] == "group_join" || $_GET['form_type'] == "group_join") {
  $gid = $_REQUEST['gid'];
  if (empty($gid)) {
    $ERROR_MSG[] = _('You must choose a group.');
  }

  $password = $_REQUEST['password'];
  if (empty($password)) {
    $ERROR_MSG[] = _('You must enter a password.');
  }

  if (count($ERROR_MSG) == 0) {
    /*
     * If already in group, ignore request.
     */
    $result = db_select('training_user_group', array('gid' => 'integer'), 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
    if ($result->num_rows == 0) {
      $result->close();
      $result = db_select('training_group', array('name' => 'text'), 'gid = '.db_quote($gid, 'integer').' AND password = '.db_quote($password, 'text'));

      if ($result->num_rows == 1) {
        $values = array(
          'uid' => $uid,
          'gid' => $gid
        );
        $types = array(
          'integer',
          'integer'
        );
        db_insert('training_user_group', $values, $types);

        header("Location: groups.php");
        exit();
      }
      else {
        $ERROR_MSG[] = _('Invalid group password.');
      }
    }
    else {
      $ERROR_MSG[] = _('You are already a member of the group.');
    }
  }
} elseif ($_POST['form_type'] == "group_request") {
  $gid = $_POST['gid'];
  if (empty($gid)) {
    $ERROR_MSG[] = _('You must choose a group.');
  }

  $note = $_POST['note'];

  if (count($ERROR_MSG) == 0) {
    /*
     * If already in group, ignore request.
     */
    $result = db_select('training_user_group', array('gid' => 'integer'), 'gid = '.db_quote($gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
    if ($result->num_rows == 0) {
      $result->close();
      /*
       * If there is already an outstanding request, ignore.
       */
      $where = 'uid = '.db_quote($uid, 'integer').' AND gid = '.db_quote($gid, 'integer').' AND status IS NULL';
      $result = db_select('training_group_request', array('status' => 'text'), $where);
      if ($result->num_rows == 0) {
        $result->close();
        $query = "
          SELECT
            u.uid,
            u.email,
            g.name AS group_name
          FROM
            training_user_group ug INNER JOIN
            training_user u ON ug.uid = u.uid INNER JOIN
            training_group g ON ug.gid = g.gid
          WHERE ug.gid = ".db_quote($gid, 'integer')." AND ug.admin = 'Y'";
        $result = db_query($query);
        $to_row = $result->fetch_assoc();
        $result->close();

        $result = db_select('training_user', array('first_name' => 'text', 'last_name' => 'text', 'email' => 'text'), 'uid = '.db_quote($uid, 'integer'));
        $from_row = $result->fetch_assoc();
        $result->close();

        $body = "";
        if (!empty($note)) {
          $body = stripslashes($note)."\n=====\n";
        }

        $body .= "
".$from_row['first_name']." ".$from_row['last_name']." "._('would like to join a group on My Cycling Log of which you are the administrator:')." ".$to_row['group_name']."
"._('Please login to My Cycling Log to approve or reject this request.')."

"._('This request sent to you by My Cycling Log on behalf of')." ".$from_row['first_name']." ".$from_row['last_name']." <".$from_row['email'].">.
"._('Please do not reply to this message.')."

"._('Thank you').",
"._('The My Cycling Log Team')."
https://".MCL_DOMAIN;

        $subject = _('My Cycling Log Group Join Request');
        $success = aws_send_mail($to_row['email'], $subject, $body);

        /*
         * Create event.
         */
        $eid = create_event($to_row['uid'], GROUP_MEMBERSHIP_REQUEST, _('Group Membership Requests'), '/home.php');

        /*
         * Create request.
         */
        $values = array(
          'uid' => $uid,
          'gid' => $gid,
          'note' => $note,
          'eid' => $eid
        );
        $types = array(
          'integer',
          'integer',
          'text',
          'integer'
        );
        db_insert('training_group_request', $values, $types);

        $ERROR_MSG[] = _('Your request has been sent.');
      }
      else {
        $ERROR_MSG[] = _('You have already submitted a request to join this group.');
      }
    }
    else {
      $ERROR_MSG[] = _('You are already a member of the group.');
    }
  }
}
elseif ($_GET['form_type'] == "group_response") {
  $grid = $_GET['grid'];
  if (empty($grid)) {
    $ERROR_MSG[] = _('Group request must be specified.');
  }

  $status = $_GET['status'];
  if (empty($status)) {
    $ERROR_MSG[] = _('Status must be specified.');
  }
  elseif (!in_array($status, array("Accepted", "Denied"))) {
    $ERROR_MSG[] = _('Status must be either "Accepted" or "Denied".');
  }

  if (count($ERROR_MSG) == 0) {
    $result = db_select('training_group_request', array('uid' => 'integer', 'gid' => 'integer', 'eid' => 'integer'), 'grid = '.db_quote($grid, 'integer'));
    if ($result->num_rows == 1) {
      list($request_uid, $request_gid, $eid) = $result->fetch_row();
      $result->close();

      /*
       * Only the group admin can accept/reject a request.
       */
      $result = db_select('training_user_group', array('uid' => 'integer'), 'gid = '.db_quote($request_gid, 'integer').' AND uid = '.db_quote($uid, 'integer'));
      if ($result->num_rows == 1) {
        $result->close();
        $query = "UPDATE training_group_request SET status = ".db_quote($status, 'text').", response_date = ".db_now()." WHERE grid = ".db_quote($grid, 'integer');
        db_query($query);

        if ($status == "Accepted") {
          db_insert('training_user_group', array('uid' => $request_uid, 'gid' => $request_gid), array('integer', 'integer'));

          $result = db_select('training_user', array('email' => 'text'), 'uid = '.db_quote($request_uid, 'integer'));
          $request_email = $result->fetch_row()[0];
          $result->close();

          $body = "
"._('Your group join request has been approved.')."

"._('Thank you').",
"._('The My Cycling Log Team')."
https://".MCL_DOMAIN;

          $subject = _('My Cycling Log Group Join Request Approved');
          $success = aws_send_mail($request_email, $subject, $body);
        }

        /*
         * Read event.
         */
        if (!empty($eid)) {
          read_event($eid);
        }

        $ERROR_MSG[] = _('Response successfully recorded.');
      }
      else {
        $ERROR_MSG[] = _('You must be the group administrator to perform this action.');
      }
    }
    else {
      $ERROR_MSG[] = _('No request found.');
    }
  }
}

$HEADER_TITLE = _('Join Group');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<script type="text/javascript">
function doJoin(gid) {
  var d = document.getElementById('join_'+gid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
function doRequest(gid) {
  var d = document.getElementById('request_'+gid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<form name="search_form" action="/group_join.php" method="POST">
<input type="hidden" name="form_type" value="group_search"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Search Groups') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Search') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="q" size="25" class="formInput" value="<?php echo stripslashes(($_POST)? $_POST['q'] : $_GET['q']) ?>"/>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo _('SEARCH') ?>" class="btn"/>
    </td>
  </tr>
</table>
</form>

<?php if ($_REQUEST['q']) { ?>
  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head" colspan="2">
        <?php echo _('Search Results') ?>
      </td>
    </tr>
    <?php
    $q = preg_replace("/(\\\|\'|\")+/", " ", stripslashes($_REQUEST['q']));
    $q_list = explode(" ", $q);

    $like_clause = "";
    foreach ($q_list as $term) {
      if (!empty($like_clause)) {
        $like_clause .= " AND ";
      }
      $like_clause .= "(g.name LIKE ".db_quote('%'.$term.'%', 'text')." OR g.description LIKE ".db_quote('%'.$term.'%', 'text').")";
    }

    $query = "
      SELECT g.gid, g.name, g.description, g.link, ug.uid AS member
      FROM training_group g LEFT OUTER JOIN training_user_group ug ON g.gid = ug.gid AND ug.uid = $uid
      WHERE $like_clause
      LIMIT 0, 30";
    $result = db_query($query);
    if ($result->num_rows == 0) { ?>
      <tr><td class="title" colspan="2"><?php echo _('No groups found.') ?></td></tr>
    <?php
    } else {
      while ($row = $result->fetch_assoc()) { ?>
        <tr>
          <td class="title">
            <a href="/group_view.php?gid=<?php echo $row['gid'] ?>"><?php echo export_clean($row['name']) ?></a>
          </td>
          <td class="title inr">
            <?php if (empty($row['member'])) { ?>
              <div id="join_<?php echo $row['gid'] ?>" style="display: none">
                <form action="/group_join.php" method="POST">
                  <input type="hidden" name="form_type" value="group_join"/>
                  <input type="hidden" name="gid" value="<?php echo $row['gid'] ?>"/>
                  <input type="hidden" name="q" value="<?php echo stripslashes(($_POST)? $_POST['q'] : $_GET['q']) ?>"/>
                  <table border="0" cellspacing="0" cellpadding="2" class="noborbox">
                    <tr>
                      <td class="title" colspan="2"><?php echo _('Password') ?>: *</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <input type="text" name="password" size="25" class="formInput"/>
                      </td>
                    </tr>
                    <tr>
                      <td><input type="submit" value="<?php echo _('JOIN') ?>" class="btn"/></td>
                      <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                    </tr>
                  </table>
                </form>
              </div>
              <a href="javascript:void(0)" onclick="doJoin(<?php echo $row['gid'] ?>)"><?php echo _('Join') ?> &raquo;</a>

              <div id="request_<?php echo $row['gid'] ?>" style="display: none">
                <form action="/group_join.php" method="POST">
                  <input type="hidden" name="form_type" value="group_request"/>
                  <input type="hidden" name="gid" value="<?php echo $row['gid'] ?>"/>
                  <input type="hidden" name="q" value="<?php echo stripslashes(($_POST)? $_POST['q'] : $_GET['q']) ?>"/>
                  <table border="0" cellspacing="0" cellpadding="2" class="noborbox">
                    <tr>
                      <td class="title" colspan="2"><?php echo _('Note') ?>:</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <textarea name="note" class="formArea"></textarea>
                      </td>
                    </tr>
                    <tr>
                      <td><input type="submit" value="<?php echo _('SEND') ?>" class="btn"/></td>
                      <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                    </tr>
                  </table>
                </form>
              </div>
              <a href="javascript:void(0)" onclick="doRequest(<?php echo $row['gid'] ?>)"><?php echo _('Request Membership') ?> &raquo;</a>

            <?php
            } else {
              echo _('Already a member.');
            }
            ?>
          </td>
        </tr>
        <?php if (!empty($row['link'])) { ?>
          <tr>
            <td colspan="2">
              <?php echo html_string_format($row['link']) ?>
            </td>
          </tr>
        <?php } ?>
        <tr>
          <td class="cgray" colspan="2">
            <?php echo truncate_string(export_clean($row['description']), 50) ?>
          </td>
        </tr>
        <?php
      }
    }
    $result->close();
    ?>
  </table>
<?php } ?>

    </td>
    <td width="50%" class="cell">

<?php if (is_error()) { ?>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr><td><?php print_error() ?></td></tr>
</table>
<?php } ?>

<?php
/*
 * TODO: Show paging list of all groups.
 */
?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
