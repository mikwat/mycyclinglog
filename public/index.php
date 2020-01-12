<?php
require_once("common/common.inc.php");

$sid = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  include("login.php");
  $sid = session_check();
} elseif ($_GET['a']) {
  $sid = session_check();
  include("verify.php");
} else {
  $sid = session_check();
}

if ($sid) {
  if ($_GET['g'] && $_GET['p']) {
    header("Location: group_join.php?form_type=group_join&gid=".$_GET['g']."&password=".$_GET['p']."&iid=".$_GET['iid']);
    exit();
  } elseif ($_GET['next_url']) {
    header("Location: http://".MCL_DOMAIN."/".$_GET['next_url']);
    exit();
  }
}

$user_unit = $_SESSION['user_unit'];

include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">
      <?php if (!$sid) { ?>
        <table border="0" cellspacing="0" cellpadding="4" class="inbox"><tr><td>
          <div style="padding-top: 4px">
            <?php
            echo _('<b>My Cycling Log</b> is an online diary for recording your rides, whether you are training
            for your next race or keeping track of your daily commute. The goal of
            <b>My Cycling Log</b> is to make this process quick, clean, and easy.')
            ?>
            <br/>
            <br/>
            <?php
            echo _('<b>My Cycling Log</b> also supports teams and groups. When you join a
            group you can quickly compare recent rides and contribute to the group\'s overall
            statistics.  View <a href="/group_view.php">existing groups</a>.')
            ?>
            <br/>
            <br/>
            <?php
            echo _('Registration is completely free and <b>My Cycling Log</b> will never distribute or
            sell your email address. <a href="/register.php">Sign-up now</a>!')
            ?>
          </div>
      </td></tr></table>
      <?php } ?>

      <img src="/charts/all_monthly_chart.php" border="0"/>

      <?php include("common/recent.php"); ?>
    </td>
    <td class="cell">
      <?php if (!$sid) { ?>
        <script type="text/javascript">
        YAHOO.util.Event.addListener(window, "load", function() {
          document.forms['login_form'].elements['username'].focus();
        });
        </script>
        <form name="login_form" action="/" method="POST">
        <input type="hidden" name="next_url" value="<?php echo $_GET['next_url'] ?>"/>
        <?php
        if ($_REQUEST['g'] && $_REQUEST['p']) {
          $GLOBALS['ERROR_MSG'][] = _('Login to join group.');
          ?>
          <input type="hidden" name="g" value="<?php echo $_REQUEST['g'] ?>"/>
          <input type="hidden" name="p" value="<?php echo $_REQUEST['p'] ?>"/>
        <?php } ?>
        <table border="0" cellspacing="0" cellpadding="4" class="inbox">
          <tr><td class="head" colspan="2"><?php echo _('Login') ?></td></tr>
          <tr>
            <td class="inr" width="50%"><?php echo _('Username') ?>:</td>
            <td width="50%">
              <?php
              $uname = "";
              if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $uname = $_POST['username'];
              } elseif (!empty($_COOKIE['mcl_u'])) {
                $uname = $_COOKIE['mcl_u'];
              }
              ?>
              <input name="username" type="text" size="20" class="formInput" value="<?php echo $uname ?>"/>
            </td>
          </tr>
          <tr>
            <td class="inr"><?php echo _('Password') ?>:</td>
            <td>
              <input name="password" type="password" size="20" class="formInput"/>
            </td>
          </tr>
          <tr>
            <td class="inr cgray" colspan="2">
              <?php echo _('Remember me') ?>:
              <input type="checkbox" name="remember" value="1" <?php if ($_COOKIE['mcl_r'] == 1) { echo "checked"; } ?>/>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
                <td>
                  <a href="/register.php"><?php echo _('Sign Up') ?> &raquo;</a><br/>
                  <a href="/retrieve.php"><?php echo _('Forget password?') ?></a>
                </td>
                <td class="inr">
                  <input type="submit" value="<?php echo _('Login') ?>" class="btn"/>
                </td>
              </tr></table>
            </td>
          </tr>
          <?php if (is_error()) { ?>
            <tr><td colspan="2"><?php print_error() ?></td></tr>
          <?php } ?>
          <tr><td colspan="2"><a href="/problems.php"><?php echo _('Problems signing in?') ?></a></td></tr>
          <?php if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') { ?>
            <tr><td colspan="2"><a href="https://<?php echo MCL_DOMAIN ?>"><?php echo _('Try our secure site!') ?></a></td></tr>
          <?php } ?>
        </table>
        </form>
      <?php } ?>

      <?php
      include('common/highlights.php');
      include('common/popular.php');
      ?>
    </td>
  </tr>
</table>

<?php include("common/footer.php"); ?>
