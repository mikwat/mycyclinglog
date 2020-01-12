<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

// initialize globally
$GLOBALS['ERROR_MSG'] = [];

function blacklisted_domain($email) {
  $DOMAINS = array(
    'apoimail\.com',
    'asdfmail\.net',
    'asdfasdfmail\.com',
    'asdfasdfmail\.net',
    'asdooeemail\.com',
    'asooemail\.com',
    'asooemail\.net',
    'dfoofmail\.com',
    'fghmail\.net',
    'hotmails\.com',
    'lightengroups\.com',
    'mail\.ru',
    'mailapso\.com',
    'mailasdkr\.com',
    'mailsdfsdf\.com',
    'mailsdfsdf\.net',
    'qwkcmail\.com',
    'qwkcmail\.net',
    'rtotlmail\.com',
    'rtotlmail\.net',
    'telmail\.top',
    'toerkmail\.com'
  );
  $regex = "/".implode("|", $DOMAINS)."$/";
  return preg_match($regex, $email);
}

function bot_check() {
  $BOTS = array(
    "AhrefsBot",
    "AcoonBot",
    "Baiduspider",
    "bingbot",
    "BiggerBetter",
    "DoCoMo",
    "DotBot",
    "Elefent",
    "Exabot",
    "Ezooms",
    "Fever",
    "Googlebot",
    "istellabot",
    "JikeSpider",
    "Linguee\ Bot",
    "MegaIndex\.ru",
    "msnbot",
    "QuiteRSS",
    "Qwantify",
    "SemrushBot",
    "Sogou",
    "Sosospider",
    "Speedy\ Spider",
    "Spinn3r",
    "VoilaBot",
    "YandexBot",
    "YandexImages",
    "YoudaoBot",
    "80legs"
  );
  $regex = "/".implode("|", $BOTS)."/";
  if (isset($_SERVER['HTTP_USER_AGENT']) &&
      preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
    return 1;
  }
  return 0;
}

if (bot_check() == 1) {
  header("HTTP/1.1 403 Forbidden");
  exit();
}

require_once(__DIR__.'/util/config.inc.php');
require_once(__DIR__.'/../../vendor/autoload.php'); // composer autoloader
require_once(__DIR__.'/util/aws.inc.php');
require_once(__DIR__.'/util/db.inc.php');
require_once(__DIR__.'/util/cache.inc.php');
require_once(__DIR__.'/util/session.inc.php');
require_once(__DIR__.'/util/tag.inc.php');
require_once(__DIR__.'/util/email.inc.php');

function mcl_error_log($title, $body, $email_only = false) {
  if ($email_only === false) {
    error_log($title.": ".$body);
  }
}

function make_form_key() {
  $key = time() + rand(1, getrandmax());
  $_SESSION['form_key'] = $key;

  return $key;
}

function check_form_key($form_key) {
  $rval = ($form_key == $_SESSION['form_key']);
  unset($_SESSION['form_key']);
  return $rval;
}

function create_notification($uid, $title, $body) {
  $values = array('uid' => $uid, 'title' => $title, 'body' => $body);
  $types = array('integer', 'text', 'text');
  db_insert('notifications', $values, $types);;

  return db_insert_id();
}

function create_event($uid, $etid, $title, $link) {
  $values = array(
    'uid' => $uid,
    'etid' => $etid,
    'title' => $title,
    'link' => $link
  );
  $types = array(
    'integer',
    'integer',
    'text',
    'text'
  );
  db_insert('training_event', $values, $types);

  return db_insert_id();
}
function read_event($eid) {
  db_update('training_event', array('addressed' => 'T'), array('text'), 'eid='.db_quote($eid, 'integer'));
}

/*
 * http://www.carboncounter.org/offset-your-emissions/calculations-explained.aspx
 * 19.36 = the amount of pounds of carbon dioxide that is emitted as a result of burning one gallon of gasoline
 * 2,205 = the number of pounds in a metric ton
 */
