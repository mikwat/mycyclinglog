<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$GLOBALS['ERROR_MSG'] = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($_FILES['import_file']['error'] == 0 && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
    $filename = $uid . '-' . time() . '.csv';
    $success = aws_upload_to_s3($_FILES['import_file']['tmp_name'], $filename, S3_UPLOAD_BUCKET);
    if (!$success) {
      $GLOBALS['ERROR_MSG'][] = 'Unexpected error during upload.';
    }

    $body = $filename . "\n";
    aws_send_mail(REPLY_EMAIL, 'My Cycling Log: Upload', $body);
  } else {
    $GLOBALS['ERROR_MSG'][] = 'File missing.';
  }
}
$HEADER_TITLE = _('Importing Historic Data');
include_once("common/header.php");
include_once("common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($GLOBALS['ERROR_MSG'])) { ?>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Importing Historic Data') ?>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Your file has been uploaded, thanks. We will notify you once your data is available.') ?>
    </td>
  </tr>
</table>

<?php } else { ?>

<form enctype="multipart/form-data" action="/import.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Importing Historic Data') ?>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
  <tr>
    <td>
      <b>1.</b>
      <?php echo _('Download the <a href="/mycyclinglog_template.csv">import template</a>.') ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>2.</b>
      <?php echo _('Populate the template using Excel or any other spreadsheet application.') ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>3.</b>
      <?php echo _('Upload your template file below:') ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>Note.</b>
      <i><?php echo _('The <b>Date</b> column must be in one of the following forms:') ?></i>
      <ul>
        <li><tt>mm/dd/yyyy</tt></li>
        <li><tt>mm/dd/yy</tt></li>
        <li><tt>yyyy/mm/dd</tt></li>
        <li><tt>dd-mm-yyyy</tt></li>
        <li><tt>yy-mm-dd</tt></li>
        <li><tt>yyyy-mm-dd</tt></li>
      </ul>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Import File') ?>: *</td></tr>
  <tr>
    <td>
      <input name="import_file" type="file" size="25" class="formInput"/>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('UPLOAD') ?>" class="btn"/>
    </td>
  </tr>
</table>

</form>

<?php } ?>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
