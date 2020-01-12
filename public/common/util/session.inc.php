<?php
function session_check() {
  session_start();

  /*
   * Store referrer.
   */
  if (isset($_GET['r'])) {
    $_SESSION['referrer'] = $_GET['r'];
  }

  /*
   * Store invite ID.
   */
  if (isset($_GET['iid'])) {
    $_SESSION['iid'] = $_GET['iid'];
  }

  /*
   * Store locale.
   */
  if (isset($_GET['locale'])) {
    switch ($_GET['locale']) {
      case 'es':
        $_SESSION['locale'] = 'es_ES.iso88591';
        break;
      case 'it':
        $_SESSION['locale'] = 'it_IT.iso88591';
        break;
      case 'pt':
        $_SESSION['locale'] = 'pt_PT.iso88591';
        break;
      default:
        unset($_SESSION['locale']);
    }
  }

  $valid_session = false;
  if (!empty($_SESSION['uid'])) {
    $valid_session = true;
  }
  else {
    /*
     * Check remember me cookie.
     */
    if (!empty($_COOKIE['mcl_u']) && !empty($_COOKIE['mcl_p'])) {
      $username = $_COOKIE['mcl_u'];
      $ext_cookie = $_COOKIE['mcl_p'];

      $types = array(
        'uid' => 'integer',
        'last_login' => 'timestamp',
        'timezone' => 'text',
        'locale' => 'text',
        'week_start' => 'integer',
        'location' => 'text',
        'unit' => 'text');
      $result = db_select('training_user', $types, 'banned = 0 AND cancelled = 0 AND ext_cookie = '.db_quote($ext_cookie, 'text').' AND username = '.db_quote($username, 'text'));
      if ($result->num_rows == 1) {
        list($uid, $last_login, $timezone, $locale, $week_start, $location, $user_unit) = $result->fetch_row();
        session_setup($uid, $username, $last_login, $timezone, $locale, $week_start, $location, $user_unit, true);

        $valid_session = true;
      }
    }
  }

  if ($valid_session === true) {
    /*
     * Set timezone.
     */
    date_default_timezone_set($_SESSION['timezone']);
  }
  else {
    $_SESSION['user_unit'] = 'mi';
  }

  return $valid_session;
}

function session_setup($uid, $username, $last_login, $timezone, $locale, $week_start, $location, $user_unit, $remember) {
  /*
   * register uid with session
   */
  $_SESSION['uid'] = $uid;
  $_SESSION['username'] = $username;
  $_SESSION['last_login'] = $last_login;
  $_SESSION['timezone'] = $timezone;
  $_SESSION['location'] = $location;
  $_SESSION['user_unit'] = $user_unit;
  $_SESSION['week_start'] = $week_start;

  switch ($locale) {
    case 'es':
      $_SESSION['locale'] = 'es_ES.iso88591';
      break;
    case 'it':
      $_SESSION['locale'] = 'it_IT.iso88591';
      break;
    case 'pt':
      $_SESSION['locale'] = 'pt_PT.iso88591';
      break;
    default:
      unset($_SESSION['locale']);
  }


  /*
   * save username and external code in cookie
   */
  if ($remember === true) {
    $result = db_select('training_user', array('ext_cookie' => 'text'), 'ext_cookie IS NOT NULL && uid = '.db_quote($uid, 'integer'));
    if ($result->num_rows > 0) {
      list($ext_cookie) = $result->fetch_row();
    }
    if (empty($ext_cookie)) {
      $ext_cookie = make_random_password(64);
      db_update('training_user', array('ext_cookie' => $ext_cookie), array('text'), 'uid = '.db_quote($uid, 'integer'));
    }

    $expires = time() + 60*60*24*30;
    setcookie("mcl_u", stripslashes($username), $expires, '/');
    setcookie("mcl_p", $ext_cookie, $expires, '/');
    setcookie("mcl_r", 1, $expires, '/');
  } else {
    setcookie("mcl_u", "", time() - 60*60, '/');
    setcookie("mcl_p", "", time() - 60*60, '/');
    setcookie("mcl_r", "", time() - 60*60, '/');
  }

  $query = '
    UPDATE training_user SET
      last_login = now(),
      login_count = login_count + 1
    WHERE uid = '.db_quote($uid, 'integer');
  db_query($query);

  $types = array('mid' => 'integer');
  $res = db_select('training_message', $types, 'entry_date > '.db_quote($last_login, 'timestamp'));
  if ($res->num_rows > 0) {
    $_SESSION['new_posts'] = true;
  }
  else {
    $_SESSION['new_posts'] = false;
  }
}

function make_random_password($length = 7) {
  $salt = "abchefghjkmnpqrstuvwxyz0123456789";
  srand((double)microtime()*1000000);
  $i = 0;
  while ($i <= $length) {
    $num = rand() % 33;
    $tmp = substr($salt, $num, 1);
    $pass = $pass . $tmp;
    $i++;
  }

  return $pass;
}
?>
