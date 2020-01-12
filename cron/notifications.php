<?php
// Run every 10 mins.
require_once(__DIR__."/../public/common/common.inc.php");

try {
  $query = "
    SELECT u.email, n.uid, n.title, n.body, n.id
    FROM notifications n INNER JOIN training_user u ON n.uid = u.uid
    WHERE n.sent IS NULL";
  $result = db_query($query);
  while ($row = $result->fetch_assoc()) {
    try {
      $success = aws_send_mail($row['email'], $row['title'], $row['body']);

      if ($success) {
        $query_update = "UPDATE notifications SET sent = NOW() WHERE id=".$row['id'];
        db_query($query_update);
      } else {
        echo "Notification Error: Error sending notification email [To: ".$row['email']."]";
      }
    } catch (Exception $e) {
      echo "Notification Error: Error: ".$e->getMessage()."\n\nID: ".$row['id'];
    }
  }
  $result->close();
} catch (Exception $e) {
  echo "Notification Error: ". $e->getMessage();
}
?>
