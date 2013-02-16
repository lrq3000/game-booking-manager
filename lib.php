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
 * \description     Library of functions for booking management (critical for the application)
 */

// Notes :
// list() = assign multiple variables from a function (ref to the php manual)

// Make sure that the time is UTC (for all bookings) - may not necessarily be needed in our system (since it's absolute, it doesn't rely on the system's calendar), but it's a good practice
date_default_timezone_set('UTC'); // Should be called everytime we have to use strtotime

/********************************************/
/***** GENERAL USAGE FUNCTIONS *****/
/********************************************/
function convert_slots_to_time($slots, $minutes_per_slot, $separator, $hideemptyminutes = false) {
	$hours = ($slots*$minutes_per_slot)/60;
	if ($hours > 0) { // if the number is positive, we floor the number of hours (so we can get something like 0:30)
		$hours = floor($hours);
	} else { // else if the number is negative (eg: timezones), we truncate to the ceiling the number of hours (so we can get something like -0:30)
		$hours = ceil($hours);
	}
	$minutes = str_pad(abs(($slots*$minutes_per_slot)%60), 2, "0", STR_PAD_LEFT); // get the number of minutes from the slots. We get the absolute value because else we may get something like -1:-30, which is not a human format, the valid one would be -1:30

	if ($hideemptyminutes and $minutes == 0) {
		return $hours.$separator;
	} else {
		return implode($separator, array($hours, $minutes));
	}
}

function convert_time_to_slots($time, $minutes_per_slot, $separator) {
	list($hours, $minutes) = explode($separator, $time);

	$minutes = $minutes+($hours*60);

	return floor($minutes/$minutes_per_slot);
}

// Convert a duration in the format XXhXX or XXh into seconds
function convert_duration_to_seconds($duration) {
    list($hours, $minutes) = explode('h', $duration);

    $seconds = $hours*3600;
    if (!empty($minutes)) {
        $seconds += $minutes*60;
    }

    return $seconds;
}

function invert_rows_columns($array) {
    $iarray = array();
    for($i=0;$i<count($array);$i++) {
        $elt = $array[$i];
        foreach ($elt as $param=>$value) {
            $iarray[$param][$i] = $value;
        }
    }

    return $iarray;
}

// Function that add a prefix to the keys of an array, used for templating
function array_prefixkeys($array, $prefix) {
    foreach($array as $k => $v){
        $array[$prefix.$k] = $v;
        unset($array[$k]);
     }

     return $array;
}

// Custom array compare function used to sort the weekevents multidimensional array by the date and time
function array_compare_datetime($a, $b)
{
     return strcmp($a['date'].' '.$a['time'], $b['date'].' '.$b['time']);
}

// Function to sort a week events array by date and then by time
function array_sortbydatetime($events) {
	usort($events, 'array_compare_datetime'); // We use a custom comparison function callback to sort by date and time
	return $events;
}

// Apply the timezone shift for all events in an array (the array must contain a field time and a field date)
function array_shift_timezone($events, $timezone) {
	if (strtotime($timezone) != 0) { // If the timezone is UTC, we don't have to recompute anything
		// For each event (containing two fields: date and time)
		foreach ($events as &$event) {
			// Computing the timezone shift for the current event date and time
			$timezone_shift = strtotime(str_replace('-', '', $timezone)) - strtotime('0:00'); // Getting the relative time (we substract the absolute time of today at 0:00 to the timezone, which gives us the relative time)
			if ($timezone != str_replace('-', '', $timezone)) $timezone_shift = -$timezone_shift; // The relative time we get is always positive, but if the timezone was negative we here set the timeshift to the negative
			$newtime = strtotime($event['date'].' '.$event['time']) + $timezone_shift; // we add the timezone shift to the event date and time, so that we get the new time relative to the selected timezone

			// Save the new date and time relative to the selected timezone
			$event['time'] = date('H:i', $newtime);
			$event['date'] = date('Y-m-d', $newtime);
		}
	}
	return $events;
}


