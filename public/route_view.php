<?php
include_once("common/common.inc.php");

$sid = session_check();
$user_unit = $_SESSION['user_unit'];

$HEADER_TITLE = _('All Routes');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>
      <?php include("common/route_list_all.php"); ?>   
    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
