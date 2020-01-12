<?php
include_once("common/common.inc.php");

if (!session_check()) {
  header("Location: index.php?next_url=".urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$uid = $_SESSION['uid'];
$user_unit = $_SESSION['user_unit'];

$GLOBALS['ERROR_MSG'] = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_type'] == "account") {
  $unit = $_POST['unit'];
  if (empty($unit)) {
    $unit = 'mi';
  }

  $email = $_POST['email'];
  if (empty($email)) {
    $GLOBALS['ERROR_MSG'][] = _('Email is required.');
  }
  elseif (!validate_email($email)) {
    $GLOBALS['ERROR_MSG'][] = _('Email address is invalid.');
  }
  else {
    $result = db_select('training_user', array('uid' => 'integer'), 'email = '.db_quote($email, 'text').' AND uid <> '.db_quote($uid, 'integer'));
    if ($result->num_rows > 0) {
      $GLOBALS['ERROR_MSG'][] = _('Email in use by another member, please choose an alternative.');
    }
    $result->close();
  }

  $username = $_POST['username'];
  if (empty($username)) {
    $GLOBALS['ERROR_MSG'][] = _('Username is required.');
  }
  elseif (preg_match('/\s+/', $username)) {
    $GLOBALS['ERROR_MSG'][] = _('Username cannot contain spaces.');
  }
  else {
    $result = db_select('training_user', array('uid' => 'integer'), 'username = '.db_quote($username, 'text').' AND uid <> '.db_quote($uid, 'integer'));
    if ($result->num_rows > 0) {
      $GLOBALS['ERROR_MSG'][] = _('Username in use by another member, please choose an alternative.');
    }
    $result->close();
  }

  $first_name = $_POST['first_name'];
  if (empty($first_name)) {
    $GLOBALS['ERROR_MSG'][] = _('First name is required.');
  }

  $last_name = $_POST['last_name'];
  if (empty($last_name)) {
    $GLOBALS['ERROR_MSG'][] = _('Last name is required.');
  }

  $location = $_POST['location'];
  if (empty($location)) {
    $GLOBALS['ERROR_MSG'][] = _('Location is required.');
  }

  $timezone = $_POST['timezone'];
  if (empty($timezone)) {
    $GLOBALS['ERROR_MSG'][] = _('Timezone is required.');
  }

  $week_start = $_POST['week_start'];
  if ($week_start == 'Sun') {
    $week_start = 0;
  }
  else {
    $week_start = 1;
  }

  $hide_name = $_POST['hide_name'];
  if ($hide_name != 'T') {
    $hide_name = 'F';
  }

  $locale = $_POST['locale'];

  $mpd = $_POST['mpd'];
  $mpg = $_POST['mpg'];

  if (count($GLOBALS['ERROR_MSG']) == 0) {
    $values = array(
      'username' => $username,
      'email' => $email,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'location' => $location,
      'unit' => $unit,
      'timezone' => $timezone,
      'mpd' => $mpd,
      'mpg' => $mpg,
      'locale' => $locale,
      'week_start' => $week_start,
      'hide_name' => $hide_name
    );
    $types = array(
      'text',
      'text',
      'text',
      'text',
      'text',
      'text',
      'text',
      'float',
      'float',
      'text',
      'integer',
      'text'
    );
    db_update('training_user', $values, $types, 'uid = '.db_quote($uid, 'integer'));
    $GLOBALS['ERROR_MSG'][] = _('Changes saved.');

    $_SESSION['user_unit'] = trim($unit, "'");
    $_SESSION['timezone'] = $timezone;
    $_SESSION['week_start'] = $week_start;
    switch ($locale) {
      case 'es':
        $locale = 'es_ES.iso88591';
        break;
      case 'it':
        $locale = 'it_IT.iso88591';
        break;
      case 'pt':
        $locale = 'pt_PT.iso88591';
        break;
      default:
        $locale = null;
    }

    if (!empty($locale)) {
      $_SESSION['locale'] = $locale;
      setlocale(LC_ALL, $locale);
    }
    else {
      unset($_SESSION['locale']);
    }
  }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_type'] == "password") {
  $cur_password = $_POST['cur_password'];
  $pass1 = $_POST['new_password1'];
  $pass2 = $_POST['new_password2'];
  if (!empty($pass1) && $pass1 == $pass2) {
    $query = "
      SELECT password
      FROM training_user
      WHERE uid = ".db_quote($uid, 'integer');
    $result = db_query($query);
    $db_pass = $result->fetch_assoc()['password'];
    $result->close();
    if (md5($cur_password) == $db_pass) {
      $md5_password = md5($pass1);

      db_update('training_user', array('password' => $md5_password), array('text'), 'uid = '.db_quote($uid, 'integer'));
      $GLOBALS['ERROR_MSG'][] = _('Password successfully updated.');
    }
    else {
      $GLOBALS['ERROR_MSG'][] = _('Invalid current password.');
    }
  }
  else {
    if (empty($pass1)) {
      $GLOBALS['ERROR_MSG'][] = _('Password cannot be blank.');
    }
    else {
      $GLOBALS['ERROR_MSG'][] = _('Passwords do not match.');
    }
  }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_type'] == "cancel") {
  $password = $_POST['password'];
  if (!empty($password)) {
    $query = "
      SELECT password
      FROM training_user
      WHERE uid = ".db_quote($uid, 'integer');
    $result = db_query($query);
    $db_pass = $result->fetch_assoc()['password'];
    $result->close();
    if (md5($password) == $db_pass) {
      db_update('training_user', array('cancelled' => 1), array('text'), 'uid = '.db_quote($uid, 'integer'));

      header("Location: logout.php");
      exit();
    }
    else {
      $GLOBALS['ERROR_MSG'][] = _('Invalid current password.');
    }
  }
  else {
    $GLOBALS['ERROR_MSG'][] = _('Password cannot be blank.');
  }
}

$HEADER_TITLE = _('Account');
include_once("common/header.php");
include_once("common/tabs.php");

$types = array(
  'username' => 'text',
  'first_name' => 'text',
  'last_name' => 'text',
  'email' => 'text',
  'location' => 'text',
  'unit' => 'text',
  'timezone' => 'text',
  'ext_cookie' => 'text',
  'mpd' => 'float',
  'mpg' => 'float',
  'locale' => 'text',
  'week_start' => 'integer',
  'hide_name' => 'text'
);
$result = db_select('training_user', $types, 'uid = '.db_quote($uid, 'integer'));
$u_row = $result->fetch_assoc();
$result->close();
?>
<script type="text/javascript">
function doCloseAccount() {
  var d = document.getElementById('close_account');
  overlib(d.innerHTML, STICKY, WIDTH, -1);
}
</script>

<table align="center" border="0" cellspacing="0" cellpadding="0" class="main">
  <tr>
    <td width="50%">

<form name="account_form" action="/account.php" method="POST">
<input type="hidden" name="form_type" value="account"/>
<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <?php if ($_POST['form_type'] == "account" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
  <tr>
    <td class="head"><?php echo _('Modify Your Information') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Email') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="email" size="25" class="formInput" value="<?php echo stripslashes($u_row['email']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Username') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="username" size="25" class="formInput" value="<?php echo stripslashes($u_row['username']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('First Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="first_name" size="25" class="formInput" value="<?php echo stripslashes($u_row['first_name']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Last Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="last_name" size="25" class="formInput" value="<?php echo stripslashes($u_row['last_name']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Location') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="location" size="25" class="formInput" value="<?php echo stripslashes($u_row['location']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Miles Per Gallon') ?>:
    </td>
  </tr>
  <tr>
    <td>
      <input type="text" name="mpg" size="10" class="formInput" value="<?php echo stripslashes($u_row['mpg']) ?>"/>
      <span class="cgray tah10"><?php echo _('Use Google to convert between <a href="http://www.google.com/search?q=10+kpl+in+mpg" class="external" target="_blank">KPL and MPG</a>') ?></span>
    </td>
  </tr>
  <tr>
    <td class="title">
      <?php echo _('Miles Per Dollar (or equivalent)') ?>:
    </td>
  </tr>
  <tr>
    <td>
      <input type="text" name="mpd" size="10" class="formInput" value="<?php echo stripslashes($u_row['mpd']) ?>"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Default Units') ?>: *</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Miles') ?>
      <input type="radio" name="unit" size="25" value="mi" <?php if ($u_row['unit'] == 'mi') echo 'checked' ?>/>
      &nbsp;&nbsp;
      <?php echo _('Kilometers') ?>
      <input type="radio" name="unit" size="25" value="km" <?php if ($u_row['unit'] == 'km') echo 'checked' ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Calendar Day of Week Start') ?>: *</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Sunday') ?>
      <input type="radio" name="week_start" size="25" value="Sun" <?php if ($u_row['week_start'] === '0') { echo 'checked'; } ?>/>
      &nbsp;&nbsp;
      <?php echo _('Monday') ?>
      <input type="radio" name="week_start" size="25" value="Mon" <?php if ($u_row['week_start'] === '1') { echo 'checked'; } ?>/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Privacy - Full Name') ?>: *</td>
  </tr>
  <tr>
    <td>
      <?php echo _('Show') ?>
      <input type="radio" name="hide_name" size="25" value="F" <?php if ($u_row['hide_name'] == 'F') echo 'checked' ?>/>
      &nbsp;&nbsp;
      <?php echo _('Hide') ?>
      <input type="radio" name="hide_name" size="25" value="T" <?php if ($u_row['hide_name'] == 'T') echo 'checked' ?>/>
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
          if ($u_row['timezone'] == $t) {
            echo "<option value='$t' selected='true'>$t</option>";
          }
          echo "<option value='$t'>$t</option>";
        }
        ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Language') ?>: *</td>
  </tr>
  <tr>
    <td>
      <select name="locale">
        <?php
        foreach ($LOCALE_LIST as $locale => $label) {
          if ($u_row['locale'] == $locale) {
            echo "<option value='$locale' selected='true'>$label</option>";
          }
    else {
            echo "<option value='$locale'>$label</option>";
    }
        }
        ?>
      </select>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo _('UPDATE') ?>" class="btn"/>
    </td>
  </tr>
