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
 * \description     Booking preview before submitting
 */

/**Retourne la valeur du select selectId*/
function getSelectText(selectId)
{
	/**On récupère l'élement html <select>*/
	var selectElmt = document.getElementById(selectId);
	/**
	selectElmt.options correspond au tableau des balises <option> du select
	selectElmt.selectedIndex correspond à l'index du tableau options qui est actuellement sélectionné
	*/
	//return selectElmt.options[selectElmt.selectedIndex].value;
	return selectElmt.options[selectElmt.selectedIndex].text;

	// ou :
	// var selectValue = document.getElementById('identifiantDeMonSelect').options[document.getElementById('identifiantDeMonSelect').selectedIndex].value;
}

function isArray(obj){
    return obj instanceof Array;
}

function bookingpreviewupdate() {
	var servername = getSelectText("server_name");
	var modname = getSelectText("mod_name");

	var clanname = '';
	if (document.getElementById("clan_name").type == "select-one") {
		var clanname = getSelectText("clan_name");
	} else {
		var clanname = document.getElementById("clan_name").value;
	}
	var g_password = document.getElementById("g_password").value;
	var date = document.getElementById("start_date").value;
	var timezone = getSelectText("timezone");
	var time = getSelectText("start_slot");
	var duration = getSelectText("duration");
	// Get the weekday name of the date
	var d=new Date(date);
	var weekday=new Array("Sunday","Monday","Tuesday","Wednesday","Thursday",
                "Friday","Saturday");
	var day = weekday[d.getDay()];

	document.getElementById("bookingpreview").innerHTML = "The server <strong>"+servername+"</strong> with mod "+modname+" will be booked with password <em>"+g_password+"</em> for the clan <strong>"+clanname+"</strong> on "+day+" "+date+" at "+time+" ("+timezone+") for "+duration+".<br />Press Book It! button to book this date (if slot is available).";
}