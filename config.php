<?php
$servers = array(
                                "clantrain" => "Community ClanTrain",
                                "clanwar" => "Community ClanWar"
				);
$mods = array( // Features: If you use the oa-game-rotator.py companion script, you can set in the $mods[] array any oamps parameters, so that you can set specific parameters per mod such as vm, homepath, basepath, etc. WARNING: the parameters must be the longform (so no -h but --homepath).
                                array("modname" => "AfterShock v?? Instagib", "gamemod" => "as", "config" => "as-instagib.cfg"),
                                array("modname" => "ExcessivePlus v2.1 RocketsOnly", "gamemod" => "excessiveplus", "config" => "e-rocketonly.cfg", "vm" => "0"),
                                array("modname" => "OpenArena v0.8.1 CTF", "gamemod" => "oa-081", "config" => "oa-ctf.cfg"),
                                array("modname" => "OpenArena v0.8.5 CTF", "gamemod" => "oa-085", "config" => "oa-ctf.cfg"),
);
$types = array(
                                "training" => "Training",
                                "match" => "Match",
                                "league" => "League",
);
$maxpasswordsize = 32; // Max password size for the server's password (if it's too long there max be a glitch and a server crash because of the limits of the game engine, particularly with quake 3 vanilla)

$nbslots = 48; // Number of subdivisions (slots) in a day.
$maxduration = 8; // Maximum duration (in slots) of one booking
$maxduration_per_week = 8; // Maximum number of slots booking per week
$maxduration_blocks = 2; // Size of the package for booking, in slots (ex if $maxduration_blocks = 2, it means that each choice will contain a multiple of 2 : 2 slots, 4 slots, 6 slots, 8 slots, etc... It avoids to have meaningless bookings such as 15 minutes).
$minduration = 0; // Minimum duration of the booking, in slots. Under this minimum, no booking will be taken.
$maxstartdate = '+2 weeks'; // Maximum date to book (you cannot book farther than this time ahead !) -> for format, ref to php date format: http://www.php.net/manual/en/datetime.formats.relative.php

$minutes_per_slot = 1440/$nbslots; // Minutes per slot (don't touch this!).

$nomailcheck = false; // A booking needs to be confirmed by mail before being confirmed ?
$user_moderation = 'optional'; // 'yes', 'no', 'optional' - If enabled, the registration moderation will permit only registered users and clans (by you, via booking-admin.php) to book the servers. If disabled, anyone can book the servers (some security measures takes place then to prevent abuses as much as possible, such as clan name comparison, ip address and email address checks).
$clan_moderation = false; // true, false - If enabled, only clans from the list (made by the admin via booking-admin.php) can book the server (this should be enabled if $user_moderation is enabled)
$enable_unlimited_users = true;

$enable_captcha = false;
$recaptcha_public_key = '6LdUhMsSAAAAAM8_K2TPCePopj56tz4gVWeSaTqX'; // Get a free public and private key at https://www.google.com/recaptcha/admin/create
$recaptcha_private_key = '6LdUhMsSAAAAAMj0zwoJonEjuMY6L3dTgq7NbzCW';

$delimiter = '|'; // Delimiter between fields in the week and slots files. Do NOT modify this unless you know what you are doing !
$assign = '='; // Assign sign betweent params and values in week and slots files. Do NOT modify this unless you know what you are doing !
$jobspath = './jobs/'; // Path to the .txt files (clans.txt, users.txt, slots files, weeks files, etc..). All the files will be created and searched in this folder. SECURITY NOTE: these files must NOT be accessible from public, only the scripts must be able to access them. To remotely access the slots file, the game rotator script can do so by using the booking-download.php script with a secret code (see $dlhash below).

$timezone_lower_limit = -24; // Limits for timezone (each unit represents a half hour), so that you can easily change the limits of timezones (should not be required in practice)
$timezone_upper_limit = 27;

$websitename = 'OA-Community.com Servers';
$websitelink = 'http://www.oa-community.com/';

$admin_name = 'Community Admin'; // Your admin name
$admin_mail = 'none@none.org'; // Your admin mail (in case of bugs)
$admin_username = 'admin'; // Your username (to register users and clans) - used in booking-admin.php
$admin_passwordhash = '20509cfff71ab964ae17154b65bf2f36de740f5a8e54ec2e88edc6a17389cf720a5b7d54d7f2334b80260382989117407ea968f01d971117b32c3351955d973f'; // Your password's hash (NOT the password!) - used in booking-admin.php
// Note: to generate a new password hash, use the booking-quick-hash-generator.php and copy/paste the Obscure hash here in $admin_passwordhash.
// default: $admin_username = $admin_password = 'admin'

$dlhash = '5f4dcc3b5aa765d61d8327deb882cf99'; // Slots download password hash: MD5 hash of the password required to access to booking-download.php (which permits to fetch slots files) - use the provided quick-md5-generator.php or your own but do NOT use an online generator ! (they may store your hash)
// default $dlhash = 'password'

// Words list used to generate human readable passwords for users when $user_moderation is enabled and accounts are created using booking-admin.php
// Note: for the worlist for the g_password generator, see password-gen.js
$pwdwordslist = array("cane","gain","leaf","lean",
"lech","book","prim","rose","roto","rote","sift","side",
"spur","time","tint","tire","mess","will","wave","weak",
"year","yard","wick","wild","view","lock","tent","sold",
"slab","sent","tiptoe","tiptoed","timetable","timetabled","timetables","timid","last","flame","water","earth","falcon","lightning","thunder","thunderous","furious","one","two","three","four","five","six","seven","eight","nine","ten","effigy","crowned","crab","traveller","of","baldy","biomorphic","myths","past","soul","snipe","song","music","god","deusexmachina","leilol","sago", "fluffy"
                                            );
?>
