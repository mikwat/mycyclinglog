<?php
// Run every hour.
require_once(__DIR__."/../public/common/common.inc.php");

ini_set('auto_detect_line_endings', true);

function find_in_r($search_name, $search_r) {
  while (list($name, $id) = each($search_r)) {
    if (trim(strtolower($name)) == trim(strtolower($search_name))) {
      return $id;
    }
  }
  return null;
}

error_log("Start import...");

$client = $GLOBALS['s3_client'];
// Register the stream wrapper from an S3Client object
$client->registerStreamWrapper();
$upload_bucket = 's3://'.S3_UPLOAD_BUCKET.'/';

if (!is_dir($upload_bucket)) {
  error_log("Bucket not found: $upload_bucket");
  exit(1);
}

$dh = opendir($upload_bucket);
if (!$dh) {
  error_log("Unable to open bucket: $upload_bucket");
  exit(1);
}

while (($file = readdir($dh)) !== false) {
  $filetype = filetype($upload_bucket.$file);
  if ($filetype !== 'file') {
    error_log("Skipping non-file: $file ($filetype)");
    continue;
  }

  list($uid, $_ignore) = explode('-', $file);

  $result = db_select('training_user', array('unit' => 'text'), 'uid='.db_quote($uid, 'integer'));
  list($user_unit) = $result->fetch_row()[0];
  $result->close();

  $bikes = array();
  $result = db_select('training_bike', array('bid' => 'integer', 'make' => 'text', 'model' => 'text'), 'uid='.db_quote($uid, 'integer'));
  while ($row = $result->fetch_assoc()) {
    $bikes[$row['make']." ".$row['model']] = $row['bid'];
  }
  $result->close();

  $routes = array();
  $result = db_select('training_route', array('rid' => 'integer', 'name' => 'text'), 'uid='.db_quote($uid, 'integer'));
  while ($row = $result->fetch_assoc()) {
    $routes[$row['name']] = $row['rid'];
  }
  $result->close();

  db_begin();
  $GLOBALS['ERROR_MSG'] = [];

  error_log("Parsing file: $file");
  $insert_ids = array();
  $row = 0;

  $csv_handle = fopen($upload_bucket.$file, "r");
  while (($data = fgetcsv($csv_handle, 1000, ",")) !== FALSE) {
    if (++$row == 1) {
      continue;
    }

    $event_date = strtotime($data[0]);
    if ($event_date === FALSE) {
      error_log("Unable to parse date: ".$data[0]);
      $GLOBALS['ERROR_MSG'][] = "Unable to parse date: ".$data[0]."\n";
      continue;
    }

    $time = preg_split("/[\:\.]/", $data[2]);
    if (empty($data[2])) {
      $time = array("00", "00", "00");
    }
    elseif (count($time) != 3) {
      error_log("Unable to parse time: ".$data[2]);
      $GLOBALS['ERROR_MSG'][] = "Unable to parse time: ".$data[2]."\n";
      continue;
    }

    $bid = null;
    if (!empty($data[11])) {
      $bid = find_in_r($data[11], $bikes);
    }

    $rid = null;
    if (!empty($data[12])) {
      $rid = find_in_r($data[12], $routes);
    }

    $values = array(
      'uid' => $uid,
      'event_date' => date("Y-m-d", $event_date),
      'is_ride' => (empty($data[1]) || $data[1] !== "F") ? "T" : "F",
      'time' => $time[0].":".$time[1].":".$time[2],
      'distance' => $user_unit == "km" ? km_to_m($data[3]) : $data[3],
      'notes' => $data[10],
      'max_speed' => $user_unit == "km" ? km_to_m($data[5]) : $data[5],
      'heart_rate' => $data[4],
      'avg_cadence' => $data[6],
      'weight' => $data[7],
      'calories' => $data[8],
      'elevation' => $data[9],
      'bid' => $bid,
      'rid' => $rid,
      'source' => 'CSV'
    );
    $types = array(
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
      'float',
      'integer',
      'integer',
      'text'
    );

    error_log("Importing row $row: ".implode(', ', $values));
    db_insert('training_log', $values, $types);
    $insert_ids[] = db_insert_id();
  }

  fclose($csv_handle);

  if (db_end() === false) {
    error_log("Rollback, errors found!");
  } else {
    error_log("Insert IDs: ".implode(",", $insert_ids));
  }

  // TODO: send email?

  if (rename($upload_bucket.$file, $upload_bucket."done/".$file) === false) {
    error_log("Error moving file: $upload_bucket$file to ".$upload_bucket."done/$file");
  }
}

error_log("Done import.");
?>
