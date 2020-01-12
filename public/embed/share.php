<?php
include_once("../common/common.inc.php");

if (!session_check()) {
  header("Location: ../index.php");
  header("Location: ../index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

function user_urlencode(&$item, $key) {
  $item = urlencode($item);
}

$section = array();
$params = "&unit=".$user_unit;
if (is_array($_POST['section'])) {
  $section = $_POST['section'];
  array_walk($section, 'user_urlencode');
  $params .= "&custom=true&section[]=".implode("&section[]=", $section);
}

if (!empty($_SESSION['locale'])) {
  $params .= "&locale=".urlencode(substr($_SESSION['locale'], 0, 2));
}

$height = $_POST['height'];
if (empty($height) || !is_numeric($height)) {
  $height = 400;
}

$HEADER_TITLE = _('Share');
include_once("../common/header.php");
include_once("../common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<table border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr><td class="head" colspan="2"><?php echo _('Share Your Stats') ?></td></tr>
  <tr>
    <td colspan="2">
      <?php echo _('Copy and paste the following HTML code into your website or blog:') ?>
    </td>
  </tr>
  <tr>
    <td colspan="2">
<textarea rows="15" cols="80" onclick="this.select()" id="sourcecode"><?php echo export_clean('<!--     My Cycling Log on-my-site code.      -->
<div id="mcliframe" style="width: 250px; border: 1px solid #d0d0d0; background: #6090BF; overflow: hidden; white-space: nowrap;">
<span style="display: block; font: bold 11px verdana, arial; padding: 2px;"><a style="color: #fff; text-decoration: none" href="https://'.MCL_DOMAIN.'/profile/'.urlencode($_SESSION['username']).'">'.export_clean($_SESSION['username']).'</a></span>
<iframe id="statsiframe" style="height:'.$height.'px;  background: #eee;" width="100%" frameborder="0" scrolling="auto" src="https://'.MCL_DOMAIN.'/embed/?uid='.$uid.$params.'"></iframe>
<span style="display: block; font: normal 10px verdana, arial; text-align: right; padding: 1px;"><a style="color: #ddd; text-decoration: none" href="https://'.MCL_DOMAIN.'/">Log Your Rides @ MyCyclingLog.com</a></span>
</div>
<!--     My Cycling Log on-my-site code.  -->') ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="head" width="50%"><?php echo _('Customize') ?></td>
    <td class="head"><?php echo _('Preview') ?></td>
  </tr>
  <tr>
    <td>

<form name="share_form" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
<table border="0" cellspacing="0" cellpadding="4">
  <tr>
    <td class="title"><?php echo _('Longest distance in a single ride') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="maxDistance" <?php if (in_array("maxDistance", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Overall average speed') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="avgSpeed" <?php if (in_array("avgSpeed", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Number of rides in last 30 days') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="rides30" <?php if (in_array("rides30", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Last 7 days') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="distance7" <?php if (in_array("distance7", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Last 30 days') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="distance30" <?php if (in_array("distance30", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="ytdDistance" <?php if (in_array("ytdDistance", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('CO2') ?>:</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Include') ?>
      <input type="checkbox" name="section[]" value="commute" <?php if (in_array("commute", $section)) { echo "checked"; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Height') ?>:</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="height" value="<?php echo export_clean($height) ?>" size="10"/>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('UPDATE') ?>" class="btn"/>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

    </td>
    <td>
      <div id="mcliframe" style="width: 250px; border: 1px solid #d0d0d0; background: #6090BF; overflow: hidden; white-space: nowrap;">
      <span style="display: block; font: bold 11px verdana, arial; padding: 2px;"><a style="color: #fff; text-decoration: none" href="https://<?php echo MCL_DOMAIN ?>/profile/<?php echo urlencode($_SESSION['username']) ?>"><?php echo export_clean($_SESSION['username']) ?></a></span>
      <iframe id="statsiframe" style="height:<?php echo export_clean($height) ?>px; background: #eee;" width="100%" frameborder="0" scrolling="auto" src="/embed/?uid=<?php echo $uid.$params ?>"></iframe>
      <span style="display: block; font: normal 10px verdana, arial; padding: 1px;"><a style="color: #ddd; text-decoration: none" href="https://<?php echo MCL_DOMAIN ?>/">Log Your Rides @ MyCyclingLog.com</a></span>
      </div>
    </td>
  </tr>
</table>

</td></tr></table>

<?php include_once("../common/footer.php"); ?>
