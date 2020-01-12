<?php
include_once("../common/common.inc.php");
if ($_GET['locale']) {
  $locale = null;
  switch ($_GET['locale']) {
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
      unset($locale);
  }

  if (!empty($locale)) {
    setlocale(LC_ALL, $locale);
  }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <link href="http://<?php echo $_SERVER['HTTP_HOST'] ?>/common/mycyclinglog.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<?php
$EMBEDED = true;
if ($_GET['uid'] > 0) {
  include("../common/user_highlights.php");
}
elseif ($_GET['gid'] > 0) {
  include("../common/group_highlights.php");
}
?>
</body>
</html>
