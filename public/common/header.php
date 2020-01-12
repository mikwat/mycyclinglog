<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}
header('Content-Type: text/html;charset=iso-8859-1');
$GLOBALS['pageload_start'] = microtime(true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-type" content="text/html;charset=iso-8859-1"/>
  <title>My Cycling Log <?php echo (isset($HEADER_TITLE))? ": $HEADER_TITLE" : "" ?></title>
  <link href="/common/mycyclinglog.css" rel="stylesheet" type="text/css"/>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <?php if (isset($HEADER_META)) { echo $HEADER_META; } ?>
  <script type="text/javascript" src="/js/yahoo-dom-event.js"></script>
</head>
<body>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr valign="bottom">
    <td width="400">
      <a href="/"><img src="/images/mcl_logo.png" border="0" width="400" height="80" alt="My Cycling Log"/></a>
    </td>
    <td class="inr">
      <form name="search_form" action="/search.php" method="POST">
      <input type="hidden" name="form_type" value="search"/>
      <table align="right" border="0" cellspacing="0" cellpadding="4" class="noborbox">
        <tr>
          <td class="inr" width="110"><input type="text" name="q" size="15" class="formInput" value="<?php if (isset($_GET['q'])) { echo htmlspecialchars($_GET['q']); } ?>"/></td>
          <td class="inr" width="55"><input type="submit" value="SEARCH" class="sbtn"/></td>
        </tr>
        <tr>
          <td class="inr" colspan="3">
            <?php if (!isset($_SESSION['uid'])) { ?>
              <a href="/register.php"><b><?php echo _('Sign Up') ?></b></a>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <td class="inr" colspan="3">
            <?php if (isset($_SESSION['uid'])) { ?>
              <a href="/profile/<?php echo urlencode($_SESSION['username']) ?>"><?php echo _('My Public Profile') ?></a>
              <?php
              $result = db_select('training_event', array('title' => 'text'), "uid=".db_quote($_SESSION['uid'], 'integer')." AND addressed='F'");
              if ($result->num_rows > 0) { ?>
                | <span class="highlight"><a href="/home.php"><?php echo _('New Messages') ?></a></span>
              <?php } ?>
              | <a href="/logout.php"><?php echo _('Logout') ?></a>
            <?php } else { ?>
              <span class="cgray"><?php echo _('You are not logged in') ?>.</span>
              | <a href="/"><?php echo _('Login') ?></a>
            <?php } ?>
          </td>
        </tr>
      </table>
      </form>
    </td>
  </tr>
</table>
