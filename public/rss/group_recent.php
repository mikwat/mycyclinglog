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
  $result->close();
}

$query = "
  SELECT
    DISTINCT
    u.uid,
    l.lid,
    l.event_date,
    l.last_modified,
    u.username,
    u.location,
    l.time, l.distance, l.notes,
    l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS avg_speed,
    l.is_ride,
    CONCAT(b.make,' ',b.model) AS bike,
    l.rid,
    r.name AS route_name
  FROM
    training_log l INNER JOIN training_user u ON l.uid = u.uid INNER JOIN
    training_user_group ug ON u.uid = ug.uid LEFT OUTER JOIN
    training_bike b ON l.bid = b.bid LEFT OUTER JOIN
    training_route r ON l.rid = r.rid
  WHERE
    ug.gid IN (
      SELECT ug.gid
      FROM training_user u INNER JOIN training_user_group ug ON u.uid = ug.uid
      WHERE u.uid = ".db_quote($uid, 'integer')."
    )
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
<channel rdf:about="https://<?php echo MCL_DOMAIN ?>/rss/group_recent.php?uid=<?php echo $uid ?>">
  <title>My Cycling Log: Group Recent: <?php echo export_rss($username) ?></title>
  <link>https://<?php echo MCL_DOMAIN ?>/profile/<?php echo urlencode($username) ?></link>
  <description>The most recent rides from the groups of <?php echo export_rss($username) ?> on My Mycling Log.</description>

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
      $output .= $row['username'];
      $output .= " [".$row['location']."] ";
      $output .= unit_format($row['distance'], $user_unit)." ".$user_unit;
      if ($row['is_ride'] == "T") {
        $output .= " [Cycling]";
      }

      echo export_rss($output);
    ?></title>
    <description><?php
      $output = "";
      $output .= unit_format($row['distance'], $user_unit)." ".$user_unit;
      if ($row['distance'] > 0 && !empty($row['time'])) {
        $output .= " in ";
      }

      if (!empty($row['time'])) {
        $output .= $row['time']." hours";
      }

      if ($row['avg_speed'] > 0) {
        $output .= " at ".unit_format($row['avg_speed'], $user_unit)." ".$user_unit."/h";
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
        <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/rss/group_recent.php?uid=<?php echo $uid ?>"/>
      </rdf:Seq>
    </mn:channels>
  </rdf:Description>
</rdf:RDF>
