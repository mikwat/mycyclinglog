<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
  exit();
}
?>
<table width="750" align="center" border="0" cellspacing="0" cellpadding="0" class="tbox">
  <tr><td>
  <?php
  if (empty($HEADER_TITLE)) {
    echo _('My Cycling Log');
  }
  else {
    ?><a href="/"><?php echo _('My Cycling Log') ?></a><?php
  }
  echo " | ";

  if (isset($_SESSION['uid'])) {
    if ($HEADER_TITLE == _('Home')) {
      echo _('Home');
    }
    else {
      ?><a href="/home.php"><?php echo _('Home') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Mail')) {
      echo _('Mail');
    }
    else {
      ?><a href="/mail.php"><?php echo _('Mail') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Add')) {
      echo _('Add');
    }
    else {
      ?><a href="/add.php"><?php echo _('Add') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('View')) {
      echo _('View');
    }
    else {
      ?><a href="/view.php"><?php echo _('View') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Report')) {
      echo _('Report');
    }
    else {
      ?><a href="/report.php"><?php echo _('Report') ?></a><?php
    }

    echo " | ";
    if ($HEADER_TITLE == _('Account')) {
      echo _('Account');
    }
    else {
        ?><a href="/account.php"><?php echo _('Account') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Bikes')) {
      echo _('Bikes');
    }
    else {
      ?><a href="/bikes.php"><?php echo _('Bikes') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Routes')) {
      echo _('Routes');
    }
    else {
      ?><a href="/routes.php"><?php echo _('Routes') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Groups')) {
      echo _('Groups');
    }
    else {
      ?><a href="/groups.php"><?php echo _('Groups') ?></a><?php
    }
    echo " | ";

    if ($HEADER_TITLE == _('Goals')) {
      echo _('Goals');
    }
    else {
      ?><a href="/goals.php"><?php echo _('Goals') ?></a><?php
    }
    echo " | ";
  }

  if ($HEADER_TITLE == _('Discussion')) {
    echo _('Discussion');
  }
  else {
    ?><a href="/discussion.php"><?php echo _('Discussion') ?></a><?php
    if (isset($_SESSION['uid']) && !empty($_SESSION['last_login']) &&
        isset($_SESSION['new_posts']) && $_SESSION['new_posts'] === true) { ?>
      <img src="/images/alert.gif" alt="<?php echo _('New Posts') ?>" align="absmiddle"/>
    <?php }
  }
  ?>
  </td></tr>
</table>