// Check if a file is empty
function file_is_empty($filename) {
	if (!file_exists($filename)) {
		return true;
	} else {
		if (trim(file_get_contents( $filename )) == '') {
			return true;
		} else {
			return false;
		}
	}
}

// Function to print the options of a <select> form element, and reselect automatically the right value if specified in $currentvalue
// $array : contains all the possible options (key being the index/html value of the options and value the text shown)
// $currentvalue : currently selected value (will automatically select the option that contains this key)
// $stringcompare : compare strings instead of number?
function print_options($array, $currentvalue = null, $stringcompare = false) {
	if (count($array) <= 0) {
		echo '<option value=""></option>';
	} else {
		foreach ($array as $value=>$text) {
			// Reselect the same option if form is uncomplete
			$selected = "";
			if (isset($currentvalue)) {
				if ($stringcompare) {
					if (strcmp($value,$currentvalue) == 0) $selected='selected="true"';
				} else {
					if ($value == $currentvalue) $selected='selected="true"';
				}
			}
			// Output the option
			echo '<option value="'.$value.'" '.$selected.'>'.$text.'</option>';
		}
	}
}

// Function that reads a string line containing parameters and returns an array in the format :
// $params['param'] = 'value'
function read_params($line, $delimiter, $assign) {
	// Fetching all the parameters and values of the event (one event per line)
	preg_match_all('/(?P<param>\w+)('.$assign.'(?P<value>[^'.$delimiter.']+))?/', $line, $matchs);

	// If we get no match then we just return the line as it is
	if (empty($matchs['param'])) {
		return $line;
	// Else if we've matched
	} else {
		// We pad values missing for some param which don't need values
		array_pad($matchs['value'], count($matchs['param']), 'na');

		// Formatting the datas of the event and storing inside a new event in the events array
		$params = array_combine($matchs['param'], $matchs['value']);

		return $params;
	}
}

// Function that uses an array of parameters (from read_params for instance), and returns a string line
function assemble_params($data, $delimiter, $assign) {
	$slot = ''; // Var containing the final reassembled slot line to output
	// If the slot is not empty ...
	if (!empty($data)) {
		$slot = array();
		// ... for each parameter and value ...
		foreach ($data as $param => $value) {
			// ... if the value is not empty ...
			if (isset($value) && $value != '') {
				// ... we reassemble the parameter and the value with the $assign sign
				$slot[] = implode($assign, array($param, $value));
			} else {
				// ... else we just keep the parameter
				$slot[] = $param;
			}
		}

		// Now that all couples of parameters/values are assembled, we assemble with $delimiter all these couples together to form the final slot line
		$slot = implode($delimiter, $slot); // If the slot is not empty, we glue it to a string
	} // Else if the slot is empty, we output an empty string

	return $slot;
}

function getUniqueCode($length = 0)
{
	$code = md5(uniqid(rand(), true));
	if ($length > 0) return substr($code, 0, $length);
	else return $code;
}


/*****************************************/
/***** SLOTS FILE MANAGEMENT *****/
/*****************************************/
function get_slotsfilename($jobspath, $servername, $startdate) {
    return $jobspath.$servername.'-'.$startdate.'.txt';
}

function create_slotsfile($slotsfilename, $nbslots) {
	$handle = fopen($slotsfilename, 'w') or die("Can't create the slots file! Please contact the administrator to fix this bug and copy/paste this message.");
	fwrite($handle, $nbslots."\n"); // header : number of total slots
	for ($i = 0;$i<$nbslots;$i++) {
		fwrite($handle, "slot$i: empty\n");
	}
	fclose($handle);
}

