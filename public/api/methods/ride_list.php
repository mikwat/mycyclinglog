<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

function method_ride_list($uid, $offset, $limit, $user_unit = 'mi') {
  $query = "
    SELECT COUNT(*)
    FROM training_log
    WHERE uid = ".db_quote($uid, 'integer');
  $result = db_query($query);
  $total_size = $result->fetch_row()[0];
  $result->close();

  $query = "
    SELECT
      l.lid,
      l.event_date,
      l.distance,
      l.time,
      l.distance / (TIME_TO_SEC(l.time) / 3600.0) AS avg_speed,
      l.notes,
      l.heart_rate,
      l.max_speed,
      l.avg_cadence,
      l.weight,
      l.calories,
      l.elevation,
      l.is_ride,
      b.bid,
      CONCAT(b.make,' ',b.model) AS bike,
      COUNT(c.cid) AS comments,
      l.rid,
      r.name AS route_name
    FROM
      training_log l LEFT OUTER JOIN
      training_bike b ON l.bid = b.bid LEFT OUTER JOIN
      training_comment c ON l.lid = c.lid LEFT OUTER JOIN
      training_route r ON l.rid = r.rid
    WHERE l.uid = ".db_quote($uid, 'integer')."
    GROUP BY
      l.lid, l.event_date, l.distance, l.time, l.notes, l.heart_rate, l.max_speed, l.avg_cadence, l.weight,
      l.calories, l.elevation, l.is_ride, b.make, b.model
    ORDER BY l.event_date DESC, l.last_modified DESC
    LIMIT $offset, $limit";
  $result = db_query($query);
  $rval = '<list offset="'.$offset.'" limit="'.$limit.'" total_size="'.$total_size.'">';
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $rval .= '<item id="'.$row['lid'].'">';
        $rval .= '<event_date>'.date_format_std($row['event_date']).'</event_date>';
        $rval .= '<is_ride>'.($row['is_ride'] == "T" ? 'true' : 'false').'</is_ride>';
        $rval .= '<distance units="'.$user_unit.'">'.unit_format($row['distance'], $user_unit).'</distance>';
        $rval .= '<time>'.$row['time'].'</time>';
        $rval .= '<notes>'.export_rss($row['notes']).'</notes>';
        $rval .= '<heart_rate>'.export_rss($row['heart_rate']).'</heart_rate>';
        $rval .= '<max_speed>'.export_rss($row['max_speed']).'</max_speed>';
        $rval .= '<avg_cadence>'.export_rss($row['avg_cadence']).'</avg_cadence>';
        $rval .= '<weight>'.export_rss($row['weight']).'</weight>';
        $rval .= '<calories>'.export_rss($row['calories']).'</calories>';
        $rval .= '<elevation>'.export_rss($row['elevation']).'</elevation>';
        $rval .= '<bike id="'.$row['bid'].'">'.export_rss($row['bike']).'</bike>';
        $rval .= '<route id="'.$row['rid'].'">'.export_rss($row['route_name']).'</route>';
      $rval .= '</item>';
    }
    $result->close();
  }
  $rval .= '</list>';
  return $rval;
}
?>
