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
$title = "Account Recovery";

$ret = '';
$ret .= "<div class=\"content relative shadow clearfix main\">\n";

// Recover account
if (webid_is_local($_REQUEST['webid'])) {
    
    if (isset($_REQUEST['recovery_webid'])) {
          $recovery = new Recovery();
          $ret .= $recovery->recover($_REQUEST['recovery_webid']);
    }
} else {
    $ret .= "The account recovery is only available for local users.";
}

if (isset($_REQUEST['recovery_code']) && isset($_SESSION['recovery_status']))
    $ret .=$_SESSION['recovery_status'];

// display recovery options
$ret .= "<p></p>\n";
$ret .= "<h2><strong>Recover Your Account</strong></h2>\n";
$ret .= "<form method=\"post\">\n";
$ret .= "<table><tr>\n";
$ret .= "<td>\n";
$ret .= "Please type your WebID address here:";
$ret .= "</td>\n";
$ret .= "<td>\n";
$ret .= "<input type=\"text\" class=\"recovery\" name=\"recovery_webid\" />\n";
$ret .= "<input class=\"btn margin-5\" type=\"submit\" name=\"recover\" value=\"Recover account\">\n";
$ret .= "</td>\n";
$ret .= "</tr>\n";
$ret .= "<tr><td colspan=\"2\">\n";
$ret .= "<strong>Note!</strong> Instructions on how to proceed will be sent to the email address you have specified in your preferences page.";
$ret .= "</td></tr>\n";
$ret .= "<tr><td colspan=\"2\">\n";
$ret .= "<p><hr class=\"hr-msg\"></p>\n";
$ret .= "</td></tr>\n";
$ret .= "</table>\n";
$ret .= "</form> \n";

// authentication
$ret .= "<p></p>\n";
$ret .= "<h2><strong>Authenticate with your recovery code</strong></h2>\n";
$ret .= "<form method=\"post\">\n";
$ret .= "<table><tr>\n";
$ret .= "<td>\n";
$ret .= "Please provide your recovery code here:";
$ret .= "</td>\n";
$ret .= "<td>\n";
$ret .= "<input type=\"text\" class=\"recovery\" name=\"recovery_code\" />\n";
$ret .= "<input class=\"btn margin-5\" type=\"submit\" name=\"recover\" value=\"Login\">\n";
$ret .= "</td>\n";
$ret .= "</tr>\n";
$ret .= "<tr><td colspan=\"2\">\n";
$ret .= "<p><hr class=\"hr-msg\"></p>\n";
$ret .= "</td></tr>\n";
$ret .= "</table>\n";
$ret .= "</form> \n";

// pairing
$ret .= "<p></p>\n";
$ret .= "<h2><strong>Authenticate with your pairing PIN</strong></h2>\n";
$ret .= "<form method=\"post\">\n";
$ret .= "<table><tr>\n";
$ret .= "<td>\n";
$ret .= "Please provide your pairing PIN here:";
$ret .= "</td>\n";
$ret .= "<td>\n";
$ret .= "<input type=\"text\" name=\"pairing_pin\" />\n";
$ret .= "<input class=\"btn margin-5\" type=\"submit\" name=\"recover\" value=\"Pair\">\n";
$ret .= "</td>\n";
$ret .= "</tr></table>\n";
$ret .= "</form> \n";

$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';
?>

