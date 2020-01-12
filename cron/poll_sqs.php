<?php
// Run every 10 mins.
require_once(__DIR__."/../public/common/common.inc.php");

try {
  aws_poll_bounced();
} catch (Exception $e) {
  mcl_error_log("Poll SQS Error", $e->getMessage());
}
?>
