<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

function method_bike_list($uid, $offset, $limit) {
  $query = "
    SELECT COUNT(*)
    FROM training_bike
    WHERE uid = ".db_quote($uid, 'integer');
  $result = db_query($query);
  $total_size = $result->fetch_row()[0];

  $query = "
    SELECT
      bid,
      make,
      model,
      year,
      enabled,
      is_default,
      iid,
      created_on
    FROM
      training_bike
    WHERE uid = ".db_quote($uid, 'integer')."
    ORDER BY created_on
    LIMIT $offset, $limit";
  $result = db_query($query);
  $rval = '<list offset="'.$offset.'" limit="'.$limit.'" total_size="'.$total_size.'">';
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $rval .= '<bike id="'.$row['bid'].'">';
        $rval .= '<make>'.export_rss($row['make']).'</make>';
        $rval .= '<model>'.export_rss($row['model']).'</model>';
        $rval .= '<year>'.$row['year'].'</year>';
        $rval .= '<enabled>'.($row['enabled'] == "T" ? 'true' : 'false').'</enabled>';
        $rval .= '<is_default>'.($row['is_default'] == "T" ? 'true' : 'false').'</is_default>';
        $rval .= '<created_on>'.date_format_std($row['created_on']).'</created_on>';
      $rval .= '</bike>';
    }
    $result->close();
  }
  $rval .= '</list>';
  return $rval;
}
?>
