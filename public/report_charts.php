<?php
include_once("common/util/calendar.inc.php");
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = _('Report : Charts');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td colspan="2">

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tinbox"><tr><td>
  <a href="/report.php"><?php echo _('Data') ?></a>
  |
  <a href="/report_calendar.php"><?php echo _('Calendar') ?></a>
  |
  <?php echo _('Charts') ?>
</td></tr></table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tinbox">
  <tr><td>
    <?php echo _('Weekly') ?>:
    <a href="/report_charts.php?type=weekly&attr=distance"><?php echo _('Distance') ?></a>
    |
    <a href="/report_charts.php?type=weekly&attr=time"><?php echo _('Time') ?></a>
    |
    <a href="/report_charts.php?type=weekly&attr=avg_speed"><?php echo _('Avg Speed') ?></a>
    |
    <a href="/report_charts.php?type=weekly&attr=weight"><?php echo _('Avg Weight') ?></a>
    |
    <a href="/report_charts.php?type=weekly&attr=calories"><?php echo _('Calories') ?></a>
    |
    <a href="/report_charts.php?type=weekly&attr=elevation"><?php echo _('Elevation') ?></a>
  </td></tr>
  <tr><td>
    <?php echo _('Monthly') ?>:
    <a href="/report_charts.php?type=monthly&attr=distance"><?php echo _('Distance') ?></a>
    |
    <a href="/report_charts.php?type=monthly&attr=time"><?php echo _('Time') ?></a>
    |
    <a href="/report_charts.php?type=monthly&attr=avg_speed"><?php echo _('Avg Speed') ?></a>
    |
    <a href="/report_charts.php?type=monthly&attr=weight"><?php echo _('Avg Weight') ?></a>
    |
    <a href="/report_charts.php?type=monthly&attr=calories"><?php echo _('Calories') ?></a>
    |
    <a href="/report_charts.php?type=monthly&attr=elevation"><?php echo _('Elevation') ?></a>
  </td></tr>
  <tr><td>
    <?php echo _('Yearly') ?>:
    <a href="/report_charts.php?type=yearly&attr=distance"><?php echo _('Distance') ?></a>
    |
    <a href="/report_charts.php?type=yearly&attr=time"><?php echo _('Time') ?></a>
    |
    <a href="/report_charts.php?type=yearly&attr=avg_speed"><?php echo _('Avg Speed') ?></a>
    |
    <a href="/report_charts.php?type=yearly&attr=weight"><?php echo _('Avg Weight') ?></a>
    |
    <a href="/report_charts.php?type=yearly&attr=calories"><?php echo _('Calories') ?></a>
    |
    <a href="/report_charts.php?type=yearly&attr=elevation"><?php echo _('Elevation') ?></a>
  </td></tr>
</table>

<?php
$url = "/charts/";
switch ($_GET['type']) {
  case 'weekly':
    $url .= "weekly_chart.php";
    break;
  case 'yearly':
    $url .= "yearly_chart.php";
    break;
  default:
    $url .= "monthly_chart.php";
    break;
}

switch ($_GET['attr']) {
  case 'time':
    $url .= "?attr=time";
    break;
  case 'avg_speed':
    $url .= "?attr=avg_speed";
    break;
  case 'weight':
    $url .= "?attr=weight";
    break;
  case 'calories':
    $url .= "?attr=calories";
    break;
  case 'elevation':
    $url .= "?attr=elevation";
    break;
  default:
    $url .= "?attr=distance";
    break;
}
?>
<div id="report_div"><img src="<?php echo $url ?>&uid=<?php echo $uid ?>" border="0" alt="<?php echo _('Report') ?>"/></div>
</td></tr></table>

<?php include_once("common/footer.php"); ?>