function read_slotsfile($slotsfilename, $nbslots, $delimiter, $assign) {
	$slots = array();
	$handle = fopen($slotsfilename, 'r');
	if ($handle)
	{
		// The first line of the slots file always contain the header = the number of slots
		$line = fgets($handle);
		// If the numbre of slots of the file is different from the number of slots we have configured in this script (parameters editing ?) we recreate a new slots file (BEWARE : all old datas are lost !)
		if ($line != $nbslots) {
			fclose($handle);
			create_slotsfile($slotsfilename, $nbslots); // Reset the slots file
			$handle = fopen($slotsfilename, 'r');
		}
		while (!feof($handle))
		{
			// Reading one line at a time
			$line = fgets($handle);

			if (!empty($line)) {

				preg_match('/slot(?P<slotnb>\d+):\s*(?P<args>.+)?/', $line, $matchs);
				// If the line contains a valid slot number (skipping the header), we append the slot arguments to our array
				if (isset($matchs['slotnb'])) {
					if (!empty($matchs['args'])) {
						$slots[$matchs['slotnb']] = read_params(trim($matchs['args']), $delimiter, $assign);
					} else {
						$slots[$matchs['slotnb']] = '';
					}
				}
			}
		}
		fclose($handle);
	}

	return $slots;
}

// Combining function that uses get_slotsfilename, create_slotsfile and read_slotsfile for easier use
// Returns an array with $slotsfilename, $slots2filename, $slots and $slots2
function get_slots($nbslots, $jobspath, $delimiter, $assign, $servername, $startdate, $startslot, $duration) {
	//---- Getting the slots filenames
	$slotsfilename = get_slotsfilename($jobspath, $servername, $startdate); // Slots file contains the tasks planned to happen on the server
	$slots2filename = get_slotsfilename($jobspath, $servername, date('Y-m-d', strtotime($startdate)+86400) ); // Next day of the file containing the slots (because if the duration is long enough, a booking can span accross maximum 2 days)

	//---- Creating the slots file if non existent
	// If the slots file for the selected day is inexistent, we create one with the default parameters and the necessary header (number of slots)
	if (!file_exists($slotsfilename)  or file_is_empty($slots2filename)) {
		create_slotsfile($slotsfilename, $nbslots);
	}

	if (!file_exists($slots2filename) or file_is_empty($slots2filename)) {
		create_slotsfile($slots2filename, $nbslots);
	}

	//---- Loading the slots data
	$slots = read_slotsfile($slotsfilename, $nbslots, $delimiter, $assign);

	if ($startslot+$duration >= $nbslots) {
		$slots2 = read_slotsfile($slots2filename, $nbslots, $delimiter, $assign);
	} else {
		$slots2 = null;
	}

	return array($slotsfilename, $slots2filename, $slots, $slots2);
}

// Function to check if a slot is empty or not.
// The function will loop through each parameters in the slot and read them to see if they are "empty" (in the $emptykeys list) or not. If the slot contains only "empty" parameters, then we return true so that the slot is empty, else we return false.
function check_slotempty($slot, $additionalemptykeys = null) {

	// If the slot is simply empty,  we return true already
	if (empty($slot)) {
		return true;
	// Else we will read each parameter contained in the slot and see if it's an empty parameter or not
	} else {
		// List of default empty keys (so we don't have to make it everytime we call this function)
		$emptykeys = array(
							'empty',
							'restart',
							'restart_soft',
							'restart_hard',
		);

		// We add additional empty keys list if specified at function call
		if (!empty($additionalemptykeys)) {
			$emptykeys = array_merge($emptykeys, $additionalemptykeys);
		}

		// Transtype $slot to an array in case it is a scalar (another way is by doing $slot = (array)$slot but then the string value will be transferred as a value and not a key)
		if (!is_array($slot)) {
			$slot[$slot[0]] = true;
		}

		// We now count how many empty parameters (keys) are contained in this slot
		$countemptykeys = 0; // Counter of empty parameters
		// For each parameter in a slot, ...
		foreach($slot as $param => $value) {
			// ... we check if the parameter is considered empty ...
			if (in_array($param, $emptykeys)) {
				$countemptykeys++; // ... and if it is, we increment the counter
			}
		}

		// If all the parameters of the slot are empty ...
		if (count($slot) == $countemptykeys) {
			return true; // ... we return true becaure this slot is empty ...
		} else {
			return false; // ... else this slot is not empty.
		}
	}
	// isset($array[$key]) is much more cpu efficient than array_key_exists($key, $array) - BE CAREFUL ! if you try to check isset($array($key)) but $array is not an array but a scalar, it will always return true! so transtype your scalar with $array = (array)$array;
}

