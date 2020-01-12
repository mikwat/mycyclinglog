<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/usr/local/lib/php');

/*
 * Constants.
 */
define('S3_UPLOAD_BUCKET', '***');
define('AWS_SES_ARN', '***');
define('AWS_SQS', '***');
define('DO_NOT_REPLY_EMAIL', '***');
define('REPLY_EMAIL', '***');
define('LINE_LENGTH', 50);
define('SQL_NAME', "IF(u.hide_name='F',CONCAT(u.first_name,' ',u.last_name),u.username)");
define('SQL_NAME_NA', "IF(hide_name='F',CONCAT(first_name,' ',last_name),username)");

/*
 * Event type constants.
 */
define('GROUP_MEMBERSHIP_REQUEST', 1);
define('SYSTEM_NOTIFICATION', 2);
define('RELATED_COMMENT', 3);

$COLORS = array("#0000FF", "#FF0000", "#00FF00", "#DDDDDD");
$MONTH_LABELS = array(1,2,3,4,5,6,7,8,9,10,11,12);
$LOCALE_LIST = array('en' => 'English', 'es' => 'Español');

/*
 * Domain.
 */
define('MCL_DOMAIN', getenv('MCL_DOMAIN') ?: 'www.mycyclinglog.com');

/*
 * Database.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', '***');
define('DB_PASS', '***');
define('DB_NAME', '***');

/*
 * Cache.
 */
define('CACHE_BASE', '/tmp/');
$CACHE_OPTIONS = array(
  'cacheDir' => CACHE_BASE,
  'lifeTime' => 30 * 60,
  'pearErrorMode' => 8 // CACHE_LITE_ERROR_DIE
);

define('REGISTRATION_DISABLED_FILE', '/var/www/registration-disabled');
?>
