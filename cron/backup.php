<?php
// Run every day.
require_once(__DIR__."/../public/common/common.inc.php");

ini_set('auto_detect_line_endings', true);

$name = getenv('BACKUP_NAME');
$type = getenv('BACKUP_TYPE');
$bucket = getenv('BACKUP_BUCKET');

// when $type == 'file'
$file_ext = getenv('BACKUP_FILE_EXT');
$file = getenv('BACKUP_FILE');

// when $type == 'db'
$dns = dns_get_record(getenv('DB_HOST'), DNS_SRV); // Check for DNS SRV record (used by ECS).
if (count($dns) > 0) {
  $db_host = $dns[0]['target'];
} else {
  $db_host = getenv('DB_HOST');
}
$db_name = getenv('BACKUP_DB_NAME');
$db_user = getenv('BACKUP_DB_USER');
$db_pass = getenv('BACKUP_DB_PASS');

$TEMP_DIR = '/tmp';

error_log("Start $name backup...");

function upload_to_s3($bucket, $key, $file) {
  $result = $GLOBALS['s3_client']->putObject(array(
    'Bucket' => $bucket,
    'Key' => $key,
    'SourceFile' => $file
  ));

  error_log("S3 upload results: $result");
}

try {
  if ($type == 'file') {
    upload_to_s3($bucket, $name.'-'.date('Ymd').$file_ext, $file);
  } else {
    $db_file = $db_name.'.sql';
    $temp_file = "$TEMP_DIR/$db_file";

    $result = exec("mysqldump -h $db_host -u $db_user -p$db_pass $db_name > $temp_file");
    error_log("mysqldump: $result");

    $result = exec("tar -czv -C $TEMP_DIR -f $temp_file.gz $db_file");
    error_log("tar: $result");

    upload_to_s3($bucket, $name.'-'.date('Ymd').'.sql.gz', "$temp_file.gz");
  }
} catch (Exception $e) {
  error_log("Backup error: ".$e->getMessage());
}

error_log("Done $name backup.");
?>
