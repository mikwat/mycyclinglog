<?php
require_once('../common/common.inc.php');
require_once('methods.php');

function authPrompt() {
   header('WWW-Authenticate: Basic realm="My Cycling Log API"');
   header('HTTP/1.0 401 Unauthorized');
   echo 'You are not authorized.';
   exit;
}

function getParam($param_name, $default_value = 'unknown') {
  list($ignore, $param_s) = explode('?', $_SERVER['REQUEST_URI']);
  $param_r = explode('&', $param_s);
  foreach ($param_r as $p) {
    list($key, $val) = explode('=', $p);
    if ($key == $param_name) {
      return $val;
    }
  }
  return $default_value;
}

if (isset($_GET['login'])) {
    $d = base64_decode(substr($_GET['login'], 6));
    list($username, $password) = explode(':', $d);

    $result = db_select('training_user', array('uid' => 'integer', 'password' => 'text'), 'username='.db_quote($username, 'text'));
    if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $db_md5_pass = $row['password'];
      $entered_md5_pass = md5($password);
      if ($db_md5_pass != $entered_md5_pass) {
        // invalid password
        error_log('invalid password');
        authPrompt();
      }
      // all good, continue!
      $uid = $row['uid'];
    } else {
      // username not found
      error_log('username not found');
      authPrompt();
    }
    $result->close();
} else {
  // default
  authPrompt();
}

$method = getParam('method');
switch ($method) {
  case 'ride.new':
    $rval = method_ride_new($uid);
    break;
  case 'ride.list':
    $offset = getParam('offset', 0);
    $limit = getParam('limit', 10);
    $rval = method_ride_list($uid, $offset, $limit);
    break;
  case 'bike.new':
    $make = getParam('make', null);
    $model = getParam('model', null);
    $year = getParam('year', null);
    $enabled = getParam('enabled', null);
    $rval = method_bike_new($uid, $make, $model, $year, $enabled);
    break;
  case 'bike.list':
    $offset = getParam('offset', 0);
    $limit = getParam('limit', 10);
    $rval = method_bike_list($uid, $offset, $limit);
    break;
  default:
    $rval = array("Unknown method.");
}

header('Content-type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<response>';
print_r($rval);
echo '</response>';
?>