function get_co2($distance, $dpg) {
  if ($dpg > 0) {
    return number_format((($distance / $dpg) * 19.36) / 2205, 2, '.', '');
  }

  return 0;
}

function encode_params($params) {
  $encoded_params = array();
  foreach ($params as $key => $val) {
    $encoded_params[] = urlencode($key).'='.urlencode($val);
  }

  return $encoded_params;
}

function get_phpobject($url) {
  $ch = curl_init();
  $timeout = 5; // set to zero for no timeout
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $response = curl_exec($ch);
  curl_close($ch);

  if ($response !== false) {
    return unserialize($response);
  }

  return NULL;
}

function is_error() {
  return (isset($GLOBALS['ERROR_MSG']) && sizeof($GLOBALS['ERROR_MSG']) > 0);
}

function print_error() {
  ?><span style="color: #F00">
    <?php
    if (isset($GLOBALS['ERROR_MSG']) && is_array($GLOBALS['ERROR_MSG'])) {
      foreach($GLOBALS['ERROR_MSG'] as $msg) {
        echo $msg . "<br/>";
      }
    }
    ?>
  </span><?php
}

function ordinalize($num) {
  if (!is_numeric($num)) {
    return $num;
  }

  if ($num >= 11 and $num <= 19) {
    return $num."th";
  }
  elseif ($num % 10 == 1) {
    return $num."st";
  }
  elseif ($num % 10 == 2) {
    return $num."nd";
  }
  elseif ($num % 10 == 3) {
    return $num."rd";
  }
  else {
    return $num."th";
  }
}

function truncate_string($s, $l) {
  if (empty($s) || strlen($s) <= $l) {
    return $s;
  }

  $r = "";
  for ($i = 0; $i < $l; $i++) {
    $r .= substr($s, $i, 1);
  }

  return $r."...";
}
function split_string($s, $l) {
  return wordwrap(stripslashes($s), $l, "\n", 1);
}
function stripnewlines($s) {
  $search = array("\r\n", "\n", "\r");
  return str_replace($search, " ", $s);
}

function rss_safe($string) {
   return str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $string);
}
function javascript_safe($string) {
   return str_replace(array('<', '>'), array('&lt;', '&gt;'), $string);
}

function export_rss($s) {
  $s = stripnewlines($s);
  $s = rss_safe($s);
  return stripslashes($s);
}
function export_clean($s) {
  $s = stripnewlines($s);
  $s = javascript_safe($s);
  return stripslashes($s);
}

function html_string_format($s, $l = LINE_LENGTH) {
  $s = javascript_safe($s);
  // Disable links to discourage bots
  //$s = preg_replace('/(http:\/\/[\?\/\.a-z0-9#&-~_%$+]+)/ie', "'<a href=\"\\1\">'.truncate_string('\\1', $l).'</a>'", $s);
  $s = nl2br($s);
  return $s;
}

function convert_time_zone($timeFromDatabase, $format) {
  $userTime = new DateTime($timeFromDatabase);

  if (isset($_SESSION['timezone'])) {
    $userTime->setTimezone(new DateTimeZone($_SESSION['timezone']));
  }

  return $userTime->format($format);
}

function date_format_nice($mysql_date) {
  return convert_time_zone($mysql_date, 'D, M j, Y');
}

function date_format_std($mysql_date) {
  return convert_time_zone($mysql_date, 'm/d/Y');
}

function datetime_format_nice($mysql_datetime) {
  return convert_time_zone($mysql_datetime, 'D n/j g:ia T');
}

function datetime_format($mysql_datetime) {
  return convert_time_zone($mysql_datetime, 'm/d/Y g:ia T');
}

