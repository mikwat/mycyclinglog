<?php
function email_comment($from_username, $to_email, $body, $uid, $lid, $eid) {
  $email_subject = $from_username.' '._('has left a comment for you on My Cycling Log');
  $email_body = $from_username.' '._('has left a comment for you.')."\n\n==========\n";
  $email_body .= $body."\n==========\n\n";
  $email_body .= _('To view this comment, follow the link below:')."\n";
  $email_body .= 'https://'.MCL_DOMAIN.'/?next_url='.urlencode('ride_detail.php?uid='.$uid.'&lid='.$lid.'&eid='.$eid);
  $success = aws_send_mail($to_email, $email_subject, $email_body);
  if (!$success) {
    mcl_error_log("Email Comment Error", "Error sending comment notification email [To: $to_email]");
  }
}
?>
