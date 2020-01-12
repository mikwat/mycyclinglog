<?php
function get_bg($mag) {
  if ($mag >= 100) {
    $class = "bg10";
  }
  elseif ($mag >= 90) {
    $class = "bg9";
  }
  elseif ($mag >= 80) {
    $class = "bg8";
  }
  elseif ($mag >= 70) {
    $class = "bg7";
  }
  elseif ($mag >= 60) {
    $class = "bg6";
  }
  elseif ($mag >= 50) {
    $class = "bg5";
  }
  elseif ($mag >= 40) {
    $class = "bg4";
  }
  elseif ($mag >= 30) {
    $class = "bg3";
  }
  elseif ($mag >= 20) {
    $class = "bg2";
  }
  elseif ($mag >= 10) {
    $class = "bg1";
  }
  else {
    $class = "bg0";
  }

  return $class;
}

// PHP Calendar Class Version 1.4 (5th March 2001)
//
// Copyright David Wilkinson 2000 - 2001. All Rights reserved.
//
// This software may be used, modified and distributed freely
// providing this copyright notice remains intact at the head
// of the file.
//
// This software is freeware. The author accepts no liability for
// any loss or damages whatsoever incurred directly or indirectly
// from the use of this script. The author of this software makes
// no claims as to its fitness for any purpose whatsoever. If you
// wish to use this software you should first satisfy yourself that
// it meets your requirements.
//
// URL:   http://www.cascade.org.uk/software/php/calendar/
// Email: davidw@cascade.org.uk

class Calendar
{
    function Calendar($page) {
      $this->page = $page;
    }

    function getDistanceMap() {
      return $this->distance_map;
    }

    function setDistanceMap($map) {
      $this->distance_map = $map;
    }

    function getRideMap() {
      return $this->ride_map;
    }

    function setRideMap($map) {
      $this->ride_map = $map;
    }

    /*
        Get the array of strings used to label the days of the week. This array contains seven
        elements, one for each day of the week. The first entry in this array represents Sunday.
    */
    function getDayNames() {
        return $this->dayNames;
    }

    /*
        Set the array of strings used to label the days of the week. This array must contain seven
        elements, one for each day of the week. The first entry in this array represents Sunday.
    */
    function setDayNames($names) {
        $this->dayNames = $names;
    }

    /*
        Get the array of strings used to label the months of the year. This array contains twelve
        elements, one for each month of the year. The first entry in this array represents January.
    */
    function getMonthNames() {
        return $this->monthNames;
    }

    /*
        Set the array of strings used to label the months of the year. This array must contain twelve
        elements, one for each month of the year. The first entry in this array represents January.
    */
    function setMonthNames($names) {
        $this->monthNames = $names;
    }

    /*
        Gets the start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    function getStartDay() {
        return $this->startDay;
    }

    /*
        Sets the start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    function setStartDay($day) {
        $this->startDay = $day;
    }

    /*
        Gets the start month of the year. This is the month that appears first in the year
        view. January = 1.
    */
    function getStartMonth() {
        return $this->startMonth;
    }

    /*
        Sets the start month of the year. This is the month that appears first in the year
        view. January = 1.
    */
    function setStartMonth($month) {
        $this->startMonth = $month;
    }

    /*
        Return the URL to link to in order to display a calendar for a given month/year.
        You must override this method if you want to activate the "forward" and "back"
        feature of the calendar.

        Note: If you return an empty string from this function, no navigation link will
        be displayed. This is the default behaviour.

        If the calendar is being displayed in "year" view, $month will be set to zero.
    */
    function getCalendarLink($month, $year) {
        $link = $this->page."?m=$month&y=$year";
        if ($_GET[uid]) {
          $link .= "&uid=".$_GET[uid];
        }

        return $link;
    }

    /*
        Return the HTML for the current month
    */
    function getCurrentMonthView() {
        $d = getdate(time());
        return $this->getMonthView($d["mon"], $d["year"]);
    }

    /*
        Return the HTML for the current year
    */
    function getCurrentYearView() {
        $d = getdate(time());
        return $this->getYearView($d["year"]);
    }

    /*
        Return the HTML for a specified month
    */
    function getMonthView($month, $year, $link_type = 0) {
        return $this->getMonthHTML($month, $year, 1, $link_type);
    }

    /*
        Return the HTML for a specified year
    */
    function getYearView($year) {
        return $this->getYearHTML($year);
    }

    /********************************************************************************
        The rest are private methods. No user-servicable parts inside.
        You shouldn't need to call any of these functions directly.
    *********************************************************************************/

    /*
        Calculate the number of days in a month, taking into account leap years.
    */
    function getDaysInMonth($month, $year) {
        if ($month < 1 || $month > 12) {
            return 0;
        }

        $d = $this->daysInMonth[$month - 1];

        if ($month == 2) {
            // Check for leap year
            // Forget the 4000 rule, I doubt I'll be around then...
            if ($year%4 == 0) {
                if ($year%100 == 0) {
                    if ($year%400 == 0) {
                        $d = 29;
                    }
                }
                else {
                    $d = 29;
                }
            }
        }

        return $d;
    }

