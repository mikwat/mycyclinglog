<?php
include_once('common/common.inc.php');
session_check();

$HEADER_TITLE = _('Registration Complete');
include_once('common/header.php');
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Thank You for Registering') ?></td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('You have been sent a verification email. If it does not arrive shortly, take the following steps:') ?>
      <ul>
        <li><?php echo _('Check your <b>Spam</b> folder.') ?></li>
        <li><?php echo _('Go to the <a href="/problems.php">Problem Center</a>.') ?></li>
      </ul>
    </td>
  </tr>
</table>

<?php if (isset($_SESSION['referrer']) && $_SESSION['referrer'] == 'g') { ?>
<table width="100%" border="0" cellspacing="1" cellpadding="5"><tr><td align="center">
<!-- Google Code for Signup Conversion Page -->
<script language="JavaScript" type="text/javascript">
<!--
var google_conversion_id = 1071657191;
var google_conversion_language = "en_US";
var google_conversion_format = "1";
var google_conversion_color = "FFFFFF";
if (1) {
  var google_conversion_value = 1;
}
var google_conversion_label = "Signup";
//-->
</script>
<script language="JavaScript" src="http://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<img height=1 width=1 border=0 src="http://www.googleadservices.com/pagead/conversion/1071657191/?value=1&label=Signup&script=0">
</noscript>
</td></tr></table>
<?php } elseif (isset($_SESSION['referrer']) && $_SESSION['referrer'] == 'y') { ?>
<table width="100%" border="0" cellspacing="1" cellpadding="5"><tr><td align="center">
<SCRIPT LANGUAGE="JavaScript">
<!-- Overture Services Inc. 07/15/2003
var cc_tagVersion = "1.0";
var cc_accountID = "4724735570";
var cc_marketID =  "0";
var cc_protocol="http";
var cc_subdomain = "convctr";
if(location.protocol == "https:")
{
  cc_protocol="https";
  cc_subdomain="convctrs";
}
var cc_queryStr = "?" + "ver=" + cc_tagVersion + "&aID=" + cc_accountID + "&mkt=" + cc_marketID +"&ref=" + escape(document.referrer);
var cc_imageUrl = cc_protocol + "://" + cc_subdomain + ".overture.com/images/cc/cc.gif" + cc_queryStr;
var cc_imageObject = new Image();
cc_imageObject.src = cc_imageUrl;
// -->
</SCRIPT>
</td></tr></table>
<?php } ?>

  </tr>
</table>

<?php include_once('common/footer.php') ?>
