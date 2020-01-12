<?php
include_once("../common/common.inc.php");

if (bot_check() == 1) {
  header("HTTP/1.1 403 Forbidden");
  exit();
}

$nav_string = $_SERVER['REQUEST_URI'];
if (strpos($nav_string, '?') > 0) {
  $nav_string = substr($nav_string, 0, strpos($nav_string, '?'));
}
$parts = explode('/', $nav_string);

if (empty($parts[2])) {
  header("Location: ../index.php");
  exit();
}
$username = urldecode($parts[2]);
$rs = urldecode($parts[3]);
if (!is_numeric($rs)) {
  unset($rs);
}

$sid = session_check();
$user_unit = $_SESSION['user_unit'];

$types = array(
  'uid' => 'integer',
  'username' => 'text',
  'location' => 'text'
);
$result = db_select('training_user', $types, 'banned = 0 AND cancelled = 0 AND username = '.db_quote($username, 'text'));
if ($result->num_rows == 0) {
  header("Location: ../index.php");
  exit();
}
$user_row = $result->fetch_assoc();
$result->close();
$uid = $user_row['uid'];

$HEADER_TITLE = _('User Profile')." : ".export_clean($user_row['username']);
include_once("../common/header.php");
include_once("../common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td width="66%">

  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php
        echo export_clean($user_row['username']);
        echo "<br/><span class='cgray'>".export_clean($user_row['location'])."</span>";
        ?>
      </td>
      <td>
        <b><?php echo _('Actions') ?>:</b>
        <div style="padding: 5px 0 5px 0">
          <a href="/mail_new.php?to_uid=<?php echo $user_row['uid'] ?>"><?php echo _('Send Message') ?> &raquo;</a>
          <?php if ($_SESSION['uid'] === 1) { ?>
            | <a href="/private/moderate.php?form_type=ban_user&uid=<?php echo $user_row['uid'] ?>" style="color: #F00;"><?php echo _('Ban User') ?></a>
          <?php } ?>
        </div>
        <b><?php echo _('Graph Options') ?>:</b>
  <script type="text/javascript">
  function toggleTab(id) {
    hide('weekly_distance');
    hide('monthly_distance');
    hide('monthly_time');
    hide('monthly_avg_speed');
    hide('yearly_distance');
    show(id);
  }
  </script>
  <div style="padding-top: 5px">
    <div>
      <?php echo _('Weekly') ?>:
      <a href="javascript:void(0)" onclick="toggleTab('weekly_distance')"><?php echo _('Distance') ?></a>
    </div>
    <div>
      <?php echo _('Monthly') ?>:
      <a href="javascript:void(0)" onclick="toggleTab('monthly_distance')"><?php echo _('Distance') ?></a>
      |
      <a href="javascript:void(0)" onclick="toggleTab('monthly_time')"><?php echo _('Time') ?></a>
      |
      <a href="javascript:void(0)" onclick="toggleTab('monthly_avg_speed')"><?php echo _('Avg Speed') ?></a>
    </div>
    <div>
      <?php echo _('Yearly') ?>:
      <a href="javascript:void(0)" onclick="toggleTab('yearly_distance')"><?php echo _('Distance') ?></a>
    </div>
  </div>
      </td>
    </tr>
  </table>

  <div id="weekly_distance" style="display: none">
    <img src="/charts/weekly_chart.php?attr=distance&uid=<?php echo $uid ?>&width=468&height=300" border="0"/>
  </div>
  <div id="monthly_distance" style="display: block">
    <img src="/charts/monthly_chart.php?attr=distance&uid=<?php echo $uid ?>&width=468&height=300" border="0"/>
  </div>
  <div id="monthly_avg_speed" style="display: none">
    <img src="/charts/monthly_chart.php?attr=avg_speed&uid=<?php echo $uid ?>&width=468&height=300" border="0"/>
  </div>
  <div id="monthly_time" style="display: none">
    <img src="/charts/monthly_chart.php?attr=time&uid=<?php echo $uid ?>&width=468&height=300" border="0"/>
  </div>
  <div id="yearly_distance" style="display: none">
    <img src="/charts/yearly_chart.php?attr=distance&uid=<?php echo $uid ?>&width=468&height=300" border="0"/>
  </div>

  <?php include("../common/user_recent.php"); ?>

</td><td class="cell">

  <?php include("../common/user_highlights.php") ?>

  <table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Groups') ?>
        <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
      </td>
    </tr>
    <?php
    $query = "
      SELECT
        g.gid,
        g.name,
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed,
        COUNT(DISTINCT u.uid) AS members
      FROM
        training_group g INNER JOIN training_user_group ug ON g.gid = ug.gid INNER JOIN
        training_user u ON ug.uid = u.uid LEFT OUTER JOIN
        training_log l ON u.uid = l.uid AND l.is_ride = 'T' LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE
        YEAR(l.event_date) = YEAR(".db_now().")
        AND g.gid IN (
          SELECT gid
          FROM training_user_group
          WHERE uid = ".db_quote($uid, 'integer')."
        )
      GROUP BY g.gid, g.name";
    $result = db_query($query);
    while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td class="title">
          <a href="/group_view.php?gid=<?php echo $row['gid'] ?>"><?php echo export_clean($row['name']) ?></a>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo $row['members']." "._('members have ridden') ?>
          <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
          <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
        </td>
      </tr>
    <?php }
    $result->close();
    ?>
  </table>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
    <tr>
      <td class="head">
        <?php echo _('Bikes') ?>
        <span class="cgray tah10"><?php echo _('Statistics since') ?> <?php echo date("M j, Y", mktime(0, 0, 0, 1, 1, date("Y")))?></span>
      </td>
    </tr>
    <?php
    $query = "
      SELECT
        b.bid,
        b.make,
        b.model,
        b.year,
        b.enabled,
        b.is_default,
        SUM(l.distance) AS distance,
        SUM(l2.distance) / (SUM(TIME_TO_SEC(l2.time)) / 3600.0) AS avg_speed
      FROM
        training_bike b LEFT OUTER JOIN
        training_log l ON b.bid = l.bid AND YEAR(l.event_date) = YEAR(".db_now().") LEFT OUTER JOIN
        training_log l2 ON l.lid = l2.lid AND l2.time > 0 AND l2.distance > 0
      WHERE b.uid = ".db_quote($uid, 'integer')." AND b.enabled = 'T'
      GROUP BY b.bid, b.make, b.model, b.year, b.enabled, b.is_default";
    $result = db_query($query);
    while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td class="title">
          <a href="/bike_detail.php?bid=<?php echo $row['bid'] ?>"><?php echo export_clean($row['make'].' '.$row['model']) ?></a>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo unit_format($row['distance'], $user_unit)." ".$user_unit." "._('at') ?>
          <?php echo unit_format($row['avg_speed'], $user_unit)." ".$user_unit._('/h') ?>
        </td>
      </tr>
    <?php }
    $result->close();
    ?>
  </table>

</td></tr></table>

<?php include_once("../common/footer.php"); ?>
