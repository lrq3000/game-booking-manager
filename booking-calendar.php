<?php
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
 * \description     Booking calendar to show events to the public
 */

require_once('config.php');
require_once('lib.php');


//**** Templates for each events ****
// Public (if show_public=yes)
$public_event_template = '
<div>
<h3>$time - $endtime $title</h3>
<b>$confirmed</b><br />
Booked by clan: <strong>$clan</strong><br />
Type: $type<br />
Mod: $mod_fullname<br />
GTV: $gtv<br />
Website: $website<br />
Description: <a class="show_hide" href="#" rel="#slidingDiv$counter">Show</a><br />
<div id="slidingDiv$counter" class="toggleDiv" style="display: none;">
<noscript> <!-- Little trick to ensure that the description gets shown for non-javascript browsers but with CSS enabled -->
</div>
<div>
</noscript>
$description
</div>
</div>
';

$public_event_template_xml = '
		<match type="$type" date="$date" starttime="$time" endtime="$endtime" public="yes">
                        <confirmed>$confirmed</confirmed>
                        <title>$title</title>
			<clan>$clan</clan>
			<mod>$mod_fullname</mod>
			<gtv>$gtv</gtv>
                        <website>$website</website>
                        <description>$description</description>
		</match>
';

// Private (if show_public=no) - we hide personal datas
$private_event_template = '
<div>
<h3>$time - $endtime Booked</h3>
<b>$confirmed</b><br />
</div>
';

$private_event_template_xml = '
		<match date="$date" starttime="$time" endtime="$endtime" public="no">
			<confirmed>$confirmed</confirmed>
		</match>
';

//**** Printing functions ****
function print_header() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>OA Clan Planner - Calendar</title>
                <script type="text/javascript" src="lib.js"></script>
                <!-- includes necessary for the sliding divs effect -->
                <script type="text/javascript" src="lib/jquery.js"></script>
                <script type="text/javascript" src="slidingdivs.js"></script>
                <script type="text/javascript">
                $(document).ready(function(){

                    $('.show_hide').showHide({
                         speed: 500,  // speed you want the toggle to happen
                         easing: '',  // the animation effect you want. Remove this line if you dont want an effect and if you haven't included jQuery UI
                         changeText: 1, // if you dont want the button text to change, set this to 0
                         showText: 'Show',// the button text to show when a div is closed
                         hideText: 'Hide' // the button text to show when a div is open

                     });

                });
                </script>

	</head>
<body onload="autodetect_timezone();">
<?php
}

