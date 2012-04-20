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

// verify if we're logged in or not
check_auth($idp, $page_uri);

$ret = "";
$ret .= "<div class=\"container\">\n";
$ret .= "<font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Manage your local subscription</font>\n";
$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";
    
$ret .= "<div class=\"container\">\n";

// subscribe or unsubscribe
if (isset($_REQUEST['subscribe'])) {
    $ret .= $_SESSION['myprofile']->subscribe();
    $_SESSION['feed_hash'] = $_SESSION['myprofile']->get_feed();
    $_SESSION['user_hash'] = $_SESSION['myprofile']->get_hash();
} else if (isset($_REQUEST['unsubscribe'])) {
    $_SESSION['user_hash'] = null;
    $_SESSION['feed_hash'] = null;
    $ret .= $_SESSION['myprofile']->unsubscribe();
}

// verify if we are already registered

// display form if we are not registered
if (!is_subscribed($_SESSION['webid'])) {
    $ret .= "<p><font color=\"black\">Register your WebID to receive pingbacks.</font></p>\n";
    $ret .= "<form name=\"manage\" method=\"GET\" action=\"\">\n";
    $ret .= "<input type=\"hidden\" name=\"subscribe\" value=\"1\">\n";
    $ret .= "<table border=\"0\">\n";
    $ret .= "<tr><td>Your WebID <font color=\"#00BBFF\">" . $_SESSION['webid'] . "</font> will be registered in order to receive pingbacks.</td></tr>\n";
    $ret .= "<tr><td><br/><input class=\"button ui-button-primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" type=\"submit\" name=\"submit\" value=\"Register\"></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form>\n";
}
// prompt the user
else {   
    $ret .= "<br/><p><font style=\"font-size: 1.3em;\">The URI for your personal feed is <a href=\"" . $base_uri . "/atom.php?id=" . $_SESSION['feed_hash'] . "\">" . $base_uri . "/atom.php?id=" . $_SESSION['feed_hash'] . "</a></font></p>\n";
    $ret .= "<p><font style=\"font-size: 1.3em;\">The URI for your Wall is <a href=\"" . $base_uri . "/wall.php?user=" . $_SESSION['user_hash'] . "\">" . $base_uri . "/wall.php?user=" . $_SESSION['user_hash'] . "</a></font></p>\n";

    $ret .= "<form name=\"manage\" method=\"GET\" action=\"\">\n";
    $ret .= "<input type=\"hidden\" name=\"unsubscribe\" value=\"1\">\n";
    $ret .= "<table border=\"0\">\n";
    $ret .= "<tr><td>Note: in order to receive pingbacks, please add a <i>pingback:to</i> relation to your profile, poiting to <i>" . $base_uri . "/notification.php</i> More information can be found on the <a href=\"http://www.w3.org/wiki/Pingback\">pingback wiki page</a>.<br/><br/></td></tr>\n";
    $ret .= "<tr><td><br />Would you like to unregister your WebID <strong>" . $_SESSION['webid'] . "</strong>? You will no longer be able to receive pingbacks and <font color=\"red\">all exisiting messages and wall posts will be lost</font>!</td></tr>\n";
    $ret .= "<tr><td><br/><input class=\"button ui-button-error ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" type=\"submit\" name=\"submit\" value=\" Unregister \"></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form>\n";
}
   
$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';

?>
