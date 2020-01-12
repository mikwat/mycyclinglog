//Event that happens when a user selects a date on the calendar -- Updates select boxes and calendar date
function mclCalOnSelect(type, args, obj) {
  var dates = args[0];
  var date = dates[0];
  var year = date[0], month = date[1], day = date[2];

  var selMonth = document.getElementById("selMonth_" + this.id);
  var selDay = document.getElementById("selDay_" + this.id);
  var selYear = document.getElementById("selYear_" + this.id);
  if (selMonth && selDay && selYear) {
    selMonth.selectedIndex = month - 1;
    selDay.selectedIndex = day - 1;

    for (var y = 0; y < selYear.options.length; y++) {
      if (selYear.options[y].text == year) {
        selYear.selectedIndex = y;
        break;
      }
    }

    var daysInMonth = YAHOO.widget.DateMath.findMonthEnd(new Date(selYear.options[selYear.selectedIndex].value, selMonth.options[selMonth.selectedIndex].value, 1));
    selDay.options.length = daysInMonth.getDate();

    for (i = 0; i < daysInMonth.getDate(); i++) {
      selDay.options[i].value = selDay.options[i].text = i + 1;
    }
  }

  var selectedDate = this.getSelectedDates()[0];
  for (var i = 0; i < document.forms.length; i++) {
    if (document.forms[i].elements[this.id]) {
      document.forms[i].elements[this.id].value = mclCalDateFormat(selectedDate);
    }
  }
}

// date generating function
function mclCalDateFormat(dt) {
  return (
    (dt.getMonth() < 9 ? '0' : '') + (dt.getMonth() + 1) + "/" + (dt.getDate() < 10 ? '0' : '') + dt.getDate() + "/" + dt.getFullYear()
  );
}

//Builds up the calendar and select boxes -- runs once
function mclCalBuild() {
  var selMonth = document.getElementById("selMonth_" + this.id);
  var selDay = document.getElementById("selDay_" + this.id);
  var selYear = document.getElementById("selYear_" + this.id);
  if (selMonth && selDay && selYear) {
    var numMonths = this.cfg.getProperty("MONTHS_LONG").length;

    selMonth.options.length = numMonths;
    for (i = 0; i < numMonths; i++) {
       selMonth.options[i].value = i;
       selMonth.options[i].text = this.cfg.getProperty("MONTHS_LONG")[i];
    }

    var selectedDate = this.getSelectedDates()[0];
    var daysInMonth = YAHOO.widget.DateMath.findMonthEnd(
      (selectedDate)? selectedDate : new Date(selYear.options[selYear.selectedIndex].value, selMonth.options[selMonth.selectedIndex].value, 1));
    selDay.options.length = daysInMonth.getDate();
    for (i = 0; i < daysInMonth.getDate(); i++) {
      selDay.options[i].value = selDay.options[i].text = i + 1;
    }

    if (selectedDate) {
      selMonth.value = selectedDate.getMonth();
      selDay.value = selectedDate.getDate();
      selYear.value = selectedDate.getFullYear();
    }
  }
};

//Changes a calendar's select box -- changes days in the month and updates date on calendar.
function mclCalUpdate() {
  var selMonth = document.getElementById("selMonth_" + this.id);
  var selDay = document.getElementById("selDay_" + this.id);
  var selYear = document.getElementById("selYear_" + this.id);
  if (selMonth && selDay && selYear) {
    var daysInMonth = YAHOO.widget.DateMath.findMonthEnd(new Date(selYear.options[selYear.selectedIndex].value, selMonth.options[selMonth.selectedIndex].value, 1));
    var resetDay = false;
    if (selDay.selectedIndex+1 > daysInMonth.getDate()){
      resetDay = true;
    }
    selDay.options.length = daysInMonth.getDate();

    for (i = 0; i < daysInMonth.getDate(); i++) {
      selDay.options[i].value = selDay.options[i].text = i + 1;
    }

    if (resetDay) {
      selDay.options[daysInMonth.getDate()-1].selected = true;
    }

    var month = parseInt(selMonth.options[selMonth.selectedIndex].value) + 1;
    var day = parseInt(selDay.options[selDay.selectedIndex].value);
    var year = parseInt(selYear.options[selYear.selectedIndex].value);

    var date = month + "/" + day + "/" + year;
    this.select(date);
    this.cfg.setProperty("pagedate", month + "/" + year);

    this.render();
  }
}
