<?php
require_once('Cache/Lite.php');

$GLOBALS['cache'] = new Cache_Lite($CACHE_OPTIONS);
$GLOBALS['cache_hit_count'] = 0;
$GLOBALS['cache_save_count'] = 0;

function cache_get($key) {
  $data = $GLOBALS['cache']->get($key);
  $GLOBALS['cache_hit_count']++;
  //error_log('cache_get('.$key.')');
  return ($data)? unserialize($data) : false;
}

function cache_save($data, $key) {
  $GLOBALS['cache']->save(serialize($data), $key);
  $GLOBALS['cache_save_count']++;
  //error_log('cache_save('.$key.')');
  //mcl_error_log('Cache Save', $key);
}
?>