function check_collision($nbslots, $startslot, $duration, $slots = null, $slots2 = null) {
    // IMPORTANT : mettre aussi restart, restart_hard et restart_soft si aucun autre parametre (donc non reservé)
    $collision = false;
    // We check every slots to know if they are booked or not
    for($i=$startslot; $i<$startslot+$duration;$i++) {

        if ($i < $nbslots) {
            $slot =& $slots[$i];
        // If the number of slots for the booking is above the total number of slots for a day, it means that the booking spans 2 days
        } elseif ($i >= $nbslots) {
            $slot =& $slots2[$i - $nbslots];
        }

        // if a slot contains 'empty' parameter or is empty or contains a restart command (and nothing else!), then we consider that there is no collision
        if (check_slotempty($slot))
        {
                $collision = false;
        // Else, a collision is detected because the slot is already booked
        } else {
                $collision = true;
        }

        if ($collision) { break; }
    }

    return $collision;
}

function configure_slots($nbslots, $startslot, $duration, $start_data, $end_data, $through_data, $slots, $slots2 = null) {
	for($i=$startslot; $i<=$startslot+$duration;$i++) {
		// We fill in $data array with all the parameters of this booking (some useful for the rotator script, some other useful only for showing in the calendar)
		$data = array();
		$data = array_merge((array)$data, (array)$through_data);
		// If this is the starting slot, we restart the server (hard restart to avoid some config glitches if they were changed in a previous booking)
		if ($i == $startslot) $data = array_merge((array)$data, (array)$start_data); // For the starting slot, we add some starting parameters (like restart to effect the changes)
		if ($i == $startslot+$duration) $data = (array)$end_data; // For the ending slot, we keep only the ending parameters (restart ?) so that we can free the server

		// In case the duration of the training span over 2 days,
		// we select the right slot from slots and slots2
		unset($slot); // First, we unset the pointer/reference
		$slot = ''; // We give it a default value
		if ($i < $nbslots) { // If we are under $nbslots then we use the 1st slots list
			$slot =& $slots[$i];
		} elseif ($i >= $nbslots) { // Else, we use the 2nd slots list
			$slot =& $slots2[$i - $nbslots];
		}


		// We configure the slot with the new data for :
		// - All the slots BUT the one after the last reserved ($i==$startslot+$duration)
		// - The one after the last slot reserved if empty or only reserved/waiting for confirmation (if not then it is already used for another booking)
		if ($i != $startslot+$duration or check_slotempty($slot, array('reserved') )) {
			$slot = $data; // We assign the new datas to the slot
		}
	}

	return array($slots, $slots2);
}

// Function that converts a slots array containing themselves arrays of parameters => values, and reassemble all these into lines that can be outputted to the specified file
function save_slotsfile($slotsfilename, $nbslots, $slots, $delimiter, $assign) {
	// For each slot
	for ($i = 0; $i<count($slots); $i++) {
		// We assemble the slot into one string
		$slot = assemble_params($slots[$i], $delimiter, $assign);

		// We store the final assembled slot line
		$slots[$i] = 'slot'.$i.': '.$slot;
	}
	// Finally, we write the slots down to the slots file
	try {
		$handle = fopen($slotsfilename, 'w');
		fwrite($handle, $nbslots."\n"); // We output the number of slots as the header of the slots file
		fwrite($handle, implode("\n",$slots)); // We concatenate each lines with a line return
		fclose($handle);
	// If we catch an error we return false
	} catch (Exception $e) {
		return false;
	}
	// Else, no exception caught, we return true
	return true;
}


/****************************************/
/***** WEEK FILE MANAGEMENT *****/
/****************************************/

