<?php
include_once("common/common.inc.php");

session_check();

unset($ERROR_MSG);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  if (validate_email($email)) {
    $values = array(
      'username' => 'text',
      'auth_code' => 'text',
      'enabled' => 'text'
    );
    $result = db_select('training_user', $values, 'email = '.db_quote($email, 'text'));
    list($username, $auth_code, $enabled) = $result->fetch_row();
    $result->close();

    if ($enabled == "F") {
      $subject = _('My Cycling Log Registration');
      $body = "
"._('Please use following link to verify your email address:')."
https://".MCL_DOMAIN."/?a=$auth_code

"._('Username').": $username
"._('Password').": ["._('hidden for security')."]

".('The My Cycling Log Team')."
https://".MCL_DOMAIN;
      $success = aws_send_mail($email, $subject, $body);
      if (!$success) {
        $ERROR_MSG[] = _('There was an error sending the verification email.');
      }

      $subject = _('My Cycling Log Registration Problem');
      $body = "$email\nhttps://".MCL_DOMAIN."/?a=$auth_code";
      aws_send_mail(REPLY_EMAIL, $subject, $body);
    }
    else {
      $ERROR_MSG[] = _('Your email address has already been verified.');
    }
  }
  else {
    $ERROR_MSG[] = _('Email address is not valid.');
  }
}
$HEADER_TITLE = _('Problem Center');
include_once("common/header.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($ERROR_MSG)) { ?>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Problem Center') ?>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Another verification email has been sent.') ?>
    </td>
  </tr>
</table>

<?php } else { ?>

<form action="/problems.php" method="post">
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Problem Center') ?>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
  <tr>
    <td>
      <b>1.</b>
      <?php echo _('If your email address has been verified, but you have forgotten your username or password, retrieve them <a href="/retrieve.php">here</a>.') ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>2.</b>
      <?php echo _('If you did not receive a verification email, enter your email address below to have another sent.') ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>3.</b>
      <?php echo _('To try again, return to the login page <a href="/index.php">here</a>.') ?>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Email Address') ?>: *</td></tr>
  <tr>
    <td>
      <input name="email" type="text" size="25" class="formInput" value="<?php if ($_POST['email']) { echo export_clean($_POST['email']); } ?>"/>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('SUBMIT') ?>" class="btn"/>
    </td>
  </tr>
</table>

</form>

<?php } ?>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
