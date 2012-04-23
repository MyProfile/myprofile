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

define('INCLUDE_CHECK',true);
require 'lib/graphite.php';
require 'lib/arc/ARC2.php';
require 'config.php';

$ret = "";

// process form and send pingback
if (isset($_POST['source'])) {

    $from   = mysql_real_escape_string(trim($_REQUEST['source']));
    $to     = mysql_real_escape_string(trim($_REQUEST['target']));
    $msg    = mysql_real_escape_string($_REQUEST['comment']);

    // fetch the user's profile
    $fg = new Graphite();
    $fg->load($from);
    $fr = $fg->resource($from);

    $ok = 0;
    // only send pings if the destination person is friends with the sender (avoids spam)
    foreach($fr->all("foaf:knows") as $friend) {
        // check if the target is my friend or not       
        if ($friend == $to) {
            // write webid uri to database
            $query = "INSERT INTO pingback_messages SET date='" . time() . "', from_uri = '" . $from . "', to_uri = '" . $to . "'";
            if (isset($_REQUEST['comment']))
                $query .= ", msg = ' " . $msg . "'";
            $result = mysql_query($query);
            if (!$result) {
                $ret  = 'Invalid query: ' . mysql_error() . "\n";
                $ret .= 'Query: ' . $query;
            } else {
                $ret .= header("HTTP/1.1 201 Created");
                $ret .= header("Status: 201 Created");
                $ret .= "<html><body>\n";
                $ret .= "Your notification has been successfully delivered!\n";
                $ret .= "</body></html>\n";
            }
            // everything is ok, exit loop
            $ok = 1;
            break;
        }
    }
    // return a proper HTTP response code with error
    if ($ok == 0) {
        $ret .= header("HTTP/1.1 404 Not Found");
        $ret .= header("Status: 404 Not Found");
        $ret .= "<html><body>\n";
        $ret .= "Could not find any mention of <strong>" . $to . "</strong> in your list of known people.\n";
        $ret .= "</body></html>\n";
    }
} else {
    // show form
    $ret .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
    $ret .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"  xmlns:pingback=\"http://purl.org/net/pingback/\">\n";
    $ret .= "   <head>\n";
    $ret .= "	<title>Pingback</title>\n";
    $ret .= "	<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />\n";
    $ret .= "   </head>\n";
    $ret .= "   <body typeof=\"pingback:Container\">\n";
    $ret .= "   <form method=\"post\" action=\"http://fcns.eu/people/andrei/pingback.php\">\n";
    $ret .= "       <p>Your WebID: <input size=\"30\" property=\"pingback:source\" type=\"text\" name=\"source\" /></p>\n";
    $ret .= "       <p>Target WebID: <input size=\"30\" property=\"pingback:target\" type=\"text\" name=\"target\" value=\"" . $_GET['target'] . "\" /></p>\n";
    $ret .= "       <p>Comment (optional): <input size=\"30\" maxlength=\"256\" type=\"text\" name=\"comment\" style=\"background-color:#fff; border:dashed 1px grey;\" /></p>\n";
    $ret .= "       <p><input type=\"submit\" name=\"submit\" value=\"Ping!\" /></p>\n";
    $ret .= "   </form>\n";
    $ret .= "   </body>\n";
    $ret .= "</html>\n";
}

echo $ret;

?>		      

