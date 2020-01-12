<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

function method_ride_new($uid) {
  $ERROR_MSG = [];

  $date_i = strtotime($_POST['event_date']);
  if ($date_i === -1) {
    $ERROR_MSG[] = _('Invalid parameter format: event_date');
  }
  else {
    $date = date("Y-m-d", $date_i);
  }

  $is_ride = $_POST['is_ride'];
  if (empty($is_ride)) {
    $ERROR_MSG[] = _('Required parameter missing: is_ride');
  }

  /*
   * Get time.
   */
  $h = empty($_POST['h']) ? "00" : $_POST['h'];
  $m = empty($_POST['m']) ? "00" : $_POST['m'];
  $s = empty($_POST['s']) ? "00" : $_POST['s'];

  if ($h < 0) {
    $ERROR_MSG[] = _('Hours must be greater than or equal to 0.');
  }

  if ($m > 59) {
    $ERROR_MSG[] = _('Minutes must be less than 60.');
  }
  elseif ($m < 0) {
    $ERROR_MSG[] = _('Minutes must be greater than or equal to 0.');
  }

  if ($s > 59) {
    $ERROR_MSG[] = _('Seconds must be less than 60.');
  }
  elseif ($s < 0) {
    $ERROR_MSG[] = _('Seconds must be greater than or equal to 0.');
  }

  if ($h != "00" || $m != "00" || $s != "00") {
    $time = $h.":".$m.":".$s;
  }
  else {
    $time = null;
  }

  /*
   * Get distance.
   */
  $user_unit = $_POST['user_unit'];
  $distance = '';
  if (!empty($_POST['distance'])) {
    $distance = $_POST['distance'];
    if (!is_numeric($distance)) {
      $ERROR_MSG[] = _('Distance is not a valid number.');
    }
    elseif ($distance < 0) {
      $ERROR_MSG[] = _('Distance must be greater than or equal to 0.');
    }
    elseif ($user_unit == "km") {
      /*
       * Always insert distance in miles.
       */
      $distance = km_to_m($distance);
    }
  }

  $notes = $_POST['notes'];
  $heart_rate = $_POST['heart_rate'];

  $max_speed = null;
  if (!empty($_POST['max_speed'])) {
    $max_speed = $_POST['max_speed'];
    if (!is_numeric($max_speed)) {
      $ERROR_MSG[] = _('Maximum speed is not a valid number.');
    }
    elseif ($max_speed < 0) {
      $ERROR_MSG[] = _('Maximum speed must be greater than or equal to 0.');
    }
    elseif ($user_unit == "km") {
      $max_speed = km_to_m($max_speed);
    }
  }

  $avg_cadence = null;
  if (is_numeric($_POST['avg_cadence'])) {
    $avg_cadence = $_POST['avg_cadence'];
  }

  $weight = null;
  if (is_numeric($_POST['weight'])) {
    $weight = $_POST['weight'];
  }

  $calories = null;
  if (is_numeric($_POST['calories'])) {
    $calories = $_POST['calories'];
  }

  $elevation = null;
  if (is_numeric($_POST['elevation'])) {
    $elevation = $_POST['elevation'];
  }

  $tag_str = $_POST['tags'];
  $tags = null;
  if (!empty($tag_str)) {
    $tags = parse_tag_str($tag_str);
  }

  $rid = $_POST['rid'];
  $bid = $_POST['bid'];
  if (count($ERROR_MSG) == 0) {
    $values = array(
      'uid' => $uid,
      'bid' => $bid,
      'rid' => $rid,
      'event_date' => $date,
      'is_ride' => $is_ride,
      'time' => $time,
      'distance' => $distance,
      'notes' => $notes,
      'max_speed' => $max_speed,
      'heart_rate' => $heart_rate,
      'avg_cadence' => $avg_cadence,
      'weight' => $weight,
      'calories' => $calories,
      'elevation' => $elevation
    );
    $types = array(
      'integer',
      'integer',
      'integer',
      'timestamp',
      'text',
      'time',
      'float',
      'text',
      'float',
      'text',
      'float',
      'float',
      'float',
      'float'
    );

    $lid = $_POST['lid'];
    if (is_numeric($lid)) {
      //db_update('training_log', $values, $types, 'uid = '.db_quote($uid, 'integer').' AND lid = '.db_quote($lid, 'integer'));
    }
    else {
      db_insert('training_log', $values, $types);
      $lid = db_insert_id();
    }

    /*
     * Update tags.
     */
    if ($tags != null && count($ERROR_MSG) == 0) {
      db_delete('training_log_tag', 'lid = '.db_quote($lid, 'integer'));
      foreach ($tags as $t) {
        if (!empty($t) && strlen($t) < 64) {
          $result = db_select('training_tag', array('tid' => 'integer'), 'title = '.db_quote($t, 'text'));
          if ($result->num_rows > 0) {
            $tid = $result->fetch_row()[0];
          } else {
            db_insert('training_tag', array('title' => $t), array('text'));
            $tid = db_insert_id();
          }
          $result->close();

          db_insert('training_log_tag', array('lid' => $lid, 'tid' => $tid), array('integer', 'integer'));
        }
      }
    }

    if (count($ERROR_MSG) == 0) {
      return $lid;
    }
  }
  return $ERROR_MSG;
}
?>
