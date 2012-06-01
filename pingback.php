<?php
//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : pingback.php
// Date       : 21st Apr 2011
//
// Project name: SemPB - Semantical Pingback
// Copyright 2011 fcns.eu
// Author: Andrei Sambra - andrei@fcns.eu
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// See <http://www.gnu.org/licenses/> for a description of this license.

require 'include.php';

$ret = "";

// Process request and deliver pingback
if (isset($_POST['source']) && (isset($_POST['target']) && (isset($_POST['comment'])) {
    // fetch the user's profile
    $profile = new MyProfile(trim($_POST['source']), $base_uri);
    $profile->load();
    
    // Prepare data to be inserted into the database
    $from   = mysql_real_escape_string(trim($_REQUEST['source']));
    $to     = mysql_real_escape_string(trim($_REQUEST['target']));
    $msg    = mysql_real_escape_string(trim($_REQUEST['comment']));
    $name   = mysql_real_escape_string(trim($profile->get_name()));
    $pic    = mysql_real_escape_string(trim($profile->get_picture()));

    // Return HTTP 400 (bad request)
    if (strlen($_POST['source']) == 0) {
        // No destination user, return a proper HTTP response code with error
        $ret .= header("HTTP/1.1 400 Bad request");
        $ret .= header("Status: 400 Bad request");
        $ret .= "<html><body>\n";
        $ret .= "Bad request: you did not specify the source user.\n";
        $ret .= "</body></html>\n";
    } else if ((!isset($_POST['target'])) && (strlen($_POST['target']) == 0)) {
        // No destination user, return a proper HTTP response code with error
        $ret .= header("HTTP/1.1 400 Bad request");
        $ret .= header("Status: 400 Bad request");
        $ret .= "<html><body>\n";
        $ret .= "Bad request: you did not specify the destination user.\n";
        $ret .= "</body></html>\n";
    } else if ((!isset($_POST['comment'])) && (strlen($_POST['comment']) == 0))let m {
        // No message, return a proper HTTP response code with error
        $ret .= header("HTTP/1.1 400 Bad request");
        $ret .= header("Status: 400 Bad request");
        $ret .= "<html><body>\n";
        $ret .= "Bad request: you did not provide a message.\n";
        $ret .= "</body></html>\n";
    } else {
        // write webid uri to database
        $query = "INSERT INTO pingback_messages SET date='" . time() . "', ";
        $query .= "from_uri = '" . $from . "', ";
        $query .= "to_uri = '" . $to . "', ";
        $query .= "name = '" . $name . "', ";
        $query .= "pic = '" . $pic . "', ";
        $query .= "msg = ' " . $msg . "'";
        
        $result = mysql_query($query);
        
        if (!$result) {
            // Database error, return a proper HTTP response code with error
            $ret .= header("HTTP/1.1 500 Internal Error");
            $ret .= header("Status: 500 Internal Error");
            $ret .= "<html><body>\n";
            $ret .= "Internal error: could not deliver the ping (database error).\n";
            $ret .= "</body></html>\n";
        } else {
            mysql_free_result($result);
            // Everything is OK, return a proper HTTP response success code
            $ret .= header("HTTP/1.1 201 Created");
            $ret .= header("Status: 201 Created");
            $ret .= "<html><body>\n";
            $ret .= "Your message has been successfully delivered!\n";
            $ret .= "</body></html>\n";
        }
    }
} else {
    // Show form
    $ret .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
    $ret .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"  xmlns:pingback=\"http://purl.org/net/pingback/\">\n";
    $ret .= "   <head>\n";
    $ret .= "	<title>Pingback</title>\n";
    $ret .= "	<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />\n";
    $ret .= "   </head>\n";
    $ret .= "   <body typeof=\"pingback:Container\">\n";
    $ret .= "   <form method=\"post\" action=\"pingback.php\">\n";
    $ret .= "       <p>Your WebID: <input size=\"30\" property=\"pingback:source\" type=\"text\" name=\"source\" /></p>\n";
    $ret .= "       <p>Target WebID: <input size=\"30\" property=\"pingback:target\" type=\"text\" name=\"target\" value=\"" . $_GET['target'] . "\" /></p>\n";
    $ret .= "       <p>Comment (optional): <input size=\"30\" maxlength=\"256\" type=\"text\" name=\"comment\" style=\"background-color:#fff; border:dashed 1px grey;\" /></p>\n";
    $ret .= "       <p><input type=\"submit\" name=\"submit\" value=\"Ping!\" /></p>\n";
    $ret .= "   </form>\n";
    $ret .= "   </body>\n";
    $ret .= "</html>\n";
}
// Display
echo $ret;

?>		      

