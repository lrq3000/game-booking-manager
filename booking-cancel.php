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
 * \description     Booking cancellation form to cancel bookings
 */

require_once('config.php');
require_once('lib.php');


/***** PRINTING FUNCTIONS AND TEMPLATES *****/

function print_header() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>OA Clan Planner - Cancelling Form</title>
		<script type="text/javascript" src="calendarDateInput.js">

		/***********************************************
		* Jason's Date Input Calendar- By Jason Moon http://www.jasonmoon.net/
		* Script featured on and available at http://www.dynamicdrive.com
		* Keep this notice intact for use.
		***********************************************/

		</script>
	</head>
<body>
	<h1>OA Clan Planner - Cancelling Form</h1>
<?php
}

function print_footer() {
?>
	<br /><br />
	<a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="agplv3.png" alt="This application is licensed under Affero GPL v3" title="This application is licensed under Affero GPL v3" /></a>
	</body>
</html>
<?php
}

function show_msg($msg) {
	print_header();
	echo '<div class=notify>'.$msg.'</div>';
	print_footer();
	exit();
}

function show_error($msg) {
	print_header();
	echo '<div class=msgerror>ERROR: '.$msg.'</div>';
	print_footer();
	exit();
}

function show_form($msgerror = null) {
include('config.php');

print_header();
?>
	<div class="msgerror">
	<?php
		if (!empty($msgerror)) {
			echo "There is an error in the submitted form:<br />";
			echo $msgerror;
		}
	?>
	</div>
	<form method="get" action="?">
		Server that you booked:
		<select name="server_name" id="server_name">
		<?php print_options($servers, $_POST["server_name"]); ?>
		</select><br />

		Date of the booking (at UTC time): <script>DateInput('start_date', true, 'YYYY-MM-DD', 1 <?php if (!empty($_GET["start_date"])) { echo ', "'.$_GET["start_date"].'"'; } ?> )</script>

		Cancel code: <input type="text" name="deletecode" id="deletecode" /><br />

		<em>Note: Cancelling your booking will give your credits back for the week (so you can again book the freed time).</em><br />
		<em>Note2: You cannot cancel your booking anymore if it happens today or in the past.</em><br />

		<input type="submit" name="submit" value="Cancel booking" />
	</form>
<?php
print_footer();
exit();
}

/************************/
/********* MAIN ********/
/************************/

//== BOOKING CANCELLATION
// Description: cancel a booking from the weekfile and the slots file (so that the user can again book the freed time and the slots freed can again be booked by someone else)
if (empty($_GET["deletecode"]) or empty($_GET["server_name"]) or empty($_GET["start_date"])) {
	show_form();
} else {

        if (str_replace('-','',$_GET["start_date"]) <= str_replace('-','',date('Y-m-d'))) {
		show_error('You cannot delete a booking that has happened in the past or today!');
        }

	//---- Fetching the event to be confirmed from the weekfile
	$weekfilename = get_weekfilename($jobspath, $_GET["server_name"], $_GET["start_date"]);
	$weekevents = read_weekfile($weekfilename, $delimiter, $assign);

	$compare = array(
						'deletecode' => $_GET["deletecode"],
						//'date' => $_GET["start_date"],
	);
	$similarevents = similar_events($weekevents, $compare, $clan_moderation);

	// Cannot find the booking
	if (count($similarevents) <= 0) {
		show_error('No booking could be found with this date/server/cancel code. The booking has either been already cancelled or the date/server/cancel code is wrong.');
	// Else, booking found with this code
	} else {
		// Check if the date is ok (if today or past, cannot cancel)
		if ($similarevents[0]['date'] <= date('Y-m-d')) {
		    show_error('You cannot delete a booking that has happened in the past or today!');
		}

		//---- Memorize the data of the booking (to delete the slots from slotsfile)
		$_GET['clan_name'] = $similarevents[0]['clan'];
		$_GET['start_date'] = $similarevents[0]['date'];
		$_GET['start_slot'] = convert_time_to_slots($similarevents[0]['time'], $minutes_per_slot, ':');
		$_GET['duration'] = convert_time_to_slots($similarevents[0]['duration'], $minutes_per_slot, 'h');

		//---- Loading the slots data (slots filename depends on the servername and startdate)
		list($slotsfilename, $slots2filename, $slots, $slots2) = get_slots($nbslots, $jobspath, $delimiter, $assign, $_GET['server_name'], $_GET['start_date'], $_GET['start_slot'], $_GET['duration']);

		//---- Configuring the slots (we empty the slots that were cancelled)
		$start_data = $end_data = $through_data =  array('empty' => ''); // Slots configuration parameters: we empty everything
		list($slots, $slots2) = configure_slots($nbslots, $_GET['start_slot'], $_GET['duration'], $start_data, $end_data, $through_data, $slots, $slots2);

		//---- Saving the slots
		$saveok = true;
		$saveok2 = true;

		$saveok = save_slotsfile($slotsfilename, $nbslots, $slots, $delimiter, $assign);

		if ($_GET['start_slot']+$_GET['duration'] >= $nbslots) {
			$saveok2 = save_slotsfile($slots2filename, $nbslots, $slots2, $delimiter, $assign);
		}

		// If the saving could not complete, we stop the procedure and throw an error
		if (!$saveok or !$saveok2) {
			show_error('BUG-CRITICAL: could not save into the slotfile, please contact the administrator to fix this bug. Your event could not be confirmed.');

		// Else we have successfully saved the new slots file, and we now update the week events
		} else {
			unset($weekevents[$similarevents[0]['originalindex']]); // We delete this event entry from the week file
			// We save the new weekfile with the confirmed event
			if (!save_weekfile($weekfilename, $weekevents, $delimiter, $assign)) {
				// If the saving could not complete, throw an error
				show_error('BUG: could not save into the weeksfile, please contact the administrator to fix this bug. Your event could NOT be deleted.');

			} else {
				// Else, everything is OK ! Event confirmed !
				show_msg('Your event on server '.$servers[$_GET['server_name']].' on '.$_GET['start_date'].' at '.convert_slots_to_time($_GET['start_slot'], $minutes_per_slot, ':').' for clan '.$_GET['clan_name'].' has been successfully <strong>cancelled</strong> and the free slots have been recredited to the clan for another booking.');
			}
		}

		exit();
	}
}
?>
