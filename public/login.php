<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: index.php");
}

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!empty($_POST['username']) && !empty($_POST['password'])) {
    /* check enabled flag and password */
    $types = array(
      'uid' => 'integer',
      'password' => 'text',
      'enabled' => 'text',
      'last_login' => 'timestamp',
      'timezone' => 'text',
      'locale' => 'text',
      'week_start' => 'integer',
      'location' => 'text',
      'unit' => 'text',
      'ext_cookie' => 'text');
    $username = trim($_POST['username']);
    $res = db_select('training_user', $types, 'banned = 0 AND cancelled = 0 AND username = '.db_quote($username, 'text'));

    list($uid, $db_password, $enabled, $last_login, $timezone, $locale, $week_start, $location, $user_unit, $ext_cookie) = $res->fetch_row();
    if ($enabled == 'T') {
      $md5_password = md5($_POST['password']);
      if ($db_password == $md5_password) {
        /*
         * Check remember me checkbox.
         */
        $remember = false;
        if ($_POST['remember'] == 1) {
          $remember = true;
        }

        session_setup($uid, $username, $last_login, $timezone, $locale, $week_start, $location, $user_unit, $remember);

        if (!empty($_POST['g']) && !empty($_POST['p'])) {
          header("Location: group_join.php?form_type=group_join&gid=".$_POST['g']."&password=".$_POST['p']);
        }
        elseif (!empty($_POST['next_url'])) {
          header("Location: http://".MCL_DOMAIN."/".$_POST['next_url']);
        }
        else {
          header("Location: home.php");
        }
      }
      else {
        $GLOBALS['ERROR_MSG'][] = _('Login failed. Invalid username or password.');
      }
    }
    elseif ($enabled == 'F') {
      $GLOBALS['ERROR_MSG'][] = _('Your email address has not been verified. Please check for a confirmation email.');
    }
    else {
      $GLOBALS['ERROR_MSG'][] = _('Login failed. An unexpected error occured.');
    }
  }
  else {
    $GLOBALS['ERROR_MSG'][] = _('Login failed. Required field missing.');
  }
}
?>
