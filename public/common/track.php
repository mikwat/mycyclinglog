<?php
// Make sure image is sent to the browser immediately
ob_implicit_flush(TRUE);

// keep running after browser closes connection
@ignore_user_abort(true);

sendGIF();

function sendGIF(){
  $img = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAIBTAA7');
  header('Content-Type: image/gif');
  header('Content-Length: '.strlen($img));
  header('Connection: Close');
  print $img;
  // Browser should drop connection after this
  // Thinks it's got the whole image
}

include_once("common.inc.php");

define('TIMEOUT', 20);

if (session_check()) {
  $uid = $_SESSION['uid'];

  $ip_address = $_SERVER['REMOTE_ADDR'];

  $result = db_select('training_online', array('uid' => 'integer'), 'uid = '.db_quote($uid, 'integer'));
  $num_rows = $result->num_rows;
  $result->close();

  if ($num_rows == 0) {
    $query = "
      INSERT INTO training_online (uid, ip_address)
      VALUES (".db_quote($uid, 'integer').", ".db_quote($ip_address, 'text').")";
    db_query($query);
  }
  else {
    $query = "UPDATE training_online SET last_active_dt = ".db_now()." WHERE uid = ".db_quote($uid, 'integer');
    db_query($query);
  }
}

db_delete('training_online', 'last_active_dt < DATE_SUB('.db_now().', INTERVAL '.TIMEOUT.' MINUTE)');
?>