    /*
        Generate the HTML for a given month
    */
    function getMonthHTML($m, $y, $showYear = 1, $link_type = 0) {
      $s = "";
      $month_total = 0;

      $a = $this->adjustDate($m, $y);
      $month = $a[0];
      $year = $a[1];

    	$daysInMonth = $this->getDaysInMonth($month, $year);
    	$date = getdate(mktime(12, 0, 0, $month, 1, $year));

    	$first = $date["wday"];
    	$monthName = $this->monthNames[$month - 1];

    	$prev = $this->adjustDate($month - 1, $year);
    	$next = $this->adjustDate($month + 1, $year);

    	if ($showYear == 1) {
    	    //$prevMonth = $this->getCalendarLink($prev[0], $prev[1]);
    	    $prevMonth = $this->getCalendarLink($month, $year);
    	    $nextMonth = $this->getCalendarLink($next[0], $next[1]);
    	}
    	else {
    	    $prevMonth = "";
    	    $nextMonth = "";
    	}

    	$header = $monthName . (($showYear > 0) ? " " . $year : "");

    	$s .= "<table width=\"150\" cellspacing=\"0\" cellpadding=\"4\" class=\"calbox\">\n";
    	$s .= "<tr>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . (($prevMonth == "" || $link_type != -1) ? "&nbsp;" : "<a href=\"$prevMonth\">&lt;&lt;</a>")  . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\" colspan=\"5\">$header</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . (($nextMonth == "" || $link_type != 1) ? "&nbsp;" : "<a href=\"$nextMonth\">&gt;&gt;</a>")  . "</td>\n";
    	$s .= "</tr>\n";

    	$s .= "<tr>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+1)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+2)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+3)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+4)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+5)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"title\">" . $this->dayNames[($this->startDay+6)%7] . "</td>\n";
    	$s .= "</tr>\n";

    	// We need to work out what date to start at so that the first appears in the correct column
    	$d = $this->startDay + 1 - $first;
    	while ($d > 1) {
    	    $d -= 7;
    	}

      // Make sure we know when today is, so that we can use a different CSS style
      $today = getdate(time());
    	while ($d <= $daysInMonth) {
    	    $s .= "<tr>\n";

    	    for ($i = 0; $i < 7; $i++) {
    	      $class = "tah10 cblack ";
    	      $key = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT);
            $mag = $this->distance_map[$key];
    	      if ($mag > 0) {
              $month_total += $mag;
              $class .= get_bg($mag);
    	      }
            elseif ($this->distance_map[$key] == -1) {
              $class .= "bg01";
            }
    	      else {
      	      $class .= ($year == $today["year"] && $month == $today["mon"] && $d == $today["mday"]) ? "bg" : "";
        	  }

  	        $s .= "<td class=\"$class\" align=\"center\" valign=\"top\">";
  	        if ($d > 0 && $d <= $daysInMonth) {
                if (isset($this->ride_map[$key])) {
                  $j_txt = $this->ride_map[$key];
                }
                else {
                  $j_txt = $mag;
                }

  	            $s .= (($mag == 0) ? $d : "<a href=\"javascript:void(0)\" onmouseover=\"return overlib('".$j_txt."', WIDTH, -1);\" onmouseout=\"return nd();\">$d</a>");
  	        }
  	        else {
  	            $s .= "&nbsp;";
  	        }
    	      $s .= "</td>\n";
      	    $d++;
    	    }

    	    $s .= "</tr>\n";
    	}

      /*
       * Add total line
       */
      $s .= "<tr>\n";
      $s .= "<td align=\"left\" colspan=\"7\" class=\"tah10 cblack ".get_bg($month_total / 10)."\"><b>Total: ".number_format($month_total, 2)." ".$user_unit."</b></td>\n";
      $s .= "</tr>\n";

    	$s .= "</table>\n";

    	return $s;
    }

    /*
        Generate the HTML for a given year
    */
    function getYearHTML($year) {
      $s = "";
    	$prev = $this->getCalendarLink(0, $year - 1);
    	$next = $this->getCalendarLink(0, $year + 1);

        $s .= "<table class=\"calendar\" border=\"0\">\n";
        $s .= "<tr>";
    	$s .= "<td align=\"center\" valign=\"top\" align=\"left\" class=\"tah10 cwhite\">" . (($prev == "") ? "&nbsp;" : "<a href=\"$prev\" class=\"w\">&lt;&lt;</a>")  . "</td>\n";
        $s .= "<td class=\"calendarHeader\" valign=\"top\" align=\"center\">" . (($this->startMonth > 1) ? $year . " - " . ($year + 1) : $year) ."</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" align=\"right\" class=\"tah10 cwhite\">" . (($next == "") ? "&nbsp;" : "<a href=\"$next\" class=\"w\">&gt;&gt;</a>")  . "</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(0 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(1 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(2 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(3 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(4 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(5 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(6 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(7 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(8 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(9 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(10 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(11 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "</table>\n";

        return $s;
    }

    /*
        Adjust dates to allow months > 12 and < 0. Just adjust the years appropriately.
        e.g. Month 14 of the year 2001 is actually month 2 of year 2002.
    */
    function adjustDate($month, $year) {
        $a = array();
        $a[0] = $month;
        $a[1] = $year;

        while ($a[0] > 12)
        {
            $a[0] -= 12;
            $a[1]++;
        }

        while ($a[0] <= 0)
        {
            $a[0] += 12;
            $a[1]--;
        }

        return $a;
    }

    /*
        The start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    var $startDay = 0;

    /*
        The start month of the year. This is the month that appears in the first slot
        of the calendar in the year view. January = 1.
    */
    var $startMonth = 1;

    /*
        The labels to display for the days of the week. The first entry in this array
        represents Sunday.
    */
    var $dayNames = array("S", "M", "T", "W", "T", "F", "S");

    /*
        The labels to display for the months of the year. The first entry in this array
        represents January.
    */
    var $monthNames = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

    /*
        The number of days in each month. You're unlikely to want to change this...
        The first entry in this array represents January.
    */
    var $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    var $distance_map = array();
    var $ride_map = array();
    var $page = "";
}
?>
