<?php
include_once("common/common.inc.php");

function clean_csv_field($s) {
  $search = array("\r\n", "\n", "\r", "\"");
  $replace = array(" ", " ", " ", "'");
  return str_replace($search, $replace, $s);
}

function export_csv($query, $user_unit) {
  @ob_start();

  $content = "";
  $result = db_query($query);
  $fields = $result->fetch_fields();

  /*
   * Add header row
   */
  $header = array_map(function($field) { return '"'.ucwords($field->name).'"'; }, $fields);
  $content .= implode(',', $header);
  $content .= "\r\n";

  /*
   * Add content rows
   */
  while ($row = $result->fetch_assoc()) {
    $content_row = [];
    foreach($fields as $field) {
      if (in_array($field->name, array("distance", "avg speed", "max speed"))) {
        $content_row[] = '"'.clean_csv_field(unit_format($row[$field->name], $user_unit)).'"';
      }
      else {
        $content_row[] = '"'.clean_csv_field($row[$field->name]).'"';
      }
    }
    $content .= implode(',', $content_row);
    $content .= "\r\n";
  }

  $result->close();

  /*
   * Output data
   */
  $output_file = 'mycyclinglog.csv';
  @ob_end_clean();
  @ini_set('zlib.output_compression', 'Off');

  header('Pragma: public');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');
  header('Content-Transfer-Encoding: none');
  //This should work for IE & Opera
  header('Content-Type: application/octetstream; name="' . $output_file . '"');
  //This should work for the rest
  header('Content-Type: application/octet-stream; name="' . $output_file . '"');
  header('Content-Disposition: inline; filename="' . $output_file . '"');
  echo $content;
  exit();
}

if ($_GET['uid'] > 0) {
  header("Location: user_view.php?uid=".$_GET['uid']);
  exit();
}

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

if ($_GET['e'] != 1) {
  $HEADER_TITLE = _('View');
  include_once("common/header.php");
  include_once("common/tabs.php");
}

/* setup sort urls */
$s = $_GET['s'];
switch ($s) {
  case "event_date":
  case "time":
  case "type":
  case "distance":
  case "heart_rate":
  case "avg_speed":
    $sortby = $s;
    break;
  default:
    $sortby = "event_date";
    $s = "event_date";
    break;
}

$d = $_GET['d'];
switch ($d) {
  case "a":
    $sortby .= " ASC";
    break;
  default:
    $sortby .= " DESC";
    $d = "d";
    break;
}

$url = "/view.php?";
$url_date = $url.sort_url($s, $d, "event_date");
$url_time = $url.sort_url($s, $d, "time");
$url_type = $url.sort_url($s, $d, "type");
$url_distance = $url.sort_url($s, $d, "distance");
$url_avg = $url.sort_url($s, $d, "avg_speed");
$url_heart = $url.sort_url($s, $d, "heart_rate");
$url_notes = $url.sort_url($s, $d, "notes");
$url_export = $url.sort_url($s, $d)."&e=1";

