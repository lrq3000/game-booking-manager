/*
 * Copyright (C) 2011-2013 Larroque Stephen
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/**
 * \description     Library of the functions for client's interactions (eg: autodetect timezone)
 */

// Return the value of the html select with id="selectId"
function getSelectValue(selectId)
{
	/**Fetch the html <select> from id=selectID*/
	var selectElmt = document.getElementById(selectId);
	/**
	selectElmt.options corresponds to the list of tags <option> of the <select>
	selectElmt.selectedIndex corresponds to the index of the list of options that is currently selected
	*/
	return selectElmt.options[selectElmt.selectedIndex].value;
	//return selectElmt.options[selectElmt.selectedIndex].text;

	// or :
	// var selectValue = document.getElementById('identifiantDeMonSelect').options[document.getElementById('identifiantDeMonSelect').selectedIndex].value;
}

// Set selected the option with the specified index in a HTML select
function setSelectIndex(selectId, newindex)
{
	/**Fetch the html <select> from id=selectID*/
	var selectElmt = document.getElementById(selectId);
	/**
	selectElmt.options corresponds to the list of tags <option> of the <select>
	selectElmt.selectedIndex corresponds to the index of the list of options that is currently selected
	*/
	selectElmt.selectedIndex = newindex;
	//return selectElmt.options[selectElmt.selectedIndex].text;

	// or :
	// var selectValue = document.getElementById('identifiantDeMonSelect').options[document.getElementById('identifiantDeMonSelect').selectedIndex].value;
}

// Fetch GET parameters from URL
function param_url() {
    // Remove the ?
    param = window.location.search.slice(1,window.location.search.length);

    // Separating the parameters...
    // first[0] is in the following format: param=valeur

    params = param.split("&");

	/* example of use
	var nom=new Array();
    var valeur=new Array();

	for(i=0;i<params.length;i++){
        oneparam = params[i].split("=");
        name[i] = oneparam[0];
        value[i] = oneparam[1];
    }
	*/

	return params;
}

// Refresh the calendar with the new settings (game server, timezone, start_date, etc)
function refresh_calendar(){
	var newloc = "?";

	// Get server name
	var servername = getSelectValue("server_name");
	if (servername != ""){
	  newloc += newloc + "&server_name="+servername;
	}

	// Get timezone
	var timezone = getSelectValue("timezone");
	if (timezone != ""){
	  newloc += "&timezone="+timezone;
	}

	// Get currently selected start_date
	var params;
	var param;
	params = param_url();

	for(i=0;i<params.length;i++){
        param = params[i].split("=");
        if (param[0] == "start_date") {
			newloc += "&"+params[i];
		}
    }

	// Refresh the page with these parameters (passed to the php script as GET params)
	location.href = newloc;
}

// Pad leading 0 to a number
function pad(number, length) {

    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }

    return str;

}

// Get client-side timezone offset via JS
function get_time_zone_offset( ) {
	var current_date = new Date( );
	var utc_hour_offset = Math.round(-current_date.getTimezoneOffset( ) / 60);
	var utc_minutes_offset = pad(Math.abs(current_date.getTimezoneOffset( ) % 60), 2);
	return utc_hour_offset+':'+utc_minutes_offset;
}

// Select the right index of the option that has the specified value (not text)
// Note: selObj is a JS object, so you should use something like document.getElementById(selectId)
function selectOptionByValue(selObj, val){
    var A= selObj.options, L= A.length;
    while(L){
        if (A[--L].value== val){
            selObj.selectedIndex= L;
            L= 0;
        }
    }
}

// Detects if the page already called itself (if the form has already been submitted at least once, so that we have some GET and POST parameters)
function isPostBack(){
	return document.referrer.indexOf(document.location.href) > -1;
}

// Autodetect the client's timezone and automatically set it as default (if the user has not already choosen a timezone by himself)
function autodetect_timezone(){
	var timezonenotselected = true; // false if a $_GET["timezone"] is set

	// Detect $_GET["timezone"] parameter existence
	var params;
	var param;
	params = param_url();

	for(i=0;i<params.length;i++){
        param = params[i].split("=");
        if (param[0] == "timezone") {
			timezonenotselected = false;
		}
    }

	// If user has already chosen a timezone, we skip the autoset:
	// if either $_GET["timezone"] exists or any $_POST parameter (we can't know which one with JS), we skip autoset
	//if (timezonenotselected && !isPostBack()) { // BUG: after confirmation page, if we click on the link to go back, since the last page was the same, it will consider that isPostBack is true! But there's no $_POST ! Solution is to use PHP to call this JS function only if $_POST["timezone"] is undefined
    if (timezonenotselected) {
		// Detect the client's timezone
		var default_timezone = get_time_zone_offset();
		// Set the timezone select to the client's timezone
		selectOptionByValue(document.getElementById("timezone"), default_timezone);
	}
}