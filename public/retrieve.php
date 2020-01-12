<?php
include_once("common/common.inc.php");

session_check();

$ERROR_MSG = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  if (validate_email($email)) {
    $values = array(
      'uid' => 'integer',
      'enabled' => 'text',
      'username' => 'text',
      'first_name' => 'text',
      'last_name' => 'text'
    );
    $result = db_select('training_user', $values, 'email = '.db_quote($email, 'text'));
    if ($result->num_rows == 1) {
      list($uid, $enabled, $username, $first_name, $last_name) = $result->fetch_row();
      if ($enabled == 'T') {
        $new_pass = make_random_password();
        $md5_new_pass = md5($new_pass);

        db_update('training_user', array('password' => $md5_new_pass), array('text'), 'uid = '.db_quote($uid, 'integer'));

        $subject = _('My Cycling Log Registration');
        $body = "
$first_name $last_name,

"._('Your password at My Cycling Log has been reset.')."

"._('Username').": $username
"._('Password').": $new_pass
"._('Homepage').": https://".MCL_DOMAIN."/profile/".urlencode($username)."

"._('Click the following link to login:')."
https://".MCL_DOMAIN."

"._('Once you login, go to the Account page and change your password to something familiar to you.')."

"._('The My Cycling Log Team')."
https://".MCL_DOMAIN;
        $success = aws_send_mail($email, $subject, $body);
        if ($success) {
          $ERROR_MSG[] = _('You have been sent a new password. Please check your email.');
        }
        else {
          $ERROR_MSG[] = _('There was an error sending the email.');
        }
      } else {
        $ERROR_MSG[] = _('Email address must be verified first. Please check your email for verification message.');
      }
    } else {
      $ERROR_MSG[] = _('Email address not found.');
    }
    $result->close();
  }
  else {
    $ERROR_MSG[] = _('Invalid email address.');
  }
}

$HEADER_TITLE = _('Reset Password');
include_once("common/header.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<form name="retrieve_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Username and Password Retrieval') ?>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Email Address') ?>: *</td></tr>
  <tr>
    <td>
      <input name="email" type="text" size="25" class="formInput" value="<?php if ($_POST['email']) { echo stripslashes($_POST['email']); } ?>"/>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('SUBMIT') ?>" class="btn"/>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