// Get the year, week number, first day and last day of a given date (if empty, we get these parameters relative to the current day)
function get_weekdate($date = null) {
	if ($date == null) $date = date('Y-m-d'); // if no specific date is given, we set the date to today
	$year = date('Y', strtotime($date));
	$week =  date('W', strtotime($date));
	$firstday = date('Y-m-d', strtotime($year.'W'.$week));
	$lastday = date('Y-m-d', strtotime($firstday.' +6 days'));

	if ($firstday > $date) { // special case: at the end of the year, the year change, so that the firstday is on a different year than lastday. If firstday is greater than lastday, then this detects this change of year.
		$year = $year - 1;
		$firstday = date('Y-m-d', strtotime($year.'W'.$week));
		$lastday = date('Y-m-d', strtotime($firstday.' +6 days'));
	}

	return array($year, $week, $firstday, $lastday);
}

function get_weekfilename($jobspath, $servername, $startdate = null) {
    $week = '';
    list($year, $week, $firstday, $lastday) = get_weekdate($startdate);

    return $jobspath.$servername.'-'.$year.'-week'.$week.'.txt';
}

function create_weekfile($weekfilename) {
	$handle = fopen($weekfilename, 'w') or die("Can't create the week file! Please contact the administrator to fix this bug and copy/paste this message.");
	fclose($handle);
}

function read_weekfile($weekfilename, $delimiter, $assign) {
    $events = array();
    if (file_exists($weekfilename) and !file_is_empty($weekfilename)) {
	$handle = fopen($weekfilename, 'r');
	if ($handle)
	{
		while (!feof($handle))
		{
			// Reading the current line
			$data = array();
			$line = trim(fgets($handle));
			if (!empty($line)) {

			    // Fetching all the parameters and values of the event (one event per line) and formatting the datas of the event and storing inside a new event in the events array
			    $events[] = read_params($line, $delimiter, $assign);

			}
		}
		fclose($handle);
	}
    }

    // Return all events parameters and values
    return $events;
}

// Slice an events array to show on the calendar (eg: to show only the events over one week at a time)
function slice_events($weekevents, $firstdate, $lastdate = 99999999) {
	$firstdate = str_replace('-','',$firstdate); // it is necessary to remove the separator '-' to be able to compare the dates
	$lastdate = str_replace('-','',$lastdate);

	$firstdate_index = -1;
	$lastdate_index = count($weekevents);

	if (!empty($weekevents)) {
		$weekevents = array_sortbydatetime($weekevents); // first we need to sort out the events by date

		foreach($weekevents as $key => &$event) {
			$date = str_replace('-','',$event['date']); // it is necessary to remove the separator '-' to be able to compare the dates

			// if the current event's date is in range, we save the index
			if ($date >= $firstdate and $date <= $lastdate) {
				if ($firstdate_index == -1) $firstdate_index = $key; // found the start date
				$lastdate_index = $key + 1; // found the last date, but we don't stop until we've got the very last event of this date (we can't stop at the first occurrence because we will miss the subsequent occurrences on the same date, and that's not what we want)

			// if we've found the last date then we stop the search
			} elseif ($date > $lastdate) {
				break;
			}
		}
	}

	if ($firstdate_index == -1) {
		return array(); // if no valid event was found then we return an empty array
	} else {
		return array_slice($weekevents, $firstdate_index, $lastdate_index - $firstdate_index); // if at least one event in the specified range was found, we slice the $weekevents array to return only this (those) event(s)
	}
}

