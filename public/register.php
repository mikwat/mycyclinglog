<?php
include_once('common/common.inc.php');

session_check();

if (is_file(REGISTRATION_DISABLED_FILE)) {
  header('Location: registration_disabled.php');
  exit();
}

$ERROR_MSG = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!empty($_POST['password1']) && !empty($_POST['password2'])) {
    if ($_POST['password1'] != $_POST['password2']) {
      $ERROR_MSG[] = 'Passwords do not match.';
    }
    else {
      $md5_password = md5($_POST['password1']);

      if (empty($_POST['first_name'])) {
        $ERROR_MSG[] = _('First name is required.');
      } else {
        $first_name = $_POST['first_name'];
      }

      if (empty($_POST['last_name'])) {
        $ERROR_MSG[] = _('Last name is required.');
      } else {
        $last_name = $_POST['last_name'];
      }

      if (empty($_POST['location'])) {
        $ERROR_MSG[] = _('Location is required.');
      } else {
        $location = $_POST['location'];
      }

      if (empty($_POST['timezone'])) {
        $ERROR_MSG[] = _('Timezone is required.');
      } else {
        $timezone = $_POST['timezone'];
      }

      if (empty($_POST['email'])) {
        $ERROR_MSG[] = _('Email is required.');
      }
      elseif (!validate_email($_POST['email'])) {
        $ERROR_MSG[] = _('Email address is invalid.');
      }
      elseif (blacklisted_domain($_POST['email'])) {
        $ERROR_MSG[] = _('Email address domain has been blacklisted. Please use a different email.');
      }
      else {
        $email = $_POST['email'];
        $result = db_select('training_user', array('uid' => 'integer'), 'email = '.db_quote($email, 'text'));
        if ($result->num_rows > 0) {
          $ERROR_MSG[] = _('Email in use by another member, please choose an alternative.');
        }
        $result->close();
      }

      if (empty($_POST['username'])) {
        $ERROR_MSG[] = _('Username is required.');
      }
      elseif (preg_match('/\s+/', $_POST['username'])) {
        $ERROR_MSG[] = _('Username cannot contain spaces.');
      }
      else {
        $username = $_POST['username'];
        $result = db_select('training_user', array('uid' => 'integer'), 'username = '.db_quote($username, 'text'));
        if ($result->num_rows > 0) {
          $ERROR_MSG[] = _('Username in use by another member, please choose an alternative.');
        }
        $result->close();
      }

      if (empty($_POST['unit'])) {
        $unit = 'mi';
      } else {
        $unit = $_POST['unit'];
      }

      $referrer = isset($_SESSION['referrer']) ? $_SESSION['referrer'] : '';

      if (count($ERROR_MSG) == 0) {
        $auth_code = mt_rand(100000000, 999999999);
        $result = db_select('training_user', array('uid' => 'integer'), 'auth_code = '.db_quote($auth_code, 'text'));

        /*
         * repeat until auth_code is unique
         */
        while ($result->num_rows > 0) {
          $result->close();
          $auth_code = mt_rand(100000000, 999999999);
          $result = db_select('training_user', array('uid' => 'integer'), 'auth_code = '.db_quote($auth_code, 'text'));
        }
        $result->close();

        $values = array(
          'first_name' => $first_name,
          'last_name' => $last_name,
          'username' => $username,
          'email' => $email,
          'password' => $md5_password,
          'auth_code' => $auth_code,
          'enabled' => 'F',
          'location' => $location,
          'referrer' => $referrer,
          'unit' => $unit,
          'timezone' => $timezone
        );
        $types = array(
          'text',
          'text',
          'text',
          'text',
          'text',
          'integer',
          'text',
          'text',
          'text',
          'text',
          'text'
        );
        db_insert('training_user', $values, $types);

        $uid = db_insert_id();

        /*
         * Look for invitation to update.
         */
        if (!empty($_SESSION['iid'])) {
          $iid = $_SESSION['iid'];
          db_update('training_invite', array('accepted' => 'Y', 'new_uid' => $uid), array('text', 'integer'), 'iid = '.db_quote($iid, 'integer'));
        }

        $subject = _('My Cycling Log Registration');
        $body = "
".$first_name." ".$last_name.",

"._('Thank you for registering for My Cycling Log.')."

"._('Please use following link to verify your email address:')."
https://".MCL_DOMAIN."/?a=$auth_code

"._('Username').": ".$username."
"._('Password').": ["._('hidden for security')."]
"._('Homepage').": https://".MCL_DOMAIN."/profile/".urlencode($username)."

"._('The My Cycling Log Team')."
https://".MCL_DOMAIN;
        $success = aws_send_mail($email, $subject, $body);
        if ($success) {
          header('Location: done.php');
        }
        else {
          $ERROR_MSG[] = _('There was an error sending the verification email.');
        }
      }
    }
  }
  else {
    $ERROR_MSG[] = _('Password fields are required.');
  }
}

