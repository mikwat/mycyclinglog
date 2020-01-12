<?php
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;

$sdk = new Sdk([
  'version' => 'latest',
  'region'  => 'us-east-1'
]);

$GLOBALS['ses_client'] = $sdk->createSes();
$GLOBALS['sqs_client'] = $sdk->createSqs();
$GLOBALS['s3_client'] = $sdk->createS3();

function aws_upload_to_s3($file_path, $key, $bucket) {
  $client = $GLOBALS['s3_client'];
  try {
    error_log("Uploading file to $bucket:$key.");

    $result = $client->putObject([
      'Bucket' => $bucket,
      'Key' => $key,
      'SourceFile' => $file_path,
    ]);
  } catch (AwsException $e) {
    $msg = $e->getAwsRequestId() . ", ";
    $msg .= $e->getAwsErrorType() . ", ";
    $msg .= $e->getAwsErrorCode() . ", ";
    $msg .= $e->getResult();
    error_log($msg);

    return false;
  }

  return true;
}

function aws_send_mail($to, $subject, $body) {
  $types = array('email_bounced' => 'integer');
  $result = db_select('training_user', $types, 'email = '.db_quote($to, 'text'));
  $row = $result->fetch_assoc();
  $result->close();
  if ($row && $row['email_bounced'] == 1) {
    error_log("email_bounced set for $to. skipping.");
    return false;
  }

  $client = $GLOBALS['ses_client'];

  try {
    error_log("Sending email to [$to] subject [$subject].");

    $client->sendEmail([
      'Destination' => [ // REQUIRED
        /* 'BccAddresses' => ['<string>', ...], */
        /* 'CcAddresses' => ['<string>', ...], */
        'ToAddresses' => [$to],
      ],
      'Message' => [ // REQUIRED
        'Body' => [ // REQUIRED
          /* 'Html' => [ */
          /*   'Charset' => '<string>', */
          /*   'Data' => '<string>', // REQUIRED */
          /* ], */
          'Text' => [
            /* 'Charset' => '<string>', */
            'Data' => $body, // REQUIRED
          ],
        ],
        'Subject' => [ // REQUIRED
          /* 'Charset' => '<string>', */
          'Data' => $subject, // REQUIRED
        ],
      ],
      'ReplyToAddresses' => [REPLY_EMAIL],
      /* 'ReturnPath' => '<string>', */
      'ReturnPathArn' => AWS_SES_ARN,
      'Source' => 'My Cycling Log <' . REPLY_EMAIL . '>', // REQUIRED
      'SourceArn' => AWS_SES_ARN
    ]);
  } catch (AwsException $e) {
    $msg = $e->getAwsRequestId() . ", ";
    $msg .= $e->getAwsErrorType() . ", ";
    $msg .= $e->getAwsErrorCode() . ", ";
    $msg .= $e->getResult();
    error_log($msg);

    return false;
  }

  return true;
}

function aws_poll_bounced() {
  $client = $GLOBALS['sqs_client'];

  $result = $client->receiveMessage(array(
    'QueueUrl' => AWS_SQS,
    'MaxNumberOfMessages' => 10
  ));

  if (!isset($result['Messages'])) {
    echo "No messages.\n";
    return;
  }

  foreach ($result['Messages'] as $message) {
    $body = json_decode($message['Body'], true);
    $details = json_decode($body['Message'], true);
    if ($details['notificationType'] == 'Bounce') {
      $bounced_recipients = $details['bounce']['bouncedRecipients'];
      foreach ($bounced_recipients as $recipient) {
        $email = $recipient['emailAddress'];
        echo "Bounced $email.\n";
        db_update(
          'training_user',
          array('email_bounced' => 1),
          array('integer'),
          'email='.db_quote($email, 'text')
        );

        $client->deleteMessage(array(
          'QueueUrl' => AWS_SQS,
          'ReceiptHandle' => $message['ReceiptHandle']
        ));
      }
    }
  }
}
?>