// Function that returns an array of similar events to the one given in $compare (so you can specify the fields to compare)
function similar_events($weekevents, $compare, $clan_moderation) {
	$similarevents = array(); // Array to store the similar events and return them
	// For each event in the week
	foreach ($weekevents as $eventindex => &$event) {
		// We compare each specified field to compare in $compare
		foreach ($compare as $key => $currentvalue) {
                    // If the field exists in the event...
                    if (isset($event[$key])) {
			// If we are in public registration mode, then if we are looking at the clanname, we compare it with a levenshtein distance to seek the similarities (a clan name needs not to be exactly the same !)
			// else, in closed registration system, we are sure to get valid and normalized clan names (much more CPU and memory efficient), so we don't need to calculate the levenshtein distance
			if ($key == 'clan' and !$clan_moderation) {
				if (levenshtein($event[$key], $currentvalue) < (strlen($currentvalue) / 2)) { // The clan name is similar enough if the levenshtein distance is lesser than half the size of the clan name
					// If the clanname is similar, we consider that this event is similar
					$event['originalindex'] = $eventindex; // We store the original index, will be useful to delete or edit the event
					$similarevents[] = $event;
					break;
				}
			// For all other fields, we seek equality to admit events similarity
			} else {
                            // If the fields are equal
                            if ($event[$key] == $currentvalue) {
                                    // We add this event in the list of similar events
                                    $event['originalindex'] = $eventindex; // We store the original index, will be useful to delete or edit the event
                                    $similarevents[] = $event;
                                    break;
                            }
			}
                    }
		}
	}

	return $similarevents;
}

function save_weekfile($weekfilename, $weekevents, $delimiter, $assign) {
	// We assemble each event of the weekfile (&$event is used to directly reference to the events in $weekevents, else if we did $event it would not modify the values in $weekevents but only $event inside the foreach loop)
	foreach ($weekevents as &$event) {
		$event = assemble_params($event, $delimiter, $assign);
	}
	try {
		$handle = fopen($weekfilename, 'w');
		fwrite($handle, implode("\n", $weekevents)); // We concatenate each lines with a line return
		fclose($handle);
	} catch (Exception $e) {
		return false;
	}
	return true;
}


/********************************************/
/***** REGISTRATION MODERATION *****/
/********************************************/

function get_clanfilename($jobspath) {
	return $jobspath.'clans.txt';
}

function read_clanfile($clanfilename) {
	$clans = array();
	if (!file_exists($clanfilename)) {
		$handle = fopen($clanfilename, 'w') or die("Can't create the clans file! Please contact the administrator to fix this bug and copy/paste this message.");
		fclose($handle);
	} elseif (!file_is_empty($clanfilename)) {

		$handle = fopen($clanfilename, 'r');
		if ($handle)
		{
			while (!feof($handle))
			{
				// Reading the current line
				$line = trim(fgets($handle));
				if (!empty($line)) {

				    // Fetching the clan name (one per line)
				    $clans[$line] = $line;

				}
			}
			fclose($handle);
		}
	}

	return $clans;
}

function save_clanfile($clanfilename, $clans) {
	try {
		$handle = fopen($clanfilename, 'w');
		fwrite($handle, implode("\n", $clans)); // We concatenate each lines with a line return
		fclose($handle);
	} catch (Exception $e) {
		return false;
	}
	return true;
}

function get_userfilename($jobspath) {
	return $jobspath.'users.txt';
}

function read_userfile($userfilename, $delimiter, $assign) {
	$users = array();
	if (!file_exists($userfilename)) {
		$handle = fopen($userfilename, 'w') or die("Can't create the users file! Please contact the administrator to fix this bug and copy/paste this message.");
		fclose($handle);
	} elseif (!file_is_empty($userfilename)) {

		$handle = fopen($userfilename, 'r');
		if ($handle)
		{
			while (!feof($handle))
			{
				// Reading the current line
				$line = trim(fgets($handle));
				if (!empty($line)) {

				    // Fetching the username and password
				    list($username, $userdata) = explode($delimiter, $line, 2);
				    $users[$username] = read_params($userdata, $delimiter, $assign);

				}
			}
			fclose($handle);
		}
	}

	return $users;
}

function save_userfile($userfilename, $users, $delimiter, $assign) {
	$finalusers = array();
	foreach ($users as $username => $userdata) {
		$finalusers[] = $username.$delimiter.assemble_params($userdata, $delimiter, $assign);
	}
	try {
		$handle = fopen($userfilename, 'w');
		fwrite($handle, implode("\n", $finalusers)); // We concatenate each lines with a line return
		fclose($handle);
	} catch (Exception $e) {
		return false;
	}
	return true;
}

