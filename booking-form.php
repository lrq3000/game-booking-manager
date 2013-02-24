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
 * \description     Booking form to register bookings
 */

require_once('config.php');
require_once('lib.php');


/***** PARAMETERS *****/


/***** PRINTING FUNCTIONS AND TEMPLATES *****/

//---- Templates
$mailmessage_template = '
				<h1>Booking summary</h1>
				<div>
				Type: $booking_type<br />
				Server: $server_fullname<br />
				With mod: $mod_fullname<br />
				Server\'s password (g_password): $g_password<br />
				Referee password (use /ref "password" to login): $ref_password<br />
				On: $start_date<br />
				From: $time (UTC)<br />
				To: $endtime (UTC)<br />
				For clan: $clan_name<br />
				GTV: $gtv_enabled<br />
				<em>Note: If you are going to play a match, you should send the Referee password to the other team\'s leader, or if you are a league organizator, to the selected referee for the match.</em><br /><br />
				</div>

				<div>
				To confirm this booking, please click on the following link:<br />
				<a href="http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?server_name=$server_name&start_date=$start_date&confirmcode=$confirmcode">=&gt; Confirm this booking</a><br />
				<em>If this link doesn\'t work, your code is: $confirmcode</em><br /><br />

				To <strong>cancel</strong> this booking, you can click anytime (even after confirmation) on this link:<br />
				<a href="http://'.$_SERVER['HTTP_HOST'].'/'.basename(dirname(__FILE__)).'/booking-cancel.php?server_name=$server_name&start_date=$start_date&deletecode=$deletecode">Cancel this booking</a><br />
				<em>Note: Cancelling your booking will give your credits back for the week (so you can again book the freed time).</em><br />
				<em>Note2: You cannot cancel your booking anymore if it happens today or in the past.</em><br /><br />
				</div>
				<div>
				You can still book $time_left this week.
				<br /><br />
				<em>Please do NOT try to abuse the system, you risk to be BANNED. Please bear in mind that this tool was made for the entire community. Thank you.</em>
				<br /><br />
				</div>

				<div>
				Thank you for choosing our servers,  see you soon and have fun!<br />
				<a href="'.$websitelink.'" target="_blank">'.$websitename.'</a>
				</div>
				';

//---- Functions
function print_header() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>OA Clan Planner - Booking Form</title>
		<script type="text/javascript" src="booking-preview.js"></script>
		<script type="text/javascript" src="password-gen.js"></script>
		<script type="text/javascript" src="lib.js"></script>
		<script type="text/javascript" src="calendarDateInput.js">

		/***********************************************
		* Jason's Date Input Calendar- By Jason Moon http://www.jasonmoon.net/
		* Script featured on and available at http://www.dynamicdrive.com
		* Keep this notice intact for use.
		***********************************************/

		</script>
                <script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
	</head>
<body>
	<h1>OA Clan Planner - Booking Form</h1>
<?php
}

function print_footer() {
?>
	<br /><br />
	<a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="agplv3.png" alt="This application is licensed under Affero GPL v3+" title="This application is licensed under Affero GPL v3+" /></a>
	</body>
</html>
<?php
}

function show_msg($msg = null) {
	print_header();
	echo '<div class=notify>'.$msg.'</div>';
	print_footer();
	exit();
}

function show_error($msg = null) {
	print_header();
	echo '<div class=msgerror>ERROR: '.$msg.'</div>';
	print_footer();
	exit();
}

