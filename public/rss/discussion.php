<?php
include_once("../common/common.inc.php");

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
  FROM training_message m INNER JOIN training_user u ON m.uid = u.uid
  WHERE m.removed = 0
  ORDER BY entry_date DESC
  LIMIT 0, 10";
$result = db_query($query);
$all_rows = $result->fetch_all();
$result->close();
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:mn="http://usefulinc.com/rss/manifest/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
>
<channel rdf:about="https://<?php echo MCL_DOMAIN ?>/rss/discussion.php">
  <title>My Cycling Log: General Discussion</title>
  <link>https://<?php echo MCL_DOMAIN ?>/discussion.php</link>
  <description>General Discussion on My Mycling Log.</description>

  <items>
    <rdf:Seq>
      <?php foreach ($all_rows as $row) { ?>
      <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/discussion.php#<?php echo $row['mid'] ?>"/>
      <?php } ?>
    </rdf:Seq>
  </items>

</channel>

<?php foreach ($all_rows as $row) { ?>
  <item rdf:about="https://<?php echo MCL_DOMAIN ?>/discussion.php#<?php echo $row['mid'] ?>">
    <link>https://<?php echo MCL_DOMAIN ?>/discussion.php#<?php echo $row['mid'] ?></link>
    <title><?php
      echo export_rss(datetime_format_nice($row['entry_date'])." - ".stripslashes($row['title'])." - ");
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
        <rdf:li rdf:resource="https://<?php echo MCL_DOMAIN ?>/rss/discussion.php"/>
      </rdf:Seq>
    </mn:channels>
  </rdf:Description>
</rdf:RDF>