// Function to generate a human readable and memorizable password.
// We can specify if numbers have to be included.
// A generated password will contain a suite of words and letters, randomly picked, from a wordslist (for the words), and a password cannot begin by a number.
function generate_human_password($pwdwordlist, $numbersmandatory = false, $minlength = 12, $maxlength = 0) {

	$pass_is_satisfactory = false;
	while (!$pass_is_satisfactory) {
		$password = '';
		if ($minlength > 0) {
			while (strlen($password) < $minlength) {
				if (mt_rand(0, 2) == 1 and $password != '') { // One chance on 3 to generate a number. And we can't put a number as the first character, it will necessary be a word.
					$password .= mt_rand(0,99);
				} else { // Else we append a random word picked from a wordslist
					$password .= $pwdwordlist[mt_rand(0, count($pwdwordlist)-1)];
				}
			}
			if ($maxlength > 0 and strlen($password) > $maxlength) {
				substr($password, 0, $maxlength);
			}
		}

		// If we need numbers in our password, we check for that
		if (!$numbersmandatory) {
			$pass_is_satisfactory = true;
		} else {
			if (preg_match('/\d+/', $password) > 0) {
				$pass_is_satisfactory = true;
			}
		}

		// An human readable password cannot contain too many numbers, we have to check for that too
		if (preg_match('/\d/', $password) > (strlen($password) / 3)) {
			$pass_is_satisfactory = false;
		}
	}

	// return the password
	return $password;
}

function obscure($password, $salt = null, $algorithm = "whirlpool")
{
	// Get some random salt, or verify a salt.
	// Added by (grosbedo AT gmail DOT com)
	if ($salt == NULL)
	{
	    $salt = hash($algorithm, uniqid(rand(), true));
	}

	// Determine the length of the hash.
	$hash_length = strlen($salt);

	// Determine the length of the password.
	$password_length = strlen($password);

	// Determine the maximum length of password. This is only needed if
	// the user enters a very long password. In any case, the salt will
	// be a maximum of half the end result. The longer the hash, the
	// longer the password/salt can be.
	$password_max_length = $hash_length / 2;

	// Shorten the salt based on the length of the password.
	if ($password_length >= $password_max_length) {
		$salt = substr($salt, 0, $password_max_length);
	} else {
		$salt = substr($salt, 0, $password_length);
	}

	// Determine the length of the salt.
	$salt_length = strlen($salt);

	// Determine the salted hashed password.
	$salted_password = hash($algorithm, $salt . $password);

	// If we add the salt to the hashed password, we would get a hash that
	// is longer than a normally hashed password. We don't want that; it
	// would give away hints to an attacker. Because the password and the
	// length of the password are known, we can just throw away the first
	// couple of characters of the salted password. That way the salt and
	// the salted password together are the same length as a normally
	// hashed password without salt.
	$used_chars = ($hash_length - $salt_length) * -1;
	$final_result = $salt . substr($salted_password, $used_chars);

	return $final_result;
}

// Multidimensional array search (see php.net manual for array_search) enhanced by Grosbedo
// Finds the key of a two-dimensional array where both parameters in $searched are found.
// eg: $searched = array('mod' => 'as', 'config' => 'as-instagib') and $parents = $mods will find the index of the mod that contains both the specified mod and config (if we specify only the mod, then the first 'as' mod will be returned).
// Currently used for the calendar to find the fullname of a mod from the mod shortname and config.
// Return	if ok index, ko -1
function multidimensional_search($parents, $searched) {
	if (!empty($searched) and !empty($parents)) {

	      foreach ($parents as $key => $value) {
		$count = 0;
		foreach ($searched as $skey => $svalue) {
		  if (in_array($svalue, $value)) {
		    $count++;
		  }
		}
		if($count == count($searched)){ return $key; }
	      }
	}

	return -1;
}

?>