function seconds_to_time($input) {
  list($total_mins, $display_secs) = gmp_div_qr($input, 60);
  list($display_hours, $display_mins) = gmp_div_qr($total_mins, 60);
  return str_pad($display_hours, 2, '0', STR_PAD_LEFT).':'.
         str_pad($display_mins, 2, '0', STR_PAD_LEFT).':'.
         str_pad($display_secs, 2, '0', STR_PAD_LEFT);
}

function unit_convert($val, $unit) {
  if ($unit == 'km') {
    return m_to_km($val);
  }

  return $val;
}

function unit_format($val, $unit = 'mi', $separators = true) {
  if ($unit == 'km') {
    return m_to_km($val, 2);
  }

  if ($separators) {
    return number_format($val, 2, '.', '');
  }
  else {
    return $val;
  }
}

function km_to_m($km, $p = null) {
  $val = 0.621371192 * $km;
  if ($p !== null && is_numeric($p)) {
    return number_format($val, $p, '.', '');
  }
  return $val;
}

function m_to_km($m, $p = null) {
  $val = 1.609344 * $m;
  if ($p !== null && is_numeric($p)) {
    return number_format($val, $p, '.', '');
  }
  return $val;
}

function validate_email($email) {
  $regex = "/^[_a-z0-9-]+([\.|\+][_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";
  return preg_match($regex, $email);
}

function validate_route($url) {
  return preg_match("/bikely\.com/", $url) || preg_match("/gpsies\.com/", $url) || preg_match("/mapmyride\.com/", $url) ||  preg_match("/mapmyfitness\.com/", $url) || preg_match("/cyclogz\.com/", $url) ||
    preg_match("/everytrail\.com/", $url) || preg_match("/bikemap\.net/", $url) || preg_match("/connect\.garmin\.com/", $url);
}

