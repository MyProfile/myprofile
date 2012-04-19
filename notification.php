<?php
/*
 *  Copyright (C) 2012 MyProfile Project
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is furnished 
 *  to do so, subject to the following conditions:

 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.

 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
require_once 'include.php';

$ret = "";

// process form and send pingback
if (isset($_POST['source'])) {

    $from   = mysql_real_escape_string(trim(urldecode($_REQUEST['source'])));
    $to     = mysql_real_escape_string(trim(urldecode($_REQUEST['target'])));
    $msg    = mysql_real_escape_string($_REQUEST['comment']);

    // fetch the user's profile
    $graph = new Graphite();
    $graph->load($from);
    $profile = $graph->resource($from);

    $user = $profile->get('foaf:name');
    
    if ($user == '[NULL]')
        $user = 'Anonymous';
    if ($profile->get('foaf:img') != '[NULL]')
        $img = $profile->get('foaf:img');
    else if ($profile->get('foaf:depiction') != '[NULL]')
        $img = $profile->get('foaf:depiction');

    $user = mysql_real_escape_string($user);
    $img = mysql_real_escape_string($img);

    $ok = 0;
        
    // write webid uri to database
    $query = "INSERT INTO pingback_messages SET date='" . time() . "', from_uri = '" . $from . "', to_uri = '" . $to . "',  name='" . $user . "', pic='" . $img . "'";
    if (isset($_REQUEST['comment']))
        $query .= ", msg = ' " . $msg . "'";
    $result = mysql_query($query);
    if (!$result) {
        $ret .= 'Database error!';
    } else {
        $ret .= header("HTTP/1.1 201 Created");
        $ret .= header("Status: 201 Created");
        $ret .= "<html><body>\n";
        $ret .= "<font color=\"green\" style=\"font-size: 1.3em;\">Your notification has been successfully delivered!</font>\n";
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
    $ret .= "   <form method=\"post\" action=\"\">\n";
    $ret .= "       <p>Your WebID: <input size=\"30\" property=\"pingback:source\" type=\"text\" name=\"source\" /></p>\n";
    $ret .= "       <p>Target WebID: <input size=\"30\" property=\"pingback:target\" type=\"text\" name=\"target\" value=\"" . $_REQUEST['target'] . "\" /></p>\n";
    $ret .= "       <p>Comment (optional): <input size=\"30\" maxlength=\"256\" type=\"text\" name=\"comment\" style=\"background-color:#fff; border:dashed 1px grey;\" /></p>\n";
    $ret .= "       <p><input type=\"submit\" name=\"submit\" value=\"Ping!\" /></p>\n";
    $ret .= "   </form>\n";
    $ret .= "   </body>\n";
    $ret .= "</html>\n";
}

echo $ret;

?>		      