</form>
</table>

    </td>
    <td class="cell">

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('User Profile') ?></td>
  </tr>
  <tr>
    <td><?php echo _('Your Profile') ?>: <a href="/profile/<?php echo urlencode($_SESSION['username']) ?>"><?php echo _('View') ?></a></td>
  </tr>
</table>

<form name="password_form" action="/account.php" method="POST">
<input type="hidden" name="form_type" value="password"/>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head"><?php echo _('Modify Your Password') ?></td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Current Password') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="password" name="cur_password" size="25" class="formInput"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('New Password') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="password" name="new_password1" size="25" class="formInput"/>
    </td>
  </tr>
  <tr>
    <td class="title"><?php echo _('Confirm New Password') ?>: *</td>
  </tr>
  <tr>
    <td>
      <input type="password" name="new_password2" size="25" class="formInput"/>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="submit" value="<?php echo _('CHANGE') ?>" class="btn"/>
    </td>
  </tr>
  <?php if ($_POST['form_type'] == "password" && is_error()) { ?>
    <tr><td colspan="2"><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

<div id="close_account" style="display: none">
  <form name="password_form" action="/account.php" method="POST">
    <input type="hidden" name="form_type" value="cancel"/>
    <table border="0" cellspacing="0" cellpadding="2" class="noborbox">
      <tr>
        <td colspan="2" class="title"><?php echo _('Confirm Password') ?>: *</td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="password" name="password" size="25" class="formInput"/>
        </td>
      </tr>
      <tr>
        <td><input type="submit" value="<?php echo _('CLOSE ACCOUNT') ?>" class="btn"/></td>
        <td><input type="button" value="<?php echo _('CANCEL') ?>" onclick="nd();nd();" class="btn"/></td>
      </tr>
    </table>
  </form>
</div>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td>
      <a href="javascript:void(0);" onclick="doCloseAccount()">Close Account &raquo;</a>
    </td>
  </tr>
  <?php if ($_POST['form_type'] == "cancel" && is_error()) { ?>
    <tr><td><?php print_error() ?></td></tr>
  <?php } ?>
</table>
</form>

    </td>
  </tr>
</table>

<?php include_once("common/footer.php"); ?>
