<?php
include_once("../common/common.inc.php");

$gid = $_GET['gid'];
$result = db_select('training_group', array('name' => 'text', 'description' => 'text'), 'gid = '.db_quote($gid, 'integer'));
list($g_name, $g_desc) = $result->fetch_row();
$result->close();

header('Content-type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="iso-8859-1"?>';

$query = "
  SELECT
    m.mid,
    m.uid,
    u.username,
    u.location,
    m.title,
    m.body,
    m.entry_date
  FROM training_group_message m INNER JOIN training_user u ON m.uid = u.uid
  WHERE m.gid = ".db_quote($gid, 'integer')."
  ORDER BY entry_date DESC
  LIMIT 0, 10";
$result = db_query($query);
$all_rows = $result->fetch_all(MYSQLI_ASSOC);
$result->close();
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:mn="http://usefulinc.com/rss/manifest/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
>
<channel rdf:about="https://<?php echo MCL_DOMAIN ?>/rss/group_discussion.php?gid=<?php echo $gid ?>">
  <title>My Cycling Log: Group Discussion: <?php echo export_rss($g_name) ?></title>
  <link>https://<?php echo MCL_DOMAIN ?>/group_discussion.php?gid=<?php echo $gid ?></link>
  <description><?php echo export_rss($g_desc) ?></description>

  <items>
    <rdf:Seq>
      <?php foreach ($all_rows as $row) { ?>
      <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/group_discussion.php?gid=<?php echo $gid ?>#<?php echo $row['mid'] ?>"/>
      <?php } ?>
    </rdf:Seq>
  </items>

</channel>

<?php foreach ($all_rows as $row) { ?>
  <item rdf:about="https://<?php echo MCL_DOMAIN ?>/group_discussion.php?gid=<?php echo $gid ?>#<?php echo $row['mid'] ?>">
    <link>https:/<?php echo MCL_DOMAIN ?>/group_discussion.php?gid=<?php echo $gid ?>#<?php echo $row['mid'] ?></link>
    <title><?php
      echo export_rss(datetime_format_nice($row['entry_date'])." - ".$row['title']." - ");
      echo export_rss($row['username']." [".$row['location']."]");
    ?></title>
    <description><?php
      echo export_rss($row['body']);
    ?></description>
    <dc:date><?php echo date('Y-m-d H:m:s T', strtotime($row['entry_date'])) ?></dc:date>
  </item>
<?php } ?>

  <rdf:Description rdf:ID="manifest">
    <mn:channels>
      <rdf:Seq>
        <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/rss/group_discussion.php?gid=<?php echo $gid ?>"/>
      </rdf:Seq>
    </mn:channels>
  </rdf:Description>
</rdf:RDF>
