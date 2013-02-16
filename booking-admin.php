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
 * \description     Booking administration panel to manage users' accounts and clans closed list
 */

session_start();
require_once('config.php');
require_once('lib.php');


/*******************************/
/********* FUNCTIONS ********/
/*******************************/

/***** PARAMETERS *****/


/***** PRINTING FUNCTIONS AND TEMPLATES *****/

function print_header() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>OA Clan Planner - Admin Panel</title>
    </head>
    <body>
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

function show_form($msgerror = null) {
    print_header();
?>
        <h1>OA Clan Planner - Admin Panel</h1>
        <div class="msgerror"><?php echo $msgerror; ?></div>
        <div>This section is restricted. Please authenticate yourselves.</div>
        <form action="?" method="post" enctype="x-www-form-urlencoded">
            Username: <input type="text" name="username"/><br />
            Password: <input type="password" name="password"/><br />
            <input type="submit" value="Authenticate"/>
        </form>
<?php
    print_footer();
    exit();
}

function show_panel($msgerror = null) {
    include('config.php');
    print_header();
?>
        <h1>OA Clan Planner - Admin Panel</h1>
        <div><a href="?logout=true">Logout</a></div>
        <div class="msgerror"><?php echo $msgerror; ?></div>

        <form action="?" method="post" enctype="x-www-form-urlencoded">

            <h2>Clans</h2>
            <div class="clanlist">
		<?php
		$clanfilename = get_clanfilename($jobspath);
		$clans = read_clanfile($clanfilename);
                foreach ($clans as $clan) {
                    echo $clan.' <a href="?delete_clan=true&clan_name='.$clan.'">delete</a><br />';
                }
		?>
            </div>

            New clan: <input type="text" name="clan_name" id="clan_name"/>
            <input type="submit" name="add_clan" value="Add clan"/>

            <h2>Users</h2>
            <div class="userslist">
		<?php
		$userfilename = get_userfilename($jobspath);
		$users = read_userfile($userfilename, $delimiter, $assign);
                foreach ($users as $username => $userpass) {
                    echo $username.' <a href="?delete_user=true&username='.$username.'">delete</a><br />';
                }
		?>
            </div>

            New user: <input type="text" name="username" id="username"/>
            Email: <input type="text" name="email" id="email"/>
	    Unlimited? <select name="unlimited" id="unlimited"><option name="no">No</option><option name="yes">Yes</option></select>
            <input type="submit" name="add_user" value="Add user"/>
            <br />
            <em>Note: A random password will be generated and sent to the specified mail address.</em>
        </form>
<?php
    print_footer();
    exit();
}




/************************/
/********* MAIN ********/
/************************/

$msgerror = '';

