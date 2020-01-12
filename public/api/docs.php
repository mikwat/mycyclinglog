<?php
include_once("../common/common.inc.php");

$sid = session_check();

$HEADER_TITLE = _('API Documentation');
include_once("../common/header.php");
include_once("../common/tabs.php");
?>
<table align="center" border="0" cellspacing="0" cellpadding="0" class="main"><tr><td>

<table align="center" border="0" cellspacing="0" cellpadding="4" class="inbox">
  <tr>
    <td class="head">
      <?php echo _('API Documentation') ?>
    </td>
  </tr>
  <tr>
    <td>
<h3>Authentication</h3>
Many My Cycling API methods require authentication.  All responses are relative to the context of the authenticating user.  For the time being, <a href="http://en.wikipedia.org/wiki/Basic_authentication_scheme">HTTP Basic Authentication</a> is the only supported authentication scheme.

<h3>New Ride</h3>
Create new ride log entry.

<ul>
  <li><b>URL:</b> https://<?php echo MCL_DOMAIN ?>/api/restserver.php?method=ride.new</li>
  <li><b>Method(s):</b> POST</li>
  <li><b>Parameters:</b>
    <ul>
      <li><tt>event_date.</tt>  Required. Format must be MM/DD/YYYY, i.e. 12/31/2008</li>
      <li><tt>is_ride.</tt>  Required. Possible values: T or F.  Entry counts towards cycling total if and only if is_ride = T.</li>
      <li><tt>h.</tt>  Optional.  Hours portion of ride time.  Default: 0.</li>
      <li><tt>m.</tt>  Optional.  Minutes portion of ride time.  Default: 0.</li>
      <li><tt>s.</tt>  Optional.  Seconds portion of ride time.  Default: 0.</li>
      <li><tt>distance.</tt>  Optional.  Distance in units specified by <tt>user_unit</tt> parameter.  Default: 0.</li>
      <li><tt>user_unit.</tt>  Optional.  Units used in <tt>distance</tt>, either km or mi.  Default: mi.</li>
      <li><tt>notes.</tt>  Optional.</li>
      <li><tt>heart_rate.</tt>  Optional.</li>
      <li><tt>max_speed.</tt>  Optional.</li>
      <li><tt>avg_cadence.</tt>  Optional.</li>
      <li><tt>weight.</tt>  Optional.</li>
      <li><tt>calories.</tt>  Optional.</li>
      <li><tt>elevation.</tt>  Optional.</li>
      <li><tt>tags.</tt>  Optional.  Tags help to classify each entry.  Separate each tag with a plus (+), i.e. road+training</li>
      <li><tt>bid.</tt>  Optional.  Bike ID as returned by <b>New Bike</b> API.</li>
    </ul>
  </li>
  <li><b>Returns:</b> On success, unique ID of new ride record.</li>
</ul>

<h3>List Rides</h3>
List ride log entries.

<ul>
  <li><b>URL:</b> https://<?php echo MCL_DOMAIN ?>/api/restserver.php?method=ride.list</li>
  <li><b>Method(s):</b> GET</li>
  <li><b>Parameters:</b>
    <ul>
      <li><tt>offset.</tt>  Optional. Offset into entry list. Default: 0.</li>
      <li><tt>limit.</tt>  Optional. Maximum number of entries to return. Default: 10.</li>
    </ul>
  </li>
  <li><b>Returns:</b> On success, list of ride log entries.</li>
</ul>

<h3>New Bike</h3>
Create new bikes.

<ul>
  <li><b>URL:</b> https://<?php echo MCL_DOMAIN ?>/api/restserver.php?method=bike.new</li>
  <li><b>Method(s):</b> POST</li>
  <li><b>Parameters:</b>
    <ul>
      <li><tt>make.</tt>  Required, i.e. Specialized</li>
      <li><tt>model.</tt>  Required, i.e. S-Works</li>
      <li><tt>year.</tt>  Optional, i.e. 2010</li>
      <li><tt>enabled.</tt>  Optional. Possible values: T or F. Default: F.</li>
    </ul>
  </li>
  <li><b>Returns:</b> On success, unique ID of new bike.</li>
</ul>

<h3>List Bikes</h3>
List bike

<ul>
  <li><b>URL:</b> https://<?php echo MCL_DOMAIN ?>/api/restserver.php?method=bike.list</li>
  <li><b>Method(s):</b> GET</li>
  <li><b>Parameters:</b>
    <ul>
      <li><tt>offset.</tt>  Optional. Offset into entry list. Default: 0.</li>
      <li><tt>limit.</tt>  Optional. Maximum number of entries to return. Default: 10.</li>
    </ul>
  </li>
  <li><b>Returns:</b> On success, list of bikes.</li>
</ul>

    </td>
  </tr>
</table>

    </td>
  </tr>
</table>

<?php include_once("../common/footer.php"); ?>