function show_ok($servername, $modname, $g_password, $ref_password, $clanname, $start_date, $start_slot, $duration, $minutes_per_slot, $deletecode = null) {
	$time = convert_slots_to_time($start_slot, $minutes_per_slot, ':');
	$duration = convert_slots_to_time($duration, $minutes_per_slot, 'h', true);
	$day = date('l', strtotime($start_date));

	print_header();
	echo "<h1>Your session is now booked!</h1>";
	echo "<div>Recap of your booking:<br />";
	echo "The server <strong>".$servername."</strong> with mod ".$modname." will be booked with server password <em>".$g_password."</em> and referee password <em>".$ref_password."</em> for the clan <strong>".$clanname."</strong> on ".$day." ".$start_date." at ".$time." (UTC) for ".$duration.".";
	if (!empty($deletecode)) echo "<br /><br />Please write down this delete code if you ever want to cancel your booking (which will give you back your booking credits) that you can enter on the booking-cancel.php form: ".$deletecode;
	echo "<br /><br /><a href=\"".$_SERVER['PHP_SELF']."\">=&gt; Return to the booking form.";
	echo "</div>";
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
	<form method="post" action="?" enctype="x-www-form-urlencoded">

            <fieldset>
                <legend>Date of your booking</legend>

                Your Timezone:
		<select name="timezone" id="timezone" onchange="bookingpreviewupdate();">
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
		if (empty($_POST["timezone"]))  $_POST["timezone"] = 0;
		print_options($timezones, $_POST["timezone"], true);
		?>
		</select><br />

                <?php
                // Autodetect client's timezone if the client didn't already set a specific timezone by himself
                if (empty($_POST["timezone"])) {
                    print '
                    <script type="text/javascript">
                        autodetect_timezone();
                    </script>
                    ';
                }
                ?>

                Date to book your training/match: <script type="text/javascript">DateInput('start_date', true, 'YYYY-MM-DD', 1 <?php if (!empty($_POST["start_date"])) { echo ', "'.$_POST["start_date"].'"'; } ?> )</script>
		Time to book:
		<select name="start_slot" id="start_slot" onchange="bookingpreviewupdate();">
		<?php
			$start_slots = array('-1' => ''); // we add an empty slot so that we require the user to choose one (if we chose one by default the user may not notice it)
			for ($i=0;$i<$nbslots;$i++) {
				// Calculating and formatting the time corresponding to each slot
				$time = convert_slots_to_time($i, $minutes_per_slot, ':');

				// Storing in the array
				$start_slots[$i] = $time;
			}
			// Printing the select options
			print_options($start_slots, $_POST["start_slot"]);
		?>
		</select><br />
		Duration of your booking:
		<select name="duration" id="duration" onchange="bookingpreviewupdate();">
		<?php
			$durations = array();
			for ($i=0;$i<=$maxduration;$i=$i+$maxduration_blocks) {
				// Calculating and formatting the time corresponding to each duration (slots to be reserved)
				$time = convert_slots_to_time($i, $minutes_per_slot, 'h', true);

				// Show only options above or equal the minimum duration for a booking, or 0 (0 means empty choice, used for user's inputs checking)
				if ($i >= $minduration or $i == 0) {
					// Store the duration in the array
					$durations[$i] = $time;
				}
			}
			// Printing the select options (without minutes if there is none, it is more clear)
			print_options($durations, $_POST["duration"]);
		?>
		</select><br />

            </fieldset>

            <fieldset>
                <legend>Server configuration</legend>

		Server to book:
		<select name="server_name" id="server_name" onchange="bookingpreviewupdate();">
		<?php print_options($servers, $_POST["server_name"]); ?>
		</select><br />

		Mod to load:
		<select name="mod_name" id="mod_name" onchange="bookingpreviewupdate();">
		<?php
		$modsnames = array();
		foreach ($mods as $key => $data) {
			$modsnames[$key] = $data["modname"];
		}
		print_options($modsnames, $_POST["mod_name"]); ?>
		</select><br />

		Server's password: <input type="text" name="g_password" id="g_password" size="30" value="<?php if (!empty($_POST["g_password"])) { echo $_POST["g_password"]; } ?>" /> <a href="#" onclick="randPassword(document.getElementById('g_password'));bookingpreviewupdate();">Generate</a><br />

		Enable GTV:
		<select name="gtv_enabled" id="gtv_enabled">
			<option value="yes">Yes</option>
			<option value="no" <?php if (!empty($_POST['gtv_enabled'])) { if ($_POST['gtv_enabled'] == 'no') echo 'selected="true"'; } ?> >No</option>
		</select><br />
		<em>* Note : with GTV enabled, external people can watch your gaming session but cannot join in nor can be seen chatting, and will see the actions with a delay (no cheating possible in matchs).</em>
		<br />

                <br />
                Map: you can change the map directly ingame, either by callvote or by using your referee/rcon password (will be automatically generated and given on the confirmation page and email).

            </fieldset>

            <fieldset>
                <legend>Necessary informations</legend>

                Your clan's name (or your nickname):
		<?php
		if ($clan_moderation or strtolower($clan_moderation) == 'yes') { ?>
			<select name="clan_name" id="clan_name" onchange="bookingpreviewupdate();">
			<?php
			$clanfilename = get_clanfilename($jobspath);
			$clans = read_clanfile($clanfilename);
			print_options($clans, $_POST['clan_name']);
			?>
			</select><br />
                <?php
		} else {
		?>
			<input type="text" name="clan_name" id="clan_name" onchange="bookingpreviewupdate();" value="<?php if (!empty($_POST["clan_name"])) { echo $_POST["clan_name"]; } ?>" /><br />
		<?php
		}
		?>

                Your email: <input type="text" name="email" id="email" value="<?php if (!empty($_POST["email"])) { echo $_POST["email"]; } ?>" /><br />
                <em>* Note : the email is solely used to verify that you are human and for you to get the confirmation email, your email will NOT be displayed publicly (hidden in the calendar) nor dispatched.</em><br />

            </fieldset>

            <?php if (strtolower($user_moderation) == 'optional' or strtolower($user_moderation) == 'yes') { ?>
            <fieldset>
                <legend>Login <?php if (strtolower($user_moderation) == 'optional') echo ' (optional)'; else echo ' (required)';?></legend>

		<?php
		if (strtolower($user_moderation) == 'yes' or strtolower($user_moderation) == 'optional') {
		?>
			<?php if (strtolower($user_moderation) == 'optional') echo 'Optional user account (if you\'ve got an unlimited user account you can use here your credentials, else leave the two next fields empty):<br />'; ?>
			Username <?php if (strtolower($user_moderation) == 'optional') echo '(optional)';?>: <input type="text" name="username" id="username" value="<?php if (!empty($_POST["username"])) { echo $_POST["username"]; } ?>" /><br />
			Password <?php if (strtolower($user_moderation) == 'optional') echo '(optional)';?>: <input type="password" name="userpass" id="userpass" value="<?php if (!empty($_POST["userpass"])) { echo $_POST["userpass"]; } ?>" /><br />
		<?php
		}
		?>
            </fieldset>
            <?php } ?>

            <fieldset>
                <legend>Description of your event</legend>

                Type:
		<select name="booking_type" id="booking_type">
			<?php print_options($types, $_POST["booking_type"]); ?>
		</select><br />

                Show in public agenda:
		<select name="show_public" id="show_public">
			<option value="yes">Yes</option>
			<option value="no" <?php if (!empty($_POST['show_public'])) { if ($_POST['show_public'] == 'no') echo 'selected="true"'; } ?>>No</option>
		</select><br />
		<em>* Note : if No is selected, the booking will still appear in the calendar but without clan name nor other extended informations.</em>
		<br />

            </fieldset>

            <fieldset>
                <legend>Additional informations about your event (this is optional)</legend>

                Title of your event: <input type="text" name="title" id="title" value="<?php if (!empty($_POST["title"])) { echo $_POST["title"]; } ?>" /><br />

                Link to your Website: <input type="text" name="website" id="website" value="<?php if (!empty($_POST["website"])) { echo $_POST["website"]; } ?>" /><br />

                Description / Notes:<br /><textarea name="description" id="description" placeholder="Type here a description for your event (this is optional)" cols="100" rows="8"><?php if (!empty($_POST["description"])) { echo $_POST["description"]; } ?></textarea><br />
                <script type="text/javascript">
                    CKEDITOR.replace( 'description' );
                </script>

            </fieldset>

            <fieldset>
                <legend>Preview</legend>

                <div id="bookingpreview">Preview: Please select the appropriate options of your booking.</div>
            </fieldset>

            <?php
            if ($enable_captcha) {
                    echo '<br />Please verify that you\'re human by solving the following CAPTCHA:';
                    require_once('recaptchalib.php');
                    $publickey = $recaptcha_public_key;
                    echo recaptcha_get_html($publickey);
            }
            ?>
            <div id="submitbutton" style="text-align: center"><input type="submit" name="submit" value="Book It!" /></div>
	</form>
<?php
print_footer();
exit();
}