if ($_GET['e'] == 1) {
  $query = "
    SELECT
      l.event_date AS Date,
      l.is_ride AS 'Cycling',
      l.time AS Time,
      l.distance AS Distance,
      l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS 'Avg Speed',
      l.heart_rate AS 'Heart Rate',
      l.max_speed AS 'Max Speed',
      l.avg_cadence AS 'Avg Cadence',
      l.weight AS 'Weight',
      l.calories AS 'Calories',
      l.elevation AS 'Elevation',
      l.notes AS Notes,
      r.name AS 'Route Name',
      r.url AS 'Route Link',
      r.notes AS 'Route Notes'
    FROM
      training_log l LEFT OUTER JOIN
      training_route r ON l.rid = r.rid
    WHERE l.uid = ".db_quote($uid, 'integer')."
    ORDER BY $sortby, l.last_modified DESC";

  export_csv($query, $user_unit);
  exit();
}
else {
  $query = "
    SELECT
      l.lid,
      l.event_date,
      l.is_ride,
      l.time,
      l.distance,
      l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS avg_speed,
      l.notes,
      l.heart_rate,
      l.max_speed,
      l.avg_cadence,
      l.weight,
      l.calories,
      l.elevation,
      l.rid,
      r.name AS route_name
    FROM
      training_log l LEFT OUTER JOIN
      training_route r ON l.rid = r.rid
    WHERE l.uid = ".db_quote($uid, 'integer')."
    ORDER BY $sortby, l.last_modified DESC";
  $result = db_query($query);
}
?>
<script type="text/javascript">
function doDelete(lid) {
  var d = document.getElementById('delete_'+lid);
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
function showDetails(lid) {
  var d = document.getElementById('details_'+lid);
  overlib(d.innerHTML, WIDTH, -1);
}
</script>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td>
      <?php echo _('This is a complete list of all rides recorded.') ?>
    </td>
    <td class="inr">
      <a href="/rss/user_recent.php?uid=<?php echo $uid ?>"><img src="/images/rss.png" border="0" width="14" height="14" align="absmiddle" alt="RSS"/></a>
    </td>
  </tr>
</table>

<table align="center" width="100%" border="0" cellspacing="0" cellpadding="4" class="listbox">
  <tr>
    <td class="title"><a href="<?php echo $url_date ?>"><?php echo _('Date') ?></a></td>
    <td class="title"><a href="<?php echo $url_type ?>"><?php echo _('Type') ?></a></td>
    <td class="title"><a href="<?php echo $url_time ?>"><?php echo _('Time') ?></a></td>
    <td class="title">
      <a href="<?php echo $url_distance ?>"><?php echo _('Distance') ?></a> (<?php echo $user_unit ?>)
    </td>
    <td class="title"><a href="<?php echo $url_avg ?>"><?php echo _('Avg Speed') ?></a></td>
    <td class="title"><?php echo _('Details') ?></td>
    <td class="title"><?php echo _('Tags') ?></td>
    <td class="title">
      <table width="100%" border="0" cellpadding="0" cellspacing="0" class="noborbox">
        <tr>
          <td><?php echo _('Notes') ?></td>
          <?php if (!isset($_GET['uid'])) { ?>
            <td class="inr">
              <a href="<?php echo $url_export ?>"><?php echo _('Export to CSV') ?></a>
            </td>
          <?php } ?>
        </tr>
      </table>
    </td>
  </tr>
  <?php
  while ($row = $result->fetch_assoc()) {
    ?>
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="0" class="noborbox">
        <tr><td>
          <?php echo date_format_std($row['event_date']) ?>
        </td>
        <?php if (empty($_GET['uid'])) { ?>
          <td width="2" class="tah10">&nbsp;</td><td>
            <a href="/add.php?lid=<?php echo $row['lid'] ?>&next_url=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>"><img src="images/icon_edit.gif" border="0" alt="Edit"/></a>
          </td><td width="2"><img src="/images/spacer.gif" width="2"/></td><td>
          <div id="delete_<?php echo $row['lid'] ?>" style="display: none">
            <form action="/add.php" method="post">
              <input type="hidden" name="r" value="1"/>
              <input type="hidden" name="lid" value="<?php echo $row['lid'] ?>"/>
              <input type="hidden" name="next_url" value="<?php echo $_SERVER['REQUEST_URI'] ?>"/>
              <table border="0" cellspacing="0" cellpadding="2" class="noborbox"><tr>
                <td><input type="submit" value="<?php echo _('DELETE') ?>" class="btn"/></td>
                <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
              </tr></table>
            </form>
          </div>
          <a href="javascript:void(0)" onclick="doDelete(<?php echo $row['lid'] ?>)"><img src="images/icon_remove.gif" border="0" alt="<?php echo _('Remove') ?>"/></a>
          </td>
        <?php } ?>
        </tr></table>
      </td>
      <td>
        <?php echo ($row["is_ride"] == "T")? _('Cycling') : _('Other') ?>
      </td>
      <td>
        <?php echo ($row["time"] != "00:00:00")? $row["time"] : "" ?>
      </td>
      <td>
        <?php echo ($row["distance"] > 0)? unit_format($row["distance"], $user_unit) : "" ?>
      </td>
      <td>
        <?php echo ($row["avg_speed"] > 0)? unit_format($row["avg_speed"], $user_unit) : "" ?>
      </td>
      <td>
        <div id="details_<?php echo $row['lid'] ?>" style="display: none">
          <table width="100%" border="0" cellspacing="0" cellpadding="2" class="noborbox">
            <tr>
              <td class="title"><?php echo _('Max Speed') ?>:</td>
              <td><?php echo unit_format($row['max_speed'], $user_unit)?></td>
            </tr>
            <tr>
              <td class="title"><?php echo _('Heart Rate') ?>:</td>
              <td><?php echo export_clean($row['heart_rate']) ?></td>
            </tr>
            <tr>
              <td class="title"><?php echo _('Avg Cadence') ?>:</td>
              <td><?php echo $row['avg_cadence'] ?></td>
            </tr>
            <tr>
              <td class="title"><?php echo _('Weight') ?>:</td>
              <td><?php echo $row['weight'] ?></td>
            </tr>
            <tr>
              <td class="title"><?php echo _('Calories') ?>:</td>
              <td><?php echo $row['calories'] ?></td>
            </tr>
            <tr>
              <td class="title"><?php echo _('Elevation') ?>:</td>
              <td><?php echo $row['elevation'] ?></td>
            </tr>
          </table>
        </div>
        <a href="javascript:void(0)" onmouseover="return showDetails(<?php echo $row['lid'] ?>)" onmouseout="return nd()">
          <img src="images/icon_comments.gif" border="0" alt="<?php echo _('Details') ?>"/></a>
      </td>
      <td>
        <?php
        $t_query = "
          SELECT t.title
          FROM training_log_tag lt INNER JOIN training_tag t ON lt.tid = t.tid
          WHERE lt.lid = ".$row['lid'];
        $t_result = db_query($t_query);
        while ($t = export_clean($t_result->fetch_assoc()['title'])) {
          echo "<a href='/tag.php?t=$t&s=me'>$t</a> ";
        }
        $t_result->close();
        ?>
      </td>
      <td>
        <?php
        echo html_string_format($row['notes']);

        if (!empty($row['route_name'])) { ?>
          <div>
            <img src="/images/globe.gif" width="16" height="16" alt="Route" align="absmiddle"/>
            <a href="/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_clean($row['route_name']) ?></a>
          </div>
        <?php } ?>
      </td>
    </tr>
  <?php }
  $result->close();
  ?>
</table>

<?php include_once("common/footer.php"); ?>
