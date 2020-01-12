<?php
define('DB_MAX_QUERY_TIME', 10.0);
$GLOBALS['db_query_count'] = 0;
$GLOBALS['db_query_time'] = 0;

/*
 * DB connection setup.
 */
function db_connect() {
  try {
    // Check for DNS SRV record (used by ECS).
    $dns = dns_get_record(DB_HOST, DNS_SRV);
    if (count($dns) > 0) {
      $host = $dns[0]['target'];
    } else {
      $host = DB_HOST;
    }

    $mysqli = new mysqli($host, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
      error_log('Failed to connect to MySQL: ' . $mysqli->connect_error);
      http_response_code(503);
      exit();
    }
    $GLOBALS['db_connection'] = $mysqli;
  } catch (Exception $e) {
    error_log('Failed to connect to MySQL: ', $e->getMessage());
    http_response_code(503);
    exit();
  }
}
db_connect();

/*
 * Issue SELECT statement.
 *
 * $table = 'user';
 * $types = array('name' => 'text', 'country' => 'text');
 * $where = 'id = '.$mysqli->quote(1, 'integer');
 */
function db_select($table, $types, $where) {
  $mysqli = $GLOBALS['db_connection'];
  $start = microtime(true);
  $sql = 'SELECT `'.implode('`,`', array_keys($types)).'` FROM `'.$table.'`';
  if ($where) {
    $sql .= ' WHERE '.$where;
  }
  $res = $mysqli->query($sql);
  $end = microtime(true);

  db_is_error($mysqli->error, $start, $end, 'Table: '.$table.' Where: '.$where);

  return $res;
}

/*
 * Issue UPDATE statement.
 *
 * $table = 'user';
 * $values = array('name' => 'Fabien', 'country' => 'France');
 * $types = array('text', 'text');
 * $where = 'id = '.$mysqli->quote(1, 'integer');
 */
function db_update($table, $values, $types, $where) {
  $mysqli = $GLOBALS['db_connection'];
  $start = microtime(true);
  $sql = 'UPDATE `'.$table.'` SET `'.implode('`=?,`', array_keys($values)).'`=? WHERE '.$where;
  $stmt = $mysqli->prepare($sql);
  $stmt_types = implode('', array_map('small_types', $types));
  $stmt->bind_param($stmt_types, ...array_values($values));
  $stmt->execute();
  $end = microtime(true);

  db_is_error($stmt->error, $start, $end, 'Table: '.$table.' Where: '.$where);

  $stmt->close();
}

/*
 * Issue INSERT statement.
 *
 * $table = 'user';
 * $values = array('name' => 'Fabien', 'country' => 'France');
 * $types = array('text', 'text');
 */
function db_insert($table, $values, $types) {
  $mysqli = $GLOBALS['db_connection'];
  $start = microtime(true);
  $sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`', array_keys($values)).'`) ';
  $sql .= 'VALUES ('.implode(',', array_map(function($_v) { return '?'; }, $values)).')';
  $stmt = $mysqli->prepare($sql);
  $stmt_types = implode('', array_map('small_types', $types));
  $stmt->bind_param($stmt_types, ...array_values($values));
  $stmt->execute();
  $end = microtime(true);

  db_is_error($stmt->error, $start, $end, $table);

  $stmt->close();
}

/*
 * Issue DELETE statement.
 *
 * $table = 'user';
 * $where = 'id = '.$mysqli->quote(1, 'integer');
 */
function db_delete($table, $where) {
  $mysqli = $GLOBALS['db_connection'];
  $start = microtime(true);
  $sql = 'DELETE FROM `'.$table.'` WHERE '.$where;
  $mysqli->query($sql);
  $end = microtime(true);

  db_is_error($mysqli->error, $start, $end, 'Table: '.$table.' Where: '.$where);
}

/*
 * Issue any query.
 */
function db_query($query) {
  $mysqli = $GLOBALS['db_connection'];
  $start = microtime(true);
  $res = $mysqli->query($query);
  $end = microtime(true);

  db_is_error($mysqli->error, $start, $end, $query);

  return $res;
}

function db_is_error($error, $start = 0, $end = 0, $more = '') {
  $GLOBALS['db_query_time'] += $end - $start;
  $GLOBALS['db_query_count']++;
  if (!empty($error)) {
    $mysqli = $GLOBALS['db_connection'];
    $GLOBALS['ERROR_MSG'][] = $error;
    $error_title = $error;
    $error_body = "URI: ".$_SERVER['REQUEST_URI']."\n\n" .
      "Message: $error_title\n\n" .
      "$more";
    mcl_error_log($error_title, $error_body);
  }
}

function db_error_log($uid, $description) {
  db_insert('errors', array('uid' => $uid, 'description' => $description), array('integer', 'text'));
}

function db_insert_id() {
  $mysqli = $GLOBALS['db_connection'];
  return $mysqli->insert_id;
}

function db_quote($value, $type) {
  $mysqli = $GLOBALS['db_connection'];
  $escaped = $mysqli->real_escape_string($value);
  if (is_null($value)) {
    return 'NULL';
  }
  if ($type === 'text' || $type === 'timestamp') {
    return "'".$escaped."'";
  }
  return $escaped;
}

function db_now() {
  return 'CURRENT_TIMESTAMP';
}

function db_begin() {
  $mysqli = $GLOBALS['db_connection'];
  $mysqli->begin_transaction();
}

function small_types($v) {
  switch ($v) {
    case 'text':
    case 'timestamp':
    case 'time':
      return 's';
    case 'integer':
      return 'i';
    case 'float':
      return 'd';
    default:
      return 'b';
  }
}

function db_end() {
  $mysqli = $GLOBALS['db_connection'];

  $commit = true;
  if (isset($GLOBALS['ERROR_MSG']) && count($GLOBALS['ERROR_MSG']) > 0) {
    $mysqli->rollback();
    $commit = false;
  } else {
    $mysqli->commit();
  }

  return $commit;
}
?>
