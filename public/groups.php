<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = _('Groups');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<script type="text/javascript">
function doLeave(gid) {
  var d = document.getElementById('leave_'+gid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head" colspan="2">
      <?php echo _('My Groups') ?>
      <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
     </td>
  </tr>
  <tr>
    <td class="title" colspan="2">
      <a href="/group_edit.php"><?php echo _('Create a new group') ?> &raquo;</a><br/>
      <a href="/group_join.php"><?php echo _('Join an existing group') ?> &raquo;</a>
    </td>
  </tr>
  <?php
  $query = "
    SELECT
      g.gid,
      g.name AS group_name,
      g.description,
      g.link,
      ug.admin
    FROM
      training_user_group ug INNER JOIN
      training_group g ON ug.gid = g.gid
    WHERE
      ug.uid = ".db_quote($uid, 'integer');
  $result = db_query($query);
  if ($result->num_rows == 0) { ?>
    <tr><td colspan="2"><?php echo _('You belong to no groups.') ?></td></tr>
  <?php }
  while ($row = $result->fetch_assoc()) { ?>
    <tr>
      <td class="title">
         <a href="/group_view.php?gid=<?php echo $row['gid'] ?>"><?php echo export_clean($row['group_name']) ?></a>
      </td>
      <td class="title inr">
        <?php if ($row['admin'] == 'Y') { ?>
          <a href="/group_invite.php?gid=<?php echo $row['gid'] ?>"><?php echo _('Invite') ?></a>
          |
          <a href="/group_edit.php?gid=<?php echo $row['gid'] ?>"><?php echo _('Edit') ?></a>
        <?php } else { ?>
          <div id="leave_<?php echo $row['gid'] ?>" style="display: none">
            <form action="/group_edit.php" method="post">
              <input type="hidden" name="form_type" value="leave"/>
              <input type="hidden" name="gid" value="<?php echo $row['gid'] ?>"/>
              <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                <td><input type="submit" value="<?php echo _('LEAVE') ?>" class="btn"/></td>
                <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
              </tr></table>
            </form>
          </div>
          <a href="javascript:void(0)" onclick="doLeave(<?php echo $row['gid'] ?>)"><?php echo _('Leave group') ?> &raquo;</a>
        <?php } ?>
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
      <td class="cgray" colspan="2"><?php echo html_string_format($row['description']) ?></td>
    </tr>
    <?php
    $gid = $row['gid'];
    $query2 = "
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
    $result2 = db_query($query2);
    while ($m_row = $result2->fetch_assoc()) { ?>
    <tr>
      <td>
        <a href="/profile/<?php echo urlencode($m_row['username']) ?>" title="<?php echo export_clean($m_row['name']) ?>"><?php echo export_clean($m_row['username']) ?></a>
        <?php if ($m_row['admin'] == 'Y') { ?>
          <span class="cgray">(<?php echo _('Group Admin') ?>)</span>
        <?php } ?>
      </td>
      <td class="inr">
        <?php echo unit_format($m_row['distance'], $user_unit)." ".$user_unit." "._('at')." " ?>
        <?php echo unit_format($m_row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
      </td>
    </tr>
    <?php }
    $result2->close();
    ?>
  <?php }
  $result->close();
  ?>
</table>

    </td>
    <td width="50%" class="cell">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="inr" colspan="2">
      <b><a href="/group_view.php"><?php echo _('View Other Groups') ?> &raquo;</a></b>
    </td>
  </tr>
</table>

<?php include("common/group_recent.php") ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
