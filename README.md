*****************************************************
*	        	 Game Booking Manager            	*
*					Version: 1.4.0					*
*			First publication: 2011/12/29			*
*			Last modification: 2013/02/10			*
*			by Stephen Larroque alias GrosBedo		*
*****************************************************

Game Booking Manager is a PHP web application to manage events and interactively/automatically book a server (similar to QuakeLive server creation form).

LICENCE
-------

This application is licensed under Affero GPL v3 or above (AGPLv3+). All the files included are following this license, unless specified otherwise in the headers of the file (for third-party libraries such as recaptcha).
You can find a copy of the license terms inside the file LICENSE.txt or at http://www.gnu.org/licenses/agpl.html

PURPOSE
-------

This application was made with the idea of providing a simple yet effective GUI interface for users to book a game server.

It works on two levels:
* Slots files, which saves the data necessary to manage the game server. These files should be remotely downloaded by a game server manager script (like the oa-game-rotator.py script).
* Weeks files, which saves all the datas related to the booking. These are not needed to manage the game server, but only as a record to manage the GUI (it is used to confirm, cancel the bookings and to show them on the calendar).

The scripts provided are made with PHP5, with no database requirement, and should work on any webhost (because it needs no particular command that may be limited in shared webhostings).

The scripts are very modular and configurable, with lots of comments, having in mind a high reusability and a potential translation of application for other games than OpenArena (or even other engines than ioquake3).

Everything was made with the user and admin ease in mind: users are accompanied with lots of messages and advices and even a preview, with automatic user's timezone detection, and admins get a lot of ways to moderate the bookings and potential abuses, and user's accounts creation is a breeze with automatic password generation and accounts informations are automatically sent by email.

FEATURES
--------

- Servers administration features:
Automatically generate and manage the following parameters:
* Date, time and duration of a booking
* Server private password
* Referee password
* Any mod can be supported
* Any config can be supported
* GTV support
* Booking history: clan, email, client ip, date when the booking was made, accounts
* Private booking: no info shown to the public
* Automatic hard restart (kill+restart server) or soft restart (map_restart) depending on the context
* Special accounts

- User's GUI features:
* Human-readable password generator: is provided to generate secure but easily memorizable server's password (g_password) and user's account's password.
* Integrated public events calendar: to show all the bookings and their informations (useful for spectators and events organizators).
* Timezone selector: for user to quickly and easierly manage bookings.
* Autodetect client's timezone.
* Accurate AJAX preview of the booking informations while the user is choosing.
* Javascript calendar to easily select the date (design can easily be configured in the script).
* User can choose to enable GTV or not (spectators), and even to show the event publicly in the agenda or hide it (only the date and hour will be shown, but no other info).
* Can cancel a booking anytime (though only if not happening in the past or today).

- Moderation features:
* Automatic regularization of users (based on their IP, username, clan name, captcha, or closed registration, etc...) even when enabling open registration (no moderation).
* Optional ReCaptcha for booking submission.
* Open registration (no registration required to book) or closed registration (you can create user's accounts).
* Unlimited user's accounts can be created (users that can book any number of slots they want anytime, useful for leagues' organizators). These users can use their rights even in open registration mode (set $user_moderation = "optional").
* Open list (free text input) or closed list of clan names (so that only those clans leaders can book).
* Logging facilities to track abuses (usernames and clans are logged, as well as the date and time when the booking was registered).
* Automatic email MX validation to check if that's a valid email.

- Admin GUI:
* Admin web panel to manage users and clans when closed registration is enabled, with automatic user's password generation and email sending with all infos.
* Fully configurable gamemods and gametypes available.
* Fully configurable parameters for booking (duration, max date for booking, number of slots per day, etc...).
* Many other internal parameters that you can configure directly from the config file.
* Wrapper script to remotely download files using a crypted password hash (so that you can completely secure the jobs files with an .htaccess or unix rights management).

STRUCTURE
---------

LICENSE.txt -- License file.
README.txt -- This file.
TODO.txt -- ToDo list of functionnalities and bugfixes to implement.
booking-admin.php -- Admin panel to manage users' accounts and clans closed list
booking-calendar.php -- Public calendar of events
booking-cancel.php -- Cancelling form for bookings
booking-download.php -- Download script (no GUI, use it by accessing the URL directly. eg: booking-download.php?server_name="server"&start_date="date"&password="password")
booking-form.php -- Booking registration form
booking-preview.js -- AJAX script for preview of the booking in booking-form.php
booking-quick-hash-generator.php -- Additionnal script to help admins to generate a good hash for the admin and remote download password (since the hash is not based on MD5 or SHA1, you should use this script to generate your hash)
calendarDateInput.js -- JS Calendar to pick booking dates
config.php -- Config file (for the whole application)
jobs/ -- Contains the generated slots/weeks/users/clans files
lib.js -- Library of client's interactions functions (eg: autodetect timezone)
lib.php -- Library of critically necessary functions for managing bookings
password-gen.js -- Human-readable password generator for server's password
recaptchalib.php -- Google's ReCaptcha library (you should update it to the latest version)
*.gif *.jpg *.png -- Images, not necessary, can be changed

MECHANISM
---------

The booking of a server has been splitted in two applications:

1- Registration and management of the user's bookings. This application should be accessible publicly.
2- Applying the bookings to the game servers. This application should be private and unaccessible at all to the public (for security measures).

The application of such a strategy can be concretely applied following these steps:
1- This application generates and manages valid slots files.
2- Remotely download these slots files using the download script.
3- Use a Game Rotator script server-side, on the server that hosts the games servers, that can parse the slots files (pretty easy: first number is the number of slots, all the others are actual slots), and launch commands.
4- Optional: in our case, the OA Game Rotator script actually uses another script that manages servers, called oamps.sh. This is an intermediary step that is not necessary if you implement your own Game Rotator.

FAQ
---
- Why use a file system to store the database instead of MySQL/PostGreSQL/other?

Simply for technical and security reasons : it is far more easy to fetch a file than to connect to a remote database server, and not all hosts (particularly on shared hosting) permits the remote connection to the database.

Secondly, opening your database remotely expose it to security breachs and attacks, and you probably will store much more important and confidential informations in your database than the slots rotation datas of a game server.

For all these reasons, we decided to choose to store the datas in files, but it may be ported to a sql version if the need is expressed.

- What is a slot?

A slot is a discrete interval of time. This was made because it would be pretty difficult to manage bookings with a continuous time, particularly without using a database system, so we decided to discretize the states of the system.

Concretely, if you set the number of slots to 24, you will have 24 slots per day, each slot during 1 hour. If you have 48 slots, you get 30 minutes per slot.

Higher the number of slots, more granular and precise the durations will be.

- I have special needs. Can I add special parameters?

Yes you can in the $mods array in the config file: every parameter will be added in the slotsfile depending on if this mod is choosen.

For example, if you add "vm" => "0" it will add vm=0 in the slotsfile. If you use the companion script oa-game-rotator.py, it will automatically be sent as a commandline argument to oamps.sh.

This way, you can add special arguments for your needs, for example the homepath, basepath, exec of special parameters, etc...

CONTACT
-------

You can contact the author at the following email address:

<grosbedo at gmail dot com>