$HEADER_TITLE = _('Registration');
include_once('common/header.php');
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<form name="register_form" action="/register.php" method="POST">
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('Free Registration') ?>
      <span class="hint"><?php echo _('Fields marked with an asterisk (*) are mandatory.') ?></span>
    </td>
  </tr>
  <?php if (is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
  <tr><td class="title"><?php echo _('First Name') ?>: *</td></tr>
  <tr>
    <td>
      <input name="first_name" type="text" size="25" class="formInput" value="<?php if (isset($_POST['first_name'])) { echo stripslashes($_POST['first_name']); } ?>"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Last Name') ?>: *</td></tr>
  <tr>
    <td>
      <input name="last_name" type="text" size="25" class="formInput" value="<?php if (isset($_POST['last_name'])) { echo stripslashes($_POST['last_name']); } ?>"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Email Address') ?>: *</td></tr>
  <tr>
    <td>
      <input name="email" type="text" size="25" class="formInput" value="<?php if (isset($_POST['email'])) { echo stripslashes($_POST['email']); } ?>"/>
    </td>
  </tr>
  <tr><td class="title">
    <?php echo _('Username') ?>: *
    <span class="hint"><?php echo _('Username is case-sensative and cannot contain spaces.') ?></span>
  </td></tr>
  <tr>
    <td>
      <input name="username" type="text" size="25" class="formInput" value="<?php if (isset($_POST['username'])) { echo stripslashes($_POST['username']); } ?>"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Location') ?>: *</td></tr>
  <tr>
    <td>
      <input name="location" type="text" size="25" class="formInput" value="<?php if (isset($_POST['location'])) { echo stripslashes($_POST['location']); } ?>"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Default Units') ?>: *</td></tr>
  <tr>
    <td>
      <?php echo _('Miles') ?>
      <input type="radio" name="unit" value="mi" checked/>
      &nbsp;&nbsp;
      <?php echo _('Kilometers') ?>
      <input type="radio" name="unit" value="km"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Timezone') ?>: *</td>
  </tr>
  <tr>
    <td>
      <select name="timezone">
        <?php
        $timezone_identifiers = DateTimeZone::listIdentifiers();
        for ($i = 0; $i < count($timezone_identifiers); $i++) {
          $t = $timezone_identifiers[$i];
          if (date_default_timezone_get() == $t) {
            echo "<option value='$t' selected='true'>$t</option>";
          }
          echo "<option value='$t'>$t</option>";
        }
        ?>
      </select>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Password') ?>: *</td></tr>
  <tr>
    <td>
      <input name="password1" type="password" size="25" class="formInput"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Confirm Password') ?>: *</td></tr>
  <tr>
    <td>
      <input name="password2" type="password" size="25" class="formInput"/>
    </td>
  </tr>
  <tr><td class="title"><?php echo _('Terms and Conditions') ?></td></tr>
  <tr>
    <td>
      <?php echo _('Registration is completely free and <b>My Cycling Log</b> will never distribute or sell your email address.') ?>
      <br/>
      <br/>
      <?php echo _('The information you enter on <b>My Cycling Log</b> will be publicly viewable, including your ride details, group affiliations, name, and location. Your email address and password, however, will <b>never</b> be made public.') ?>
    </td>
  </tr>
  <tr>
    <td>
      <input type="submit" value="<?php echo _('REGISTER') ?>" class="btn"/>
    </td>
  </tr>
</table>
</form>

</td></tr></table>

<?php include_once("common/footer.php"); ?>