//== USER ALREADY AUTHENTICATED
if (isset($_SESSION['auth'])) {
    // If the session is bugged, we ask to login again
    if ($_SESSION['auth'] != 'yes') {
        show_form($msgerror);
    // Else, the session is ok and the user is really authenticated
    } else {

        // Logout if link is clicked
        if (isset($_REQUEST['logout'])) {
            session_destroy();
            show_form('You have successfully unlogged.');
        }

        //***** PANEL ACTIONS MANAGEMENT *****/

        //---- Adding a clan
        if (isset($_REQUEST['add_clan']) and trim($_REQUEST['clan_name']) != '') {
            // Fetching the clans from the clans file
            $clanfilename = get_clanfilename($jobspath);
	    $clans = read_clanfile($clanfilename);
            // Adding the new clan
            $clans[] = $_REQUEST['clan_name'];
            // Writing the new clans file
            if (!save_clanfile($clanfilename, $clans)) {
                $msgerror .= 'BUG: Could not save the clans file, please check your files access on your webserver.';
                show_panel($msgerror);
            } else {
                show_panel('OK: the clan <strong>'.$_REQUEST['clan_name'].'</strong> has been successfully added to the database.');
            }

        //---- Adding an user
        } elseif (isset($_REQUEST['add_user']) and trim($_REQUEST['username']) != '') {
            // Fetching the users from the users file
            $userfilename = get_userfilename($jobspath);
	    $users = read_userfile($userfilename, $delimiter, $assign);
            // If the user is already in the list, we cannot add it
            if (isset($users[$_REQUEST['username']])) {
                $msgerror = 'ERROR: This username is already in the database, cannot add the same user twice!';
                show_panel($msgerror);
            // Else, this is a new unique user
            } else {
                //---- User creation process
                // Note: the password is normally not known to the administrator, all the process is done in background and the password is directly sent to the user in a mail

                // We generate a human readable password
                $password = generate_human_password($pwdwordslist, true);
		// Unlimited user or not?
		strtolower($_REQUEST['unlimited']) == 'yes' ? $unlimited = 'yes' : $unlimited = 'no';
                // Adding the user to the list of users by adding the password and the unlimited power
                $users[$_REQUEST['username']] = array('password' => obscure($password), 'email' => $_REQUEST['email'], 'unlimited' => $unlimited);
                // Writing the new users file
                if (!save_userfile($userfilename, $users, $delimiter, $assign)) {
                    $msgerror .= 'BUG: Could not save the users file, please check your files access on your webserver.';
                    show_panel($msgerror);
                }
                // If we could write and add the new user, we send him an email

                // Forging the mail
                $mailto = $_REQUEST['email'];
                $mailfrom = $admin_mail;
                $mailsubject = 'Your OpenArena servers booking account';

                $mailheaders = "MIME-Version: 1.0\r\n";
                $mailheaders .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $mailheaders .= "To: $mailto <$mailto>\r\n";
                $mailheaders .= "From: $admin_name <$mailfrom>\r\n";

                $mailmessage = '<h1>Your OpenArena servers booking account</h1>
                <div>Your account has been successfully activated. Here are your informations:
                <div>
                Username: '.$_REQUEST['username'].'<br />
                Password: '.$password.'
                </div>';
		if ($unlimited == 'yes') { // If user is unlimited, then we notify him of his special right
		    $mailmessage .= 'You are now free to book for <strong>an unlimited amount of time</strong> per week the following servers:';
		} else { // Else we show the amount of time the user can book per week
		    $mailmessage .= 'You are now free to book for <strong>'.convert_slots_to_time($maxduration_per_week, $minutes_per_slot, 'h', true).' per week</strong> the following servers:';
		}
                $mailmessage .= '<div>'.implode(", ", $servers).'</div>
                See you soon on our servers!
                </div>';

                // Sending the mail
                if (!mail($mailto, $mailsubject, $mailmessage, $mailheaders)) {
                    // If the mail could not be sent, we show the user's informations on-screen (password included!)
                    $msgerror .= 'ERROR: Could not send the mail to the user! Please find below the informations to transmit to the user (particularly his password):<br /><div style="border: dotted black 1px">'.$mailmessage;
                    show_panel($msgerror);
                } else {
                    show_panel('OK: the user <strong>'.$_REQUEST['username'].'</strong> has been successfully added to the database.');
                }
            }

        //---- Deleting a clan
        } elseif (isset($_REQUEST['delete_clan']) and trim($_REQUEST['clan_name']) != '') {
            // Fetching the clans from clan file
            $clanfilename = get_clanfilename($jobspath);
	    $clans = read_clanfile($clanfilename);

            // If the clan isn't in the list, we cannot remove it
            if (!in_array($_REQUEST['clan_name'], $clans)) {
                $msgerror .= 'ERROR: No clan named <strong>'.$_REQUEST['clan_name'].'</strong> could be found in the database.';
                show_panel($msgerror);
            // Else, the clan is in the list
            } else {
                // Delete the index from the array
                $clanindex = array_search($_REQUEST['clan_name'], $clans);
                unset($clans[$clanindex]);
                // Save the new clan file
                if (!save_clanfile($clanfilename, $clans)) {
                    $msgerror .= 'BUG: Could not save the clans file, please check your files access on your webserver.';
                    show_panel($msgerror);
                } else {
                    show_panel('OK: the clan <strong>'.$_REQUEST['clan_name'].'</strong> has been successfully removed from the database.');
                }
            }

        //---- Deleting an user
        } elseif (isset($_REQUEST['delete_user']) and trim($_REQUEST['username']) != '') {
            // Fetching the users from user file
            $userfilename = get_userfilename($jobspath);
	    $users = read_userfile($userfilename, $delimiter, $assign);

            // If the user isn't in the list, we cannot remove it
            if (!isset($users[$_REQUEST['username']])) {
                $msgerror .= 'ERROR: No user named <strong>'.$_REQUEST['username'].'</strong> could be found in the database.';
                show_panel($msgerror);
            // Else, the user exists
            } else {
                // Delete the index from the array
                unset($users[$_REQUEST['username']]);
                // Save the new users file
                if (!save_userfile($userfilename, $users, $delimiter, $assign)) {
                    $msgerror .= 'BUG: Could not save the users file, please check your files access on your webserver.';
                    show_panel($msgerror);
                } else {
                    show_panel('OK: the user <strong>'.$_REQUEST['username'].'</strong> has been successfully removed from the database.');
                }
            }

        //---- No action (default action)
        } else {
            // We just print out the panel
            show_panel();
        }
    }


//== USER NOT AUTHENTICATED (FIRST TIME)
} else {
    // If there are missing/blank fields or the form is loaded for the first time
    if (empty($_POST['username']) or empty($_POST['password'])) {
        show_form(); // We just print out the login form
    // Else, user is trying to login
    } else {
        //---- Checking the authentication
        if ($_POST["username"] == $admin_username and obscure($_POST["password"], $admin_passwordhash) == $admin_passwordhash) { // If admin login username and password is the same as the ones submitted in the fields
            $_SESSION['auth'] = 'yes'; // User is authenticated
            show_panel(); // We printout the panel
        // Else the login is wrong
        } else {
            $msgerror .= "Username or password is invalid.";
            show_form($msgerror);
        }
    }
}

?>