function display_route($row) {
  if (empty($row['route_url'])) {
    $row['route_url'] = $row['url'];
    $row['route_name'] = $row['name'];
  }
  if (preg_match("/gpsies\.com/", $row['route_url'])) {
    preg_match('/fileId\=(.+)[\&\#]?/', $row['route_url'], $matches); // http://www.gpsies.com/mapOnly.do?fileId=pryjdountkvlcmbm
    if ($matches[1]) {
      ?>
      <iframe src="http://www.gpsies.com/mapOnly.do?fileId=<?php echo $matches[1] ?>" width="350" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" title="<?php echo $row['route_name'] ?>"></iframe>
      <?php
    }
  } elseif (preg_match("/mapmyride\.com/", $row['route_url']) || preg_match("/mapmyfitness\.com/", $row['route_url'])) {
    preg_match('/\?r\=(.+)\&/', $row['route_url'], $matches); // http://js.mapmyfitness.com/embed/blogview.html?r=2733eb1aca33f1454491863be9962776&u=e&t=route
    if ($matches[1]) {
      ?>
      <iframe src="http://js.mapmyfitness.com/embed/blogview.html?r=<?php echo $matches[1] ?>&u=e&t=route" height="500px" width="350px" frameborder="0"></iframe>
      <?php
    } else {
      preg_match('/\/([0-9]+)[\&\#]?/', $row['route_url'], $matches); // http://www.mapmyfitness.com/ride/united-states/ca/aptos/626125039767729015
      if ($matches[1]) {
        ?>
        <iframe src="http://js.mapmyfitness.com/embed/blogview.html?r=<?php echo $matches[1] ?>&u=e&t=route" height="500px" width="350px" frameborder="0"></iframe>
        <?php
      }
    }
  } elseif (preg_match("/bikely\.com/", $row['route_url'])) {
    preg_match('/\/bike-path\/(.+)[\&\#]?/', $row['route_url'], $matches); // http://www.bikely.com/maps/bike-path/Cartwright-Loop
    if ($matches[1]) {
      ?>
      <div id="routemapiframe" style="width: 350px; border: 1px solid #d0d0d0; background: #755; overflow: hidden; white-space: nowrap;">
      <span style="display: block; font: bold 11px verdana, arial; padding: 2px;"><a style="color: #fff; text-decoration: none" href="http://www.bikely.com/maps/bike-path/<?php echo $matches[1] ?>"><?php echo $row['route_name'] ?></a></span>
      <iframe id="rmiframe" style="height:360px;  background: #eee;" width="100%" frameborder="0" scrolling="no" src="http://www.bikely.com/maps/bike-path/<?php echo $matches[1] ?>/embed/1"></iframe>
      <span style="display: block; font: normal 10px verdana, arial; text-align: right; padding: 1px;"><a style="color: #ddd; text-decoration: none" href="http://www.bikely.com/">Share your bike routes @ Bikely.com</a></span>
      </div>
      <?php
    }
  } elseif (preg_match("/cyclogz\.com/", $row['route_url'])) {
    preg_match('/\/activity\/([0-9]+)[\&\#]?/', $row['route_url'], $matches); // http://www.cyclogz.com/activity/553
    if ($matches[1]) {
      ?>
      <iframe src="http://www.cyclogz.com/activity/<?php echo $matches[1] ?>/embed-map" width="350" height="500" frameborder="0"></iframe>
      <?php
    }
  } elseif (preg_match("/everytrail\.com/", $row['route_url'])) {
    preg_match('/trip_id\=([0-9]+)[\&\#]?/', $row['route_url'], $matches); // http://www.everytrail.com/view_trip.php?trip_id=104810
    if ($matches[1]) {
      ?>
      <iframe src="http://www.everytrail.com/iframe2.php?trip_id=<?php echo $matches[1] ?>&width=350&height=400" marginheight=0 marginwidth=0 frameborder=0 scrolling=no width=350 height=400></iframe>
      <br/>Map created by EveryTrail: <a href="http://www.everytrail.com">Travel Community</a>
      <?php
    }
  } elseif (preg_match("/bikemap\.net/", $row['route_url'])) {
    preg_match('/\/route\/([0-9]+)[\&\#]?/', $row['route_url'], $matches); // http://www.bikemap.net/route/244279
    if ($matches[1]) {
      ?>
      <div style="margin-top:2px;margin-bottom:2px;width:350px;font-family:Arial,Helvetica,sans-serif;font-size:9px;color:#535353;background-color:#ffffff;border:2px solid #2a88ac;font-style:normal;text-align:right;padding:0px;padding-bottom:3px !important;"><iframe src="http://www.bikemap.net/route/<?php echo $matches[1] ?>/widget?width=350&amp;height=400&amp;maptype=2&amp;extended=true&amp;unit=km&amp;redirect=no" width="350" height="515" border="0" frameborder="0" marginheight="0" marginwidth="0"  scrolling="no"></iframe><br />Bike route <a style="color:#2a88ac; text-decoration:underline;" href="http://www.bikemap.net/route/<?php echo $matches[1] ?>"><?php echo $matches[1] ?></a> - powered by <a style="color:#2a88ac; text-decoration:underline;" href="http://www.bikemap.net">Bikemap</a>&nbsp;</div>
      <?php
    }
  } elseif (preg_match("/connect\.garmin\.com/", $row['route_url'])) {
    preg_match('/\/activity\/([0-9]+)[\&\#]?/', $row['route_url'], $matches); // http://connect.garmin.com/activity/31891180
    if ($matches[1]) {
      ?>
      <iframe width='465' height='548' frameborder='0' src='http://connect.garmin.com:80/activity/embed/<?php echo $matches[1] ?>'></iframe>
      <?php
    }
  }
}

function sort_url($s, $d, $col = null) {
  if ($col === null) {
    return "s=".$s."&d=".$d;
  }

  $url = "s=".$col."&d=";
  if ($s == $col) {
    $url .= ($d == "a")? "d" : "a"; // switch direction
  }
  else {
    $url .= "d"; // default direction
  }

  return $url;
}
?>
