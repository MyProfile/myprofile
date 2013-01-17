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
$title = "Preferences";
$pref_on = 'settings-on';


// verify if we're logged in or not
check_auth(IDP, $page_uri);


$ret = '';
$ret .= "<div class=\"content relative shadow clearfix main\">\n";

if (webid_is_local($_SESSION['webid'])) {
    if ($_REQUEST['action'] == 'deleteacc') {
        $path = webid_get_local_path($_SESSION['webid']);
        $ok_del = false;
        // delete profile

        if (($_SESSION['myprofile']->delete_account()) && (rrmdir($path)))
            $ok_del = true;
        
        // display confirmation
        if ($ok_del) {
            $ret .= success('Your profile has been deleted.');
            $ret .= "<div><br/>Attention: do not forget to delete the corresponding certificate from your browser, since it is useless now.</div>\n";
        } else {
            $ret .= error('Could not remove your profile!');
        }
                    
        // log user out and clear WebID session	
        if ($_SESSION['webid'])
            $auth->logout;	

        // clear local session
        $_SESSION = array();
        session_destroy();

        include 'header.php';
        echo $ret;
        include 'footer.php';
        exit;
    }
}

// subscribe or unsubscribe
if (isset($_REQUEST['subscribe'])) {
    $ret .= $_SESSION['myprofile']->subscribe();
    $_SESSION['feed_hash'] = $_SESSION['myprofile']->get_feed();
    $_SESSION['user_hash'] = $_SESSION['myprofile']->get_hash();
} else if (isset($_REQUEST['submit'])) {
    // Unsubscribed if the user ticked off the checkbox
    if ((!isset($_REQUEST['subscription'])) || ($_REQUEST['subscription'] == 'off')) {
        $_SESSION['user_hash'] = null;
        $_SESSION['feed_hash'] = null;
        $ret .= $_SESSION['myprofile']->unsubscribe();
    }
    // Unsubscribe from receiving email notifications
    if ((isset($_REQUEST['email'])) || ($_REQUEST['email'] == 'on')) {
        $ret .= $_SESSION['myprofile']->subscribe_email();
    } else if ((!isset($_REQUEST['email'])) || ($_REQUEST['email'] == 'off')) {
        $ret .= $_SESSION['myprofile']->unsubscribe_email();
    }
}

// display form if we are not registered
if (!is_subscribed($_SESSION['webid'])) {
    $ret .= "<h2><strong>Manage notifications.</strong></h2>\n";
    $ret .= "<form name=\"manage\" method=\"post\">\n";
    $ret .= "<input type=\"hidden\" name=\"subscribe\" value=\"1\">\n";
    $ret .= "<table border=\"0\">\n";
    $ret .= "<tr><td>Would you like to register in order to receive notifications?</td></tr>\n";
    $ret .= "<tr><td><br/><input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\"Register\"></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form>\n";
}
// prompt the user
else {
    $ret .= "<h1>The URI for your Wall is <a href=\"" . $base_uri . "/wall?user=" . $_SESSION['user_hash'] . "\">" . $base_uri . "/wall?user=" . $_SESSION['user_hash'] . "</a></h1>\n";

    $check_email = '';
    if (is_subscribed_email($_SESSION['webid']) == true)
        $check_email = 'checked';
    $check_subscription = 'checked';
        
    $ret .= "<h2><strong>Manage notifications.</strong></h2>\n";
    $ret .= "<form method=\"post\">\n";
    $ret .= "<table>\n";
    $ret .= "<tr><td><input type=\"checkbox\" name=\"subscription\" ".$check_subscription." /> Receive notifcations. (Note: Unsubscribing <strong>removes all</strong> exisiting messages and wall posts!)</td></tr>\n";
    $ret .= "<tr><td><input type=\"checkbox\" name=\"email\" ".$check_email." /> Receive notifications through email.</td></tr>\n";
    $ret .= "<tr><td><br /><input class=\"btn margin-5\" type=\"submit\" name=\"submit\" value=\" Modify \"></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form>\n";
}

// display options for local users only
//if (webid_is_local($_SESSION['webid'])) {
    // 
    if (!isset($_REQUEST['action'])) {
        $ret .= "<p></p>\n";
        $ret .= "<h2><strong>Manage profile.</strong></h2>\n";

        $ret .= "<table><tr>\n";
        $ret .= "<td>\n";
        $ret .= "<form action=\"profile\" method=\"get\">\n";
        $ret .= "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
        $ret .= "<input class=\"btn margin-5\" type=\"submit\" name=\"submit\" value=\"Edit profile\">\n";
        $ret .= "</form> \n";
        $ret .= "</td><td>\n";
        $ret .= "<form method=\"post\">\n";
        $ret .= "<input type=\"hidden\" name=\"action\" value=\"deleteacc\" />\n";
        $ret .= "<input class=\"btn btn-danger margin-5\" type=\"submit\" name=\"delete\" value=\"Delete profile\">\n";
        $ret .= "</form>\n";
        $ret .= "</td>\n";
        $ret .= "</tr></table>\n";
        $ret .= "<strong>Warning!</strong> Deleting a profile cannot be undone. All your local data will be removed (profile, wall posts, messages, etc.)    .";
    }
//}

$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';

?>
