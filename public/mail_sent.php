<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$user_unit = $_SESSION['user_unit'];
$uid = $_SESSION['uid'];

$HEADER_TITLE = _('Sent Mail');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

    <table align="center" width="100%" border="0" cellspacing="1" cellpadding="4" class="tbox">
      <tr>
        <td>
          <a href="/mail.php"><?php echo _('Inbox') ?></a> | <?php echo _('Sent') ?>
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
          um.from_uid,
          ".SQL_NAME." AS to_name,
          u.username AS to_username,
          u.location AS to_location
        FROM
          training_user_message um INNER JOIN
          training_user u ON um.to_uid = u.uid
        WHERE
          um.from_uid = ".db_quote($uid, 'integer')."
        ORDER BY um.entry_date DESC";
      $result = db_query($query);
      if ($result->num_rows > 0) { ?>
        <table align="center" border="0" cellspacing="0" cellpadding="4" class="listbox" style="width: 100%">
          <tr>
            <td class="title"><?php echo _('Date') ?></td>
            <td class="title"><?php echo _('To') ?></td>
            <td class="title"><?php echo _('Title') ?></td>
          </tr>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo $row['entry_date'] ?></td>
              <td>
                <a href="/profile/<?php echo urlencode($row['to_username']) ?>" title="<?php echo $row['to_name'] ?>"><?php echo $row['to_username'] ?></a>
                <span class="cgray"><?php echo $row['to_location'] ?></span>
              </td>
              <td>
                <a href="/mail_sent_view.php?mid=<?php echo $row['mid'] ?>"><?php echo html_string_format($row['title']) ?></a>
              </td>
            </tr>
          <?php } ?>
        </table>
      <?php
      }
      $result->close();
      ?>
    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