/************************/
/********* MAIN ********/
/************************/

//== BOOKING CONFIRMATION
// Description: Validating the booking if confirmation mode is enabled (confirmation code sent by mail)
if (!$nomailcheck and !empty($_GET["confirmcode"]) and !empty($_GET["server_name"]) and !empty($_GET["start_date"])) {

	//---- Fetching the event to be confirmed from the weekfile
	$weekfilename = get_weekfilename($jobspath, $_GET["server_name"], $_GET["start_date"]);
	$weekevents = read_weekfile($weekfilename, $delimiter, $assign);

	$compare = array(
						'confirmcode' => $_GET["confirmcode"],
						//'date' => $_GET["start_date"],
	);
	$similarevents = similar_events($weekevents, $compare, $clan_moderation); //here we don't get a similar event but the exact event we seek to confirm

	// No event could be found with this confirmation code
	if (count($similarevents) <= 0) {
		show_error('The entered confirmation code is invalid. Your event could not be confirmed. (The event may already have been confirmed?)');
	// Else, we've found an event to confirm with this code
	} else {
		//---- Registering the event in the slots file (replacing "reserved" slots)
		$_GET["mod_name"] = $similarevents[0]['modindex']; # fetching back the index of the selected mod in the mods[] array
		$_GET["clan_name"] = $similarevents[0]['clan'];
		$_GET["g_password"] = $similarevents[0]['password'];
		$_GET["ref_password"] = $similarevents[0]['refpassword'];
		$_GET["gtv_enabled"] = $similarevents[0]['gtv'];
		$_GET["show_public"] = $similarevents[0]['show_public'];
		$_GET["start_date"] = $similarevents[0]['date'];
		$_GET["start_slot"] = convert_time_to_slots($similarevents[0]['time'], $minutes_per_slot, ':');
		$_GET["duration"] = convert_time_to_slots($similarevents[0]['duration'], $minutes_per_slot, 'h');
		$_GET["deletecode"] = $similarevents[0]['deletecode'];

		// Slots configuration parameters
		$through_data = array(
							'password' => $_GET['g_password'],
							'refpassword' => $_GET['ref_password'],
							'clan' => $_GET['clan_name'],
							//'mod' => $_GET['mod_name'],
							//'config' => $_GET['config'],
							'gtv' => $_GET['gtv_enabled'],
							'show_public' => $_GET['show_public'],
						      );
		// Logging mods/oamps parameters
		foreach ($mods[$_GET['mod_name']] as $param => $value) {
			# we copy every parameters for the selected mod, except the name (which is not needed for game rotator and can cause bugs because of special characters like spaces or semicolons)
			if ($param != 'modname') $through_data[$param] = $value;
		}
		$start_data = array(
						'restart_hard' => '',
						);
		$end_data = array('restart_soft' => '',
						);

		//---- Loading the slots data (slots filename depends on the servername and startdate)
		list($slotsfilename, $slots2filename, $slots, $slots2) = get_slots($nbslots, $jobspath, $delimiter, $assign, $_GET['server_name'], $_GET['start_date'], $_GET['start_slot'], $_GET['duration']);

		//---- Configuring the slots (no need to check the data, this should have been done prior to the first temporary registration in the week and slots files)
		list($slots, $slots2) = configure_slots($nbslots, $_GET['start_slot'], $_GET['duration'], $start_data, $end_data, $through_data, $slots, $slots2);

		//---- Saving the slots
		$saveok = true;
		$saveok2 = true;

		$saveok = save_slotsfile($slotsfilename, $nbslots, $slots, $delimiter, $assign);

		if ($_GET['start_slot']+$_GET['duration'] >= $nbslots) {
			$saveok2 = save_slotsfile($slots2filename, $nbslots, $slots2, $delimiter, $assign);
		}

		if (!$saveok or !$saveok2) {
			// If the saving could not complete, we stop the procedure and throw an error
			show_error('BUG-CRITICAL: could not save into the slotfile, please <a href="mailto:'.$admin_mail.'">contact the administrator</a> to fix this bug. Your event could not be confirmed.');

		} else {
			// Else we have successfully saved the new slots file, and we now update the week events
			$weekevents[$similarevents[0]['originalindex']]['confirmcode'] = 'yes'; // We edit the confirmcode of the confirmed event
			// We save the new weekfile with the confirmed event
			if (!save_weekfile($weekfilename, $weekevents, $delimiter, $assign)) {
				// If the saving could not complete, throw an error
				show_error('BUG: could not save into the weeksfile, please <a href="mailto:'.$admin_mail.'">contact the administrator</a> to fix this bug. Your event IS confirmed but will not show as confirmed in the calendar.');

			} else {
				// Else, everything is OK ! Event confirmed !
				show_ok($_GET['server_name'], $mods[$_GET['mod_name']]['modname'], $_GET['g_password'], $_GET['ref_password'], $_GET['clan_name'], $_GET['start_date'], $_GET['start_slot'], $_GET['duration'], $minutes_per_slot, $_GET['deletecode']);
			}
		}
	}
	exit();
}


