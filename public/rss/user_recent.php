<?php
include_once("../common/common.inc.php");

$uid = $_GET['uid'];

if (isset($_GET['unit'])) {
  if ($_GET['unit'] == 'km') {
    $user_unit = 'km';
  }
  else {
    $user_unit = 'mi';
  }
}
else {
  $result = db_select('training_user', array('username' => 'text', 'unit' => 'text'), 'uid = '.db_quote($uid, 'integer'));
  list($username, $user_unit) = $result->fetch_row();
}

$query = "
  SELECT
    l.uid,
    l.lid,
    l.event_date,
    l.last_modified,
    l.distance,
    l.time,
    l.notes,
    l.is_ride,
    CONCAT(b.make,' ',b.model) AS bike,
    l.rid,
    r.name AS route_name
  FROM
    training_log l LEFT OUTER JOIN
    training_bike b ON l.bid = b.bid LEFT OUTER JOIN
    training_route r ON l.rid = r.rid
  WHERE l.uid = ".db_quote($uid, 'integer')."
  ORDER BY l.event_date DESC, l.last_modified DESC
  LIMIT 0, 10";
$result = db_query($query);
$all_rows = $result->fetch_all(MYSQLI_ASSOC);
$result->close();

header('Content-type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="iso-8859-1"?>';
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:mn="http://usefulinc.com/rss/manifest/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
>
<channel rdf:about="https://<?php echo MCL_DOMAIN ?>/rss/user_recent.php?uid=<?php echo $uid ?>">
  <title>My Cycling Log: Recent Rides: <?php echo export_rss($username) ?></title>
  <link>https://<?php echo MCL_DOMAIN ?></link>
  <description>Most recent rides by <?php echo export_rss($username) ?> on My Mycling Log.</description>

  <items>
    <rdf:Seq>
      <?php foreach ($all_rows as $row) { ?>
      <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/ride_detail.php?uid=<?php echo $row['uid']."&amp;lid=".$row['lid'] ?>"/>
      <?php } ?>
    </rdf:Seq>
  </items>

</channel>

<?php foreach ($all_rows as $row) { ?>
  <item rdf:about="https://<?php echo MCL_DOMAIN ?>/ride_detail.php?uid=<?php echo $row['uid']."&amp;lid=".$row['lid'] ?>">
    <link>https://<?php echo MCL_DOMAIN ?>/ride_detail.php?uid=<?php echo $row['uid']."&amp;lid=".$row['lid'] ?></link>
    <title><?php
      $output = "";
      $output .= date('Y-m-d', strtotime($row['event_date'])).": ";
      $output .= unit_format($row['distance'], $user_unit)." ".$user_unit;
      if ($row['is_ride'] == "T") {
        $output .= " [Cycling]";
      }

      echo export_rss($output);
    ?></title>
    <description><?php
      $output = "";
      $output .= unit_format($row['distance'], $user_unit)." ".$user_unit;
      if ($row['distance'] > 0 && $row['time'] > 0) {
        $output .= " in ";
      }

      if ($row['time'] > 0) {
        $output .= $row['time']." hours";
      }

      if (!empty($row['bike'])) {
        $output .= " on ".$row['bike'];
      }

      if ($row['is_ride'] == "T") {
        $output .= ". [Cycling] ";
      }
      else {
        $output .= ". ";
      }

      echo export_rss($output);
      echo export_rss($row['notes']);

      if (!empty($row['rid'])) { ?>
        <img src="https://<?php echo MCL_DOMAIN ?>/images/globe.gif" width="16" height="16" alt="Route" align="absmiddle"/>
        <a href="https://<?php echo MCL_DOMAIN ?>/route_detail.php?rid=<?php echo $row['rid'] ?>"><?php echo export_rss($row['route_name']) ?></a>
      <?php }
    ?></description>
    <dc:date><?php echo date('Y-m-d H:m:s T', strtotime($row['last_modified'])) ?></dc:date>
  </item>
<?php } ?>

  <rdf:Description rdf:ID="manifest">
    <mn:channels>
      <rdf:Seq>
        <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/rss/user_recent.php?uid=<?php echo $uid ?>"/>
      </rdf:Seq>
    </mn:channels>
  </rdf:Description>
</rdf:RDF>
