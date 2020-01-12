<?php
if (!is_numeric($_GET['gid'])) {
  header("Location: index.php");
  exit();
}

include_once("common/common.inc.php");

session_check();

$gid = $_GET['gid'];
$result = db_select('training_group', array('name' => 'text', 'description' => 'text', 'link' => 'text'), 'gid = '.db_quote($gid, 'integer'));
list($g_name, $g_desc, $g_link) = $result->fetch_row();

$HEADER_TITLE = _('Group Charts')." : ".export_clean($g_name);
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td width="66%">

<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4" class="tbox">
  <tr>
    <td>
      <a href="/group_view.php?gid=<?php echo $gid ?>"><?php echo _('Group View') ?></a>
      |
      <a href="/group_detail.php?gid=<?php echo $gid ?>"><?php echo _('Recent Rides') ?></a>
      |
      <a href="/group_discussion.php?gid=<?php echo $gid ?>"><?php echo _('Discussion') ?></a>
      |
      <?php echo _('Charts') ?>
    </td>
  </tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Group History') ?>:
      <?php echo export_clean($g_name) ?>
    </td>
  </tr>
  <tr>
    <td>
      <img src="/charts/group_yearly_chart.php?attr=distance&gid=<?php echo $gid ?>" border="0"/>
    </td>
  </tr>
  <tr>
    <td>
      <img src="/images/spacer.gif" height="2"/>
    </td>
  </tr>
  <tr>
    <td>
      <img src="/charts/group_monthly_chart.php?attr=avg_speed&gid=<?php echo $gid ?>" border="0"/>
    </td>
  </tr>
</table>

</td><td class="cell">

  <?php include("common/group_highlights.php") ?>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
