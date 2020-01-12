<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

function method_bike_new($uid, $make, $model, $year, $enabled) {
  $ERROR_MSG = [];

  $make = $_POST['make'];
  if (empty($make)) {
    $ERROR_MSG[] = _('Make is required.');
  }

  $model = $_POST['model'];
  if (empty($model)) {
    $ERROR_MSG[] = _('Model is required.');
  }

  $year = $_POST['year'];
  if (!empty($year) && !is_numeric($year)) {
    $ERROR_MSG[] = _('Year must be numeric.');
  }

  $enabled = $_POST['enabled'];
  if (empty($enabled)) {
    $enabled = 'F';
  }

  if (count($ERROR_MSG) == 0) {
    $values = array(
      'uid' => $uid,
      'make' => $make,
      'model' => $model,
      'year' => $year,
      'enabled' => $enabled
    );
    $types = array(
      'integer',
      'text',
      'text',
      'integer',
      'text'
    );

    $bid = $_POST['bid'];
    if (is_numeric($bid)) {
      db_update('training_bike', $values, $types, 'uid = '.db_quote($uid, 'integer').' AND bid = '.db_quote($bid, 'integer'));
    }
    else {
      db_insert('training_bike', $values, $types);
      $bid = db_insert_id();
    }

    if (count($ERROR_MSG) == 0) {
      return $bid;
    }
  }
  return $ERROR_MSG;
}
?>
