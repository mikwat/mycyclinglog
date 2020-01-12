<?php
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
  header("Location: ../index.php");
}

function clean_csv_field($s) {
  $search = array("\r\n", "\n", "\r", "\"");
  $replace = array(" ", " ", " ", "'");
  return str_replace($search, $replace, $s);
}

function export_csv($query, $user_unit, $filename) {
  @ob_start();

  $content = "";
  $result = db_query($query);
  $fields = $result->fetch_fields();

  /*
   * Add header row
   */
  $header_row = [];
  foreach ($fields as $f) {
    $header_row[] = '"'.ucwords($f->name).'"';
  }
  $content .= implode(',', $header_row);
  $content .= "\r\n";

  /*
   * Add content rows
   */
  while ($row = $result->fetch_assoc()) {
    $content_row = [];
    foreach ($fields as $f) {
      if (in_array($f->name, array("distance", "avg speed", "max speed"))) {
        $content_roe[] = '"'.clean_csv_field(unit_format($row[$f->name], $user_unit)).'"';
      }
      else {
        $content_row[] = '"'.clean_csv_field($row[$f->name]).'"';
      }
    }
    $content .= implode(',', $content_row);
    $content .= "\r\n";
  }
  $result->close();

  /*
   * Output data
   */
  $output_file = 'mycyclinglog-' . $filename . '.csv';
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
?>
