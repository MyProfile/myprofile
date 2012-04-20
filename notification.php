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
 
/*
 * send a notification message
 */ 

//phpinfo(INFO_VARIABLES);
require_once 'include.php'; 

// verify if we're logged in or not
check_auth($idp, $page_uri);

$ret = "";
$ret .= "<div class=\"container\">\n";
// save
if ((isset($_REQUEST['doit'])) && (isset($_REQUEST['to']))) {
    $from   = mysql_real_escape_string($_SESSION['webid']);
    $to     = mysql_real_escape_string($_REQUEST['to']);
    $msg    = mysql_real_escape_string($_REQUEST['message']);
    $hash   = mysql_real_escape_string($_SESSION['user_hash']);
    $name   = mysql_real_escape_string($_SESSION['usr']);
    $pic    = mysql_real_escape_string($_SESSION['img']);

    // write webid uri to database
    $query = "INSERT INTO pingback_messages SET date='" . time() . "', from_uri='" . $from . "', to_hash='" . $hash . "', to_uri='" . $to . "', name='" . $name . "', pic='" . $pic . "'";
    if (isset($_REQUEST['message']))
    $query .= ", msg = ' " . $msg . "'";
    $result = mysql_query($query);
    if (!$result) {
        $ret .= error('SQL Error!');
    } else {
        $ret .= success('Your notification has been successfully delivered!');
    }
}

// show form

$ret .= "<p><font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Send a notification</font></p><br/>\n";
$ret .= "<p>Notifications only work for people which have subscribed to it locally.</p><br/>\n";
$ret .= "<form name=\"send\" method=\"POST\" action=\"\">\n";
$ret .= "<input type=\"hidden\" name=\"doit\" value=\"1\">\n";
$ret .= "<table border=\"0\">\n";
$ret .= "<tr valign=\"top\"><td>From WebID: <br/>&nbsp;</td><td><strong>You</strong> <font color=\"grey\"><small>(" . $_SESSION['webid'] . ")</small></font></td></tr>\n";
$ret .= "<tr valign=\"top\"><td>Target WebID: <br/>&nbsp;</td><td><input size=\"30\" type=\"text\" name=\"to\" value=\"" . $_REQUEST['to'] . "\"></td></tr>\n";
$ret .= "<tr valign=\"top\"><td>Short message (256): <br/>(optional)</td><td> <textarea style=\"height: 130px;\" name=\"message\"></textarea></td></tr>\n";
$ret .= "<tr><td><br/><input type=\"submit\" class=\"button ui-button-primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" name=\"submit\" value=\" Send notification! \"></td><td></td></tr>\n";
$ret .= "</table>\n";
$ret .= "</form>\n";

$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';

?>