function print_header_xml($servername, $date, $timezone = '') {
	header ("Content-Type: text/xml");
	echo '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
?>
<!DOCTYPE root [
	<!ELEMENT root (licence* | matchs+)+>
	<!ELEMENT licence (#PCDATA)>
	<!ELEMENT matchs (match*)>
	<!ATTLIST matchs servername CDATA #REQUIRED
				week CDATA #REQUIRED
				startday CDATA #REQUIRED
				lastday CDATA #REQUIRED
				timezone CDATA #REQUIRED>
	<!ELEMENT match (confirmed?, title?, clan?, mod?, gtv?, website?, description?)>
	<!ATTLIST match type CDATA #IMPLIED
					date CDATA #REQUIRED
					starttime CDATA #REQUIRED
					endtime CDATA #REQUIRED
					public (yes|no) #IMPLIED>
    <!ELEMENT confirmed (#PCDATA)>
    <!ELEMENT title (#PCDATA)>
    <!ELEMENT clan (#PCDATA)>
    <!ELEMENT mod (#PCDATA)>
    <!ELEMENT gtv (#PCDATA)>
    <!ELEMENT website (#PCDATA)>
    <!ELEMENT description (#PCDATA)>
]>
<?php
	list($year, $week, $firstday, $lastday) = get_weekdate($date);
	if ($timezone == "0" or $timezone == '') {
		$timezone = '';
	} elseif ($timezone >= 0 and strpos($timezone, '-') === False) {
		$timezone = "+".$timezone; // Adding the plus sign if the timezone is positive
	}
	echo "<root>\n";
	echo "	<!-- <licence>Affero GPL v3+</licence> -->\n";
	echo '	<matchs servername="'.$servername.'" week="'.$week.'" startday="'.$firstday.'" lastday="'.$lastday.'" timezone="UTC'.$timezone.'">'."\n";
}

function print_footer() {
?>
	<br /><br />
	<a href="<?php echo $_SERVER['REQUEST_URI']; if (strpos($_SERVER['REQUEST_URI'], '?') === False) echo '?'; ?>&xml"><img src="xml-button.jpg" alt="XML version of the events" title="Click here to print the XML version of the events" /></a>
	<a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="agplv3.png" alt="This application is licensed under Affero GPL v3+" title="This application is licensed under Affero GPL v3+" /></a>
	</body>
</html>
<?php
}

function print_footer_xml() {
?>
	</matchs>
</root>
<?php
}

// Function to draw the day name and number
function show_day($date) {
    $day = date('l jS', strtotime($date));

    echo '<h2>'.$day.'</h2>';
    //echo '<h2>'.$day.' ('.$date.')</h2>';
}

// Function to print events based on templates
function print_event($event) {
	global $private_event_template;
	global $public_event_template;

	if ($event['show_public'] == 'no') { // If the event is private, we print the private template with minimal informations
		echo  strtr($private_event_template, array_prefixkeys($event,'$'));
	} else { // Else, the event is public, and so we print the public template
		echo  strtr($public_event_template, array_prefixkeys($event,'$'));
	}
}

function print_event_xml($event) {
	global $private_event_template_xml;
	global $public_event_template_xml;

	if ($event['show_public'] == 'no') { // If the event is private, we print the private template with minimal informations
		echo  strtr($private_event_template_xml, array_prefixkeys($event,'$'));
	} else { // Else, the event is public, and so we print the public template
		echo  strtr($public_event_template_xml, array_prefixkeys($event,'$'));
	}
}

// Function to print the links to get to previous or next week calendars
function show_navlinks($servername, $date, $timezone) {
    $prevweek = date('Y-m-d', strtotime($date.' -1 week'));
    $nextweek = date('Y-m-d', strtotime($date.' +1 week'));

    echo '<a class="prevweeklink" href="?server_name='.$servername.'&start_date='.$prevweek.'&timezone='.$timezone.'">&lt;- Previous week</a>';
    echo '<a class="prevweeklink" href="?server_name='.$servername.'&start_date='.$nextweek.'&timezone='.$timezone.'">Next week -&gt;</a>';
}

// Function to print the title of the page (title + month, year and week's starting/ending dates)
function show_title($date) {
    list($year, $week, $firstday, $lastday) = get_weekdate($date);

    echo '<h1>Bookings Calendar<br />'.date('F Y', strtotime($firstday)).'</h1>';
    echo '<div class="titledate">Week '.$week.' (from '.$firstday.' to '.$lastday.')</div>';
}

// Function to print the selectbox to change the server's calendar
function show_serverslist() {
include('config.php');
?>
    Calendar for server:
	<select name="server_name" id="server_name" onchange="refresh_calendar();">
		<option value=""></option>
        <?php print_options($servers, $_GET['server_name']); ?>
	</select><br />
<?php
}

// Function to print the selectbox to change the timezone
function show_timezoneslist() {
include('config.php');
?>
    Timezone:
	<select name="timezone" id="timezone" onchange="refresh_calendar();">
        <?php
		$timezones = array();
		for ($i=$timezone_lower_limit;$i<$timezone_upper_limit;$i++) {

			// Calculating and formatting the time corresponding to each slot
			$time = convert_slots_to_time($i, 30, ':'); //dev notes: pour implémenter dans save: reconvertir slots en heure, et heure en slots selon le bareme courant ($minutes_per_slot)

			$i != 0 ? $index = $time : $index = 0; // UTC+0 will have an empty string as its index if we don't manually set the index to 0 for this special case (what is done here)

			if ($i > 0) {
				$time = "+".$time;
			} elseif ($i < 0) {
				null; // when $time is negative, it already has the minus sign
			} else {
				$time = "";
			}

			// Storing in the array
			$timezones["$index"] = "UTC".$time;
		}

		// Printing the select list and options
		if (!isset($_GET["timezone"]))  $_GET["timezone"] = "0";
		print_options($timezones, $_GET['timezone'], true);
	?>
	</select><br />
<?php
}

function print_noevent() {
?>
<div class="noevent">No event</div>
<?php
}


/***********************************/
/************** MAIN **************/
/***********************************/

// If the required datas are empty, we set default values
if (empty($_GET['server_name'])) $_GET['server_name'] = ''; // No need to set a default server, if none is chosen we ask the user to choose another one by himself
if (empty($_GET['start_date'])) $_GET['start_date'] = date('Y-m-d'); // If no date is given, we choose today


// Printing header and common elements
if (isset($_GET['xml'])) {
	if (isset($_GET['timezone'])) {
		print_header_xml($_GET['server_name'], $_GET['start_date'], $_GET['timezone']);
	} else {
		print_header_xml($_GET['server_name'], $_GET['start_date']);
	}
} else {
	print_header();
	show_title($_GET['start_date']);
	show_timezoneslist();
	show_serverslist();
}

// If the selected server doesn't exists (or empty choice), we show an error
if (!array_key_exists($_GET["server_name"], $servers)) {
    echo "The selected server is not available for booking (stop trying to abuse the form!).";

// Else, we show the events for this server
} else {
	// Print the weeks navigation links
	if (!isset($_GET['xml'])) show_navlinks($_GET["server_name"], $_GET["start_date"], $_GET["timezone"]);
	// Getting the corresponding weekfile for the date
	$weekfilename = get_weekfilename($jobspath, $_GET['server_name'], $_GET['start_date']);

	// Timezone setting: we get the filename of the previous and next weekfile
	list($year, $week, $firstday, $lastday) = get_weekdate($_GET['start_date']);

	$weekfilenameprev = get_weekfilename($jobspath, $_GET['server_name'], date('Y-m-d', strtotime($_GET['start_date'].' - 1 week')));
	$weekfilenamenext = get_weekfilename($jobspath, $_GET['server_name'], date('Y-m-d', strtotime($_GET['start_date'].' + 1 week')));

	// Reading the week file and storing the week's events
	$weekevents = read_weekfile($weekfilename, $delimiter, $assign);

	// Timezone setting: we load the previous and next weekfile and shift the timezone
	$weekevents = array_merge($weekevents, read_weekfile($weekfilenameprev, $delimiter, $assign), read_weekfile($weekfilenamenext, $delimiter, $assign));

	// Bugfix: add padding 0 for hours so that the times are compared in a good manner (else it can compare something like 6:30 to 18:30, putting 18:30 before 6:30. Now with padding zeros, we have 06:30 and 18:30)
	foreach ($weekevents as &$event) {
		$event['time'] = str_pad($event['time'], 5, "0", STR_PAD_LEFT);
	}

	// Sort the week's events by ascending date then time
	$weekevents = array_sortbydatetime($weekevents);

	// Timezone setting: Shift every event date and time relatively to the selected timezone, and then slice to keep only the pertinent events (the events happening this week)
	$weekevents = array_shift_timezone($weekevents, $_GET['timezone']); // shifting the date and time with timezone
	$weekevents = slice_events($weekevents, $firstday, $lastday); // keep only this week's events

	// If the week file is non-existent or is empty (array is empty) for the current week, it means we have no event at all
	if (empty($weekevents)) {
	    if (!isset($_GET['xml'])) print_noevent();
	// Else, the week file contains pertinent datas
	} else {

	    // Printing each events
	    $currentday = ''; // This will store the current day (if we change, we will output a new div with a new day name and date)
            $counter = 0; // counter that will increment for each iteration, it helps to do some stuff like ID naming HTML objects
	    foreach ($weekevents as &$event) { // We print each event of the week
                $counter++;
                $event['counter'] = $counter; // just copy the counter inside $event so that it gets replaced in the templates

		// If the day has changed, we print the new day
		if ($currentday != $event['date']) {
		    $currentday = $event['date'];
		    if (!isset($_GET['xml'])) show_day($event['date']);
		}

		// Defining/Reformatting variables
		$event['time'] = date('H:i', strtotime($event['time'])); // Reformatting start_time (adding padding 0)
		$duration = convert_duration_to_seconds($event['duration']); // Converting the duration to seconds to...
		$event['endtime'] = date("H:i", strtotime($event['time']) + $duration); // ...calculate the end_time
                if (!isset($_GET['xml'])) $event['description'] = htmlspecialchars_decode($event['description']); // decode description HTML special chars, but only if not in XML mode (else we have to keep the chars encoded for the XML to be valid and for the HTML tags to not conflict with the XML tags)
                if (!empty($event['website']) and !isset($_GET['xml'])) { // add a href (link) tag to the website
                    if (strpos($event['website'],'http://') === false) {
                        $link = 'http://'.$event['website']; // if the http:// prefix is missing, we add it
                    } else {
                        $link = $event['website'];
                    }
                    $event['website'] = '<a href="'.$link.'" target="_blank">'.$event['website'].'</a>'; // put the a href tag
                }
		#$modindex = multidimensional_search($mods, array($event['mod'],$event['config'])); // Getting the mod's fullname from the mod shortname and config
		$modindex = $event['modindex'];
		$modindex == -1 ? $event['mod_fullname'] = "Unknown" : $event['mod_fullname'] = $mods[$modindex]['modname']; // If we could not find the specified mod + config in the list of available mods ($mods array), then we show Unkown, else if it was found we show the mod's fullname
		if (array_key_exists($event['type'],$types)) { // If the supplied booking type is standard (which should be the case if registered via the form), we get the text full name of this type, else we output it as it is (this leaves space to add custom types)
		    $event['type'] = $types[$event['type']]; // Getting the text name of the type
		}
		// Getting confirmation if register_moderation is enabled
		if (isset($event['confirmcode'])) {
		    if ($event['confirmcode'] == 'yes') {
			$event['confirmed'] = 'Confirmed';
		    } else {
			$event['confirmed'] = 'Unconfirmed';
		    }
		    unset($event['confirmcode']);
		} else {
		    $event['confirmed'] = '';
		}

		// Printing the event
		if (isset($_GET['xml'])) {
			print_event_xml($event);
		} else {
			print_event($event);
		}
	    }
	}
	// Printing the weeks navigational links at the footer (after having printed every events)
	if (!isset($_GET['xml'])) show_navlinks($_GET["server_name"], $_GET["start_date"], $_GET["timezone"]);
}
// Printing the footer
if (isset($_GET['xml'])) {
	print_footer_xml();
} else {
	print_footer();
}
?>