//== FIRST BOOKING (with or without moderation)
$msgerror = ''; // This var will contain all error messages to be printed to the user in the form for him to fix
// If form is drawn for the first time, then we show the empty form
if (empty($_POST["submit"])) {
	show_form($msgerror);

// Else, if the form has already been submitted, but some mandatory parameters are missing, we show a warning message
} elseif (empty($_POST["server_name"]) or !isset($_POST["mod_name"]) or empty($_POST["clan_name"]) or empty($_POST["email"]) or empty($_POST["g_password"]) or empty($_POST["start_date"]) or !isset($_POST["start_slot"]) or empty($_POST["duration"]) or !isset($_POST["timezone"])) {
	// Check user's inputs
	if (empty($_POST["server_name"])) { $msgerror .= "<br />You need to select a server to book."; }
	if (!isset($_POST["mod_name"])) { $msgerror .= "<br />You need to select a mod to load."; }
	if (empty($_POST["clan_name"])) { $msgerror .= "<br />You must type the name of your clan."; }
	if (empty($_POST["email"])) { $msgerror .= "<br />You need to enter a valid email to book in."; }
	if (empty($_POST["g_password"])) { $msgerror .= "<br />You need to enter a password to book privately the server."; }
	if (empty($_POST["start_date"])) { $msgerror .= "<br />You need to select a date to book the server."; }
	if (!isset($_POST["start_slot"]) or $_POST["start_slot"] < 0) { $msgerror .= "<br />You need to select a time to book the server."; }
	if (empty($_POST["duration"])) { $msgerror .= "<br />You need to select a valid duration to book the server."; }
	if (!isset($_POST["timezone"])) { $msgerror .= "<br />You need to select a valid timezone."; }

	// Drawing the form (the form remembers the user's past inputs for every fields)
	show_form($msgerror);

// At last, if everything was filled in, we try to book the slot
} else {

	//---- Check user's authentication (if registration moderation is enabled)
	$unlimited = false;
	if (strtolower($user_moderation) == 'yes' or (strtolower($user_moderation) == 'optional') and !empty($_POST["username"]) and !empty($_POST["userpass"])) { // Check user's auth if either user_moderation is required, or either if user_moderation is optional AND the password field nor username are not both empty
		// If one of the auth fields is empty, we show an error message
		if (empty($_POST["username"]) or empty($_POST["userpass"])) {
			$msgerror .= "<br />Username or password is invalid. You need to be registered to be able to make a booking (and registration are private !). To register, please send your apply to <a href=\"mailto:".$admin_mail."\">".$admin_mail."</a>";
			show_form($msgerror);
		// Else, if the fields are filled
		} else {
			// Fetching the users data
			$userfilename = get_userfilename($jobspath);
			$users = read_userfile($userfilename, $delimiter, $assign);

			// If there are users in the database ...
			if (count($users) > 0) {
				// ... we check if the user submitting the form corresponds to one of our registered users
				$auth = false;
				$unlimited = false;
				if (isset($users[$_POST['username']])) {
					$userdata = $users[$_POST['username']];
					$password = $userdata['password'];
					if ($password == obscure($_POST['userpass'], $password)) {
						$auth = true;
						if ($userdata['unlimited'] == 'yes') $unlimited = true;
					}
				}
				/* DEPRECATED
				foreach ($users as $username => $userdata) {
					$password = $userdata['password'];
					if ($username == $_POST["username"] and $password == obscure($_POST["userpass"], $password)) {
						$auth = true;
						break;
					}
				}
				*/

				// If the user is not recognized, we throw an error, ...
				if (!$auth) {
					$msgerror .= "<br />Username or password is invalid. You need to be registered to be able to make a booking (and registration are private !). To register, please send your apply to <a href=\"mailto:".$admin_mail."\">".$admin_mail."</a>";
					show_form($msgerror);
				}
				// ... else the booking process will happily continue !

			// Else if there are no users in the database, we show an error
			} else {
				$msgerror .= "<br />Username or password is invalid. You need to be registered to be able to make a booking (and registration are private !). To register, please send your apply to <a href=\"mailto:".$admin_mail."\">".$admin_mail."</a>";
				show_form($msgerror);
			}
		}
	} else {
		$_POST["username"] = ''; // If there are not enough fields filled to check the user account, we set username to null so that we won't save it in the weekfile (no identity theft)
	}

	//---- Set timezone (we shift the booking time and date corresponding to the selected timezone)
	// Note: this must be done before checking user's input because of the date: if we set the timezone after checking user's inputs, the user may be able to book at an invalide date (before today or after the maxstartdate)

	// Reconvert timezone slots to the system's slots settings (so that we can simply substract the timezone to the booking start_slot, effectively resulting in a shift for the start_slot)
	//$timezone = convert_slots_to_time($_POST["timezone"], 30, ':'); // reconvert timezone slots to time
	//$timezone = convert_time_to_slots($timezone, $minutes_per_slot, ':'); // reconvert timezone time to slots (with system's slots settings)
	$timezone = convert_time_to_slots($_POST["timezone"], $minutes_per_slot, ':'); // reconvert timezone time to slots (with system's slots settings)

	$shifted_start_slot = $_POST["start_slot"] - $timezone;
	$start_date = str_replace('-', '', $_POST["start_date"]);

	// If by shifting the start_slot, we are out of range, we shift the start_date too (else the shift is ok because it's in the same day)
	while ($shifted_start_slot < 0 or $shifted_start_slot >= $nbslots) { // we loop until the new start_slot is under a valid range (normally a timezone can't be greater than a day of difference, but we take care of this case nevertheless)
		// if timezone is positive, we shift to the next day (increase the date and decrease the start_slot)
		if ($shifted_start_slot >= $nbslots) {
			$shifted_start_slot = $shifted_start_slot - $nbslots;
			$start_date = date('Ymd', strtotime($start_date." + 1 day"));
		// if timezone is negative, we shift to the previous day (decrease the date and increase the start_slot)
		} elseif ($shifted_start_slot < 0) {
			$shifted_start_slot = $shifted_start_slot + $nbslots;
			$start_date = date('Ymd', strtotime($start_date." - 1 day"));
		}
	}
	//$_POST["start_slot"] = $shifted_start_slot;
	$start_date = date('Y-m-d', strtotime($start_date));

	//---- End of set timezone

	//---- Check user's inputs

	// Checking validity of the mail address (basic syntax check for the moment)
	if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { $msgerror .= "<br />You need to enter a valid email to receive a confirmation and a delete code."; }
	// Verifying the date (must be at least tomorrow, because the bash script only check the new bookings each day, and under the maxstartdate to avoid abuse and too much ahead bookings that will be forgotten)
	$tomorrow = date("Ymd", time()+86400);
	$start_date_check = str_replace('-', '', $start_date); // to compare dates, we need to remove the - character so that we compare only numbers
	$max_start_date = date("Ymd", strtotime($maxstartdate)); // limit for booking from today (if this was set to infinity, users could abuse the system and book all the dates and times using a bot, and legit users are prone to forget their very long to come assignments, so it's better to set a reasonable limit like 2 weeks from today)
	if ($start_date_check < $tomorrow or $start_date_check > $max_start_date) { $msgerror .= "<br />You must select a VALID date (at least tomorrow and under ".$maxstartdate.") to book the server, you cannot book it today or in the past!"; }
	// Checking duration
	if ($_POST["duration"] < $minduration) { $msgerror .= "<br />You must select a valid duration, you cannot book for such a short duration. Please select a greater duration."; }
	if ($_POST["duration"] > $maxduration) { $msgerror .= "<br />You must select a valid duration, you cannot book for such a long duration. Please select a shorter duration."; }
	// Checking time
	if (!isset($_POST["start_slot"]) or $_POST["start_slot"] < 0) { $msgerror .= "<br />You need to select a time to book the server."; }
	// Checking the length of the server's password
	if (strlen($_POST["g_password"]) > $maxpasswordsize) { $msgerror .= "<br />The password is too long, please enter a password under ".$maxpasswordsize." characters."; }
	// Checking the server name (user can hack the POST arguments and send anything !)
	if (!array_key_exists($_POST["server_name"], $servers)) { $msgerror .= "<br />The selected server is not available for booking (stop trying to abuse the form!)."; }
	// Checking the mod name (user can still hack the POST arguments !)
	if (!array_key_exists($_POST["mod_name"], $mods)) { $msgerror .= "<br />The selected mod is not available (stop trying to abuse the form!)."; }
	// Checking the type (not very critical but still)
	if (!array_key_exists($_POST["booking_type"], $types)) { $msgerror .= "<br />The selected type does not exists."; }
	// Checking that text fields do no contain unauthorized characters (the ones used to delimit or assign fields in the slots file)
	if ($_POST["g_password"] != str_replace(array($delimiter, $assign), '', $_POST["g_password"] )) { $msgerror .= "<br />The password contains unauthorized characters '".$delimiter.$assign."'"; }
	if ($_POST["clan_name"] != str_replace(array($delimiter, $assign), '', $_POST["clan_name"] )) { $msgerror .= "<br />The clan name contains unauthorized characters '".$delimiter.$assign."'"; }
	if ($_POST["email"] != str_replace(array($delimiter, $assign), '', $_POST["email"] )) { $msgerror .= "<br />Your email contains unauthorized characters '".$delimiter.$assign."'"; }
	// Check if the timezone is in the right range (not necessary because players can't book outside the $max_start_date since it's checked after the timezone is applied to the date and time, but anyway it's a good practice)
	if ($_POST['timezone'] < $timezone_lower_limit or $_POST['timezone'] > $timezone_upper_limit) $msgerror .= "<br />The timezone you selected is invalid. Please select a timezone that is in a legit range.";
	// Normalize clan_name :
	if ( function_exists('normalizer_normalize') ) {
		$_POST["clan_name"] = normalizer_normalize($_POST["clan_name"], Normalizer::FORM_KD);
	}
	// Normalize yes/no select fields :
	if ($_POST['gtv_enabled'] == strtolower('no')) $_POST['gtv_enabled'] = 'no'; else $_POST['gtv_enabled'] = 'yes';
	if ($_POST['show_public'] == strtolower('no')) $_POST['show_public'] = 'no'; else $_POST['show_public'] = 'yes';
	//---- End of check


	// If there is at least one misfilled field, we go back to the form with an error message
	if (!empty($msgerror)) {
		show_form($msgerror);

	// Else, everything is filled ok and we proceed to the booking
	} else {

		// Generate a referee password (this could be done later, but it's better here so that we don't mix up with the checking process)
		$_POST['ref_password'] = generate_human_password($pwdwordslist, true);

		// Check for mail (send mail and wait for reply with a valid code)
		if (!$nomailcheck) {
			// take a given email address and split it into the  username and domain.
			list($userName, $mailDomain) = explode("@", $_POST['email']);
			if (!checkdnsrr($mailDomain, "MX")) { //checkdnsrr available for windows since php v5.3.0
				$msgerror .= "<br />You need to enter a valid email to receive a confirmation and a delete code (email was checked against the server and it does not exists!).";
				show_form($msgerror);
			}
		}


		/**** WEEK FILE - WEEK'S BOOKING LIMITS CHECKING ****/
		// Here we will check if the clan has some slots left for booking this week or if it already spent everything and must wait for the next week
		// Note : this system can be easily adapted to limit slots registration per week, but a user could book too far in time and not attend to the booked time, or one could want to change the booking when the date and time comes nearer, so there would need to be a way to edit a booking

		//---- Creating the week's booking limit file if non-existant
		$weekfilename = get_weekfilename($jobspath, $_POST['server_name'], $start_date); // Week file is a registration file containing quick glance informations on who booked what during the week, and is used to limit abuse
		// If the week file is non-existent for the current week, then we create it (just an empty file)
		if (!file_exists($weekfilename)) {
			create_weekfile($weekfilename);
		}

		//---- Checking week's booking limits
		$slots_per_clan_week = 0;
		$weekevents = read_weekfile($weekfilename, $delimiter, $assign);

		// $compare contains the fields to compare for similarity
		$compare = array(
							'clan' => $_POST['clan_name'],
							'email' => $_POST['email'],
							'realip' => $_SERVER['REMOTE_ADDR'],
		);

		$similarevents = similar_events($weekevents, $compare, $clan_moderation);

		// For each similar event to the current one waiting to be booked, we add the slots count
		foreach ($similarevents as $event) {
			$slots_per_clan_week += $event['slots'];
		}

		// Checking number of slots left :

		// if unlimited users are enabled and the current user is unlimited, then we pass the check
		if (!$unlimited) { // else if user is not unlimited, we check the slots
			// If clearly above - no slots left - we return an error
			if ($slots_per_clan_week >= $maxduration_per_week) {
				$msgerror .= "<br />CRITICAL: You already have booked all your slots for the week! Please wait until next week before booking again.";

				show_form($msgerror);
			// If some slots are left but less than wanted, we notify the user
			} elseif ($slots_per_clan_week + $_POST['duration'] > $maxduration_per_week) {
				$slots_left = $maxduration_per_week - $slots_per_clan_week;
				$time_left = convert_slots_to_time($slots_left, $minutes_per_slot, 'h', true);
				$msgerror .= "<br />You already booked too many slots! Please set a lower duration, you have <strong>$time_left</strong> remaining this week or you can wait next week.";

				show_form($msgerror);
			}
		}

		// Checking captcha (if enabled)
		if ($enable_captcha) {
			require_once('recaptchalib.php');
			$privatekey = $recaptcha_private_key;
			$resp = recaptcha_check_answer ($privatekey,
						      $_SERVER["REMOTE_ADDR"],
						      $_POST["recaptcha_challenge_field"],
						      $_POST["recaptcha_response_field"]);

			if (!$resp->is_valid) { // Captcha is invalid
				// What happens when the CAPTCHA was entered incorrectly
				$msgerror .= "<br />The reCAPTCHA wasn't entered correctly. Please try again. (reCAPTCHA said: " . $resp->error . ")";
				show_form($msgerror);
			} // else the captcha is valid and we continue the booking process
		}

		// Else, enough slots are left for this clan and we can proceed onto the booking (and we register these slots in the week's file) - but BEFORE, we have to check for the slots possible collision


		/**** SERVER JOBS/SLOTS FILE CHECKING****/
		// We make the slots file that will include the command to be executed by the SERVER JOB shell script (serverside)

		//---- Loading the slots data (slots filename depends on the servername and startdate)
		list($slotsfilename, $slots2filename, $slots, $slots2) = get_slots($nbslots, $jobspath, $delimiter, $assign, $_POST['server_name'], $start_date, $shifted_start_slot, $_POST['duration']);

		//---- Checking the slots for collision
		// We look for each slot of interest for us and we check that there is no collision
		$collision = check_collision($nbslots, $shifted_start_slot, $_POST['duration'], $slots, $slots2);

		// If there is at least one collision, we return an error
		if ($collision) {
			$msgerror .= "<br />This slot is already booked for another clan, please choose another slot or try to set a shorter duration.<br />=> You can <a target=\"_blank\" href=\"booking-calendar.php?server_name=".$_POST['server_name']."&start_date=".$start_date."\">take a look at the bookings calendar</a>";

			show_form($msgerror);
		}


		/**** WEEK FILE SAVING ****/
		//---- Registering week's booking limit
		// We register the new entry in the week file for the current booking (to limit abuses in the future)

		// We store several ips and user's browser data in case of abuse (can be removed)
		$ips = array();
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ips[] = $_SERVER['HTTP_CLIENT_IP']; }
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ips[] = $_SERVER['HTTP_X_FORWARDED_FOR']; }
		if (!empty($_SERVER['HTTP_VIA'])) { $ips[] = $_SERVER['HTTP_VIA']; }
		if (!empty($_SERVER['HTTP_RLNCLIENTIPADDR'])) { $ips[] = $_SERVER['HTTP_RLNCLIENTIPADDR']; }
		if (!empty($_SERVER['HTTP_X_COMING_FROM'])) { $ips[] = $_SERVER['HTTP_X_COMING_FROM']; }
		if (!empty($_SERVER['HTTP_COMING_FROM'])) { $ips[] = $_SERVER['HTTP_COMING_FROM']; }

		// Calculating and formatting the time corresponding to each duration (slots to be reserved)
		$time = convert_slots_to_time($shifted_start_slot, $minutes_per_slot, ':');

		// Safe-inputting the mod name (so that there's no bug inducing characters such as the delimiter and assign)
		$modname = $mods[$_POST['mod_name']]['modname'];
		$modname = str_replace($delimiter, '', $modname);
		$modname = str_replace($assign, '', $modname);

                // HTML purifier to clean up all user's inputs that are going to be printed in the calendar, and avoid XSS

                require_once 'lib/htmlpurifier/HTMLPurifier.auto.php';
                $purifier = new HTMLPurifier();

		// Filling the data array
		$data = array(
                                // REQUIRED DATA
				'clan' => str_replace($delimiter, '', $_POST['clan_name']),
				'slots' => $_POST['duration'],
				'email' => $_POST['email'],
				'password' => $_POST['g_password'],
				'refpassword' => $_POST['ref_password'],
				'realip' => $_SERVER['REMOTE_ADDR'],
				'date' => $start_date,
				'time' => $time,
				'duration' => convert_slots_to_time($_POST['duration'], $minutes_per_slot, 'h', true),
				'modname' => $modname,
				'modindex' => $_POST['mod_name'],
				//'mod' => $mods[$_POST['mod_name']]['mod'],
				//'config' => $mods[$_POST['mod_name']]['config'],
				'gtv' => $_POST['gtv_enabled'],
				'type' => $_POST['booking_type'],
				'show_public' => $_POST['show_public'],

                                // ADDITIONAL DATA
                                'title' => $purifier->purify(str_replace($delimiter, '', $_POST['title'])),
                                'website' => $purifier->purify($_POST['website']),
                                'description' => str_replace($delimiter, '', str_replace(array("\r\n", "\r", "\n", "\n\r"), '', htmlspecialchars($purifier->purify($_POST['description'])))), // purify, and also remove the $delimiter from the html input, and remove all line returns

                                // TIMESTAMP (creation date)
				'date_added' => date('Y-m-d H:i'),
			     );

		// Logging mods/oamps parameters - commented out because we only need the modindex in order to retrieve all the mod's parameters, but if you want you can renable this bloc
		//foreach ($mods[$_POST['mod_name']] as $param => $value) {
		//	$data[$param] = $value;
		//}

		// Logging all ips that can be found
		if (!empty($ips)) {
			$data['ip'] =  implode(';;', $ips);
		}

		// Logging user account
		if (isset($_POST['username']) and $_POST['username'] != '') { // If closed system with registration is activated, we log the name of the player who did this booking
			$data['username'] =  $_POST['username'];
		}

		// Logging mail confirmation code (so that the event can be confirmed, this is where the code will be checked, in the weeksfile)
		if (!$nomailcheck) { // If we check registration by mail, we generate an unique confirmation code
			$data['confirmcode'] = getUniqueCode();
			$_POST['confirmcode'] = $data['confirmcode'];
		}

		// Logging delete code (this is where the code will be checked, in the weeksfile)
		$data['deletecode'] = getUniqueCode(); // Generate a unique code for deletion
		$_POST['deletecode'] = $data['deletecode'];

		$weekevents[] = $data; // Append this event's datas into the weekevent array (so that we still have the previous events + the new one, and we finally just save the array by overwriting the weeksfile)
		// Saving in the week file
		if (!save_weekfile($weekfilename, $weekevents, $delimiter, $assign)) {
			// If the saving could not complete, we stop the procedure and throw an error
			$msgerror .= "<br />BUG-CRITICAL: could not save into the weekfile, please <a href=\"mailto:".$admin_mail."\">contact the administrator</a> to fix this bug.";

			show_form($msgerror);
		}

		/**** SERVER JOBS/SLOTS FILE SAVING ****/

		//---- Slots configuration parameters
		if (!$nomailcheck) { // If we check registration by mail, we fill the slots with 'reserved' parameter and nothing else before user confirms his registration with the confirmcode
			$start_data = $through_data = array('reserved' => '');
			$end_data = array('empty' => ''); // the slot after the last slot should remain empty because it is not belonging to the slots of the booking
		} else {
			// Slots parameters for all slots (except the last)
			$through_data = array(
								'password' => $_POST['g_password'],
								'refpassword' => $_POST['ref_password'],
								'clan' => $_POST['clan_name'],
								//'mod' => $mods[$_POST['mod_name']]['mod'],
								//'config' => $mods[$_POST['mod_name']]['config'],
								'gtv' => $_POST['gtv_enabled'],
								'show_public' => $_POST['show_public'],
							      );
			// Logging mods/oamps parameters
			foreach ($mods[$_POST['mod_name']] as $param => $value) {
				# we copy every parameters for the selected mod, except the name (which is not needed for game rotator and can cause bugs because of special characters like spaces or semicolons)
				if ($param != 'modname') $through_data[$param] = $value;
			}
			// First slot parameters (will be added to the $through_data parameters)
			$start_data = array(
							'restart_hard' => '',
							);
			// Last slot parameters ($though_data will NOT be added, because the last slot is the one that happens after the booking, to reinit the server or start another booking - this app will take care of events collisions)
			$end_data = array('restart_soft' => '',
							);
		}

		//---- Configuring the slots
		list($slots, $slots2) = configure_slots($nbslots, $shifted_start_slot, $_POST['duration'], $start_data, $end_data, $through_data, $slots, $slots2); // list() = assign multiple variables from a function (ref to the php manual)

		//---- Saving the slots
		$saveok = true;
		$saveok2 = true;

		$saveok = save_slotsfile($slotsfilename, $nbslots, $slots, $delimiter, $assign);

		if ($shifted_start_slot+$_POST['duration'] >= $nbslots) {
			$saveok2 = save_slotsfile($slots2filename, $nbslots, $slots2, $delimiter, $assign);
		}

		if (!$saveok or !$saveok2) {
			// If the saving could not complete, we stop the procedure and throw an error
			$msgerror .= "<br />BUG-CRITICAL: could not save into the slotfile, please <a href=\"mailto:".$admin_mail."\">contact the administrator</a> to fix this bug.";

			show_form($msgerror);
		} else {
			// If we don't require a mail confirmation to book, we accept the booking right now
			if ($nomailcheck) {
				show_ok($_POST['server_name'], $mods[$_POST['mod_name']]['modname'], $_POST['g_password'], $_POST['ref_password'], $_POST['clan_name'], $start_date, $shifted_start_slot, $_POST['duration'], $minutes_per_slot, $_POST['deletecode']);
			// Else, we require a mail confirmation, so we send the mail
			} else {
				$mailto = $_POST['email'];
				$mailfrom = $admin_mail;
				$mailsubject = 'Booking confirmation for '.$servers[$_POST['server_name']];

				$mailheaders = "MIME-Version: 1.0\r\n";
				$mailheaders .= "Content-type: text/html; charset=iso-8859-1\r\n";
				$mailheaders .= "To: $mailto <$mailto>\r\n";
				$mailheaders .= "From: $admin_name <$mailfrom>\r\n";

				// Defining/Reformatting variables
				$_POST['time'] = date('H:i', strtotime(convert_slots_to_time($shifted_start_slot, $minutes_per_slot, ':'))); // Reformatting start_time (adding padding 0)
				$duration = convert_duration_to_seconds(convert_slots_to_time($_POST['duration'],$minutes_per_slot, 'h')); // Converting the duration to seconds to...
				$_POST['endtime'] = date("H:i", strtotime($_POST['time']) + $duration); // ...calculate the end_time
				$_POST['mod_fullname'] = $mods[$_POST['mod_name']]['modname']; // Getting the text name of the mod
				if (array_key_exists($_POST['booking_type'],$types)) { // If the supplied booking type is standard (which should be the case if registered via the form), we get the text full name of this type, else we output it as it is (this leaves space to add custom types)
				    $_POST['booking_type'] = $types[$_POST['booking_type']]; // Getting the text name of the type
				}
				if (array_key_exists($_POST['server_name'],$servers)) { // If the supplied server is standard
				    $_POST['server_fullname'] = $servers[$_POST['server_name']]; // Getting the text name of the server
				}

				// Total slots left for the week
				if ($unlimited) { // if this is an unlimited account, then the user has an infinity of time_left
					$_POST['time_left'] = 'an <strong>unlimited</strong> amount of time';
				} else {
					$slots_left = $maxduration_per_week - $slots_per_clan_week - $_POST['duration'];
					$time_left = convert_slots_to_time($slots_left, $minutes_per_slot, 'h', true);
					$_POST['time_left'] = $time_left;
				}

				// Substituting the variables in the mail message template
				$mailmessage = strtr($mailmessage_template, array_prefixkeys($_POST,'$'));

				// If the mail could not be send, we print the confirmation link directly on-screen
				if (!mail($mailto, $mailsubject, $mailmessage, $mailheaders)) {
					show_error('The confirmation mail could not be send ! Please click on this <a href="?server_name='.$_POST['server_name'].'&start_date='.$start_date.'&confirmcode='.$_POST['confirmcode'].'">link to confirm your booking</a> and please write down this delete code if you ever want to cancel your booking that you can enter on the booking-cancel.php form: '.$_POST['deletecode'].'
						   <br /><br />Here is a copy of the mail so that you can save it for later needs:<br /><br />'.$mailmessage.'
						   ');
				// Else, if the mail was sent, we show a notification message
				} else {
					show_msg('A confirmation mail was sent to <strong>'.$_POST['email'].'</strong> - please check your inbox and click on the link inside this email to confirm your event. Please note that until then, your event is NOT booked and will NOT happen until you confirm it.');
				}

			}
		}
	}
}
?>
