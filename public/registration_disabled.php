<?php
include_once("common/common.inc.php");

session_check();

$HEADER_TITLE = _('Registration Disabled');
include_once("common/header.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Registration Temporarily Disabled') ?>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo _('New registrations have been temporarily disabled.') ?><br/><br/>
    </td>
  </tr>
</table>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
