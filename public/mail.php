<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];

if ($_POST['form_type'] == "delete_message") {
  $mid = $_POST['mid'];
  db_delete('training_user_message', 'mid = '.db_quote($mid, 'integer').' AND to_uid = '.db_quote($uid, 'integer'));
  $ERROR_MSG[] = _('Message deleted.');
}

$HEADER_TITLE = _('Mail');
include_once("common/header.php");
include_once("common/tabs.php");
?>

<script type="text/javascript">
function doDelete(id) {
  var d = document.getElementById('delete_'+id);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

    <table align="center" width="100%" border="0" cellspacing="1" cellpadding="4" class="tbox">
      <tr>
        <td>
          <?php echo _('Inbox') ?> | <a href="/mail_sent.php"><?php echo _('Sent') ?></a>
        </td>
      </tr>
    </table>

      <?php
      $query = "
        SELECT
          um.mid,
          um.to_uid,
          um.title,
          um.body,
          um.entry_date,
          um.`read`,
          um.from_uid,
          ".SQL_NAME." AS from_name,
          u.username AS from_username,
          u.location AS from_location
        FROM
          training_user_message um INNER JOIN
          training_user u ON um.from_uid = u.uid
        WHERE
          um.to_uid = ".db_quote($uid, 'integer')."
        ORDER BY um.entry_date DESC";
      $result = db_query($query);
      if ($result->num_rows > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="listbox" style="width: 100%">
          <tr>
            <td class="title"><?php echo _('Date') ?></td>
            <td class="title"><?php echo _('From') ?></td>
            <td class="title"><?php echo _('Title') ?></td>
            <td class="title inr"></td>
          </tr>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <?php $row_class = $row['read'] == 'Y' ? '' : 'title'; ?>
            <tr>
              <td class="<?php echo $row_class ?>"><?php echo datetime_format_nice($row['entry_date']) ?></td>
              <td class="<?php echo $row_class ?>">
                <a href="/profile/<?php echo urlencode($row['from_username']) ?>" title="<?php echo $row['from_name'] ?>"><?php echo $row['from_username'] ?></a>
                <span class="cgray"><?php echo $row['from_location'] ?></span>
              </td>
              <td class="<?php echo $row_class ?>">
                <a href="/mail_view.php?mid=<?php echo $row['mid'] ?>"><?php echo html_string_format($row['title']) ?></a>
              </td>
              <td class="<?php echo $row_class ?> inr">
                <div id="delete_<?php echo $row['mid'] ?>" style="display: none">
                  <form action="/mail.php" method="post">
                    <input type="hidden" name="form_type" value="delete_message"/>
                    <input type="hidden" name="mid" value="<?php echo $row['mid'] ?>"/>
                    <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                      <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                      <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
                    </tr></table>
                  </form>
                </div>
                <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['mid'] ?>)">
                  <img src="images/icon_remove.gif" border="0" alt="<?php echo _('Delete') ?>"/></a>
              </td>
            </tr>
          <?php } ?>
        </table>
      <?php }
      $result->close();
      ?>
    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
