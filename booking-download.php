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
 * \description     Download script: access this script remotely to securely download the slots file, to then manage game servers.
 * \usage    booking-download.php?server_name="server"&start_date="date"&password="password"
 */

require_once('config.php');
require_once('lib.php');

if (!empty($_GET['password']) and !empty($_GET['server_name']) and !empty($_GET['start_date'])) {
    if (md5($_GET['password']) == $dlhash) {
        $slotsfilepath = get_slotsfilename($jobspath, $_GET['server_name'], $_GET['start_date']);
        $slotsfilename = get_slotsfilename('', $_GET['server_name'], $_GET['start_date']);
        if (file_exists($slotsfilepath) and !file_is_empty($slotsfilepath)) {
            header('Content-Description: File Transfer');
            header('Content-type: application/txt');
            header('Content-Disposition: attachment; filename="'.$slotsfilename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($slotsfilepath));
            header('Accept-Ranges: bytes');
            //header('X-Sendfile: '.$file);
            include($slotsfilepath);
        }
    }
}
?>
