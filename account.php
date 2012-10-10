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

$ret .= "<div class=\"container\">\n";
$ret .= "<p><font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Manage Local Account</font></p>\n";
$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";

// proceed only if we have a local account
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
    }
    // 
    if (!isset($_REQUEST['action'])) {
        $ret .= "<form action=\"profile.php\" method=\"get\">\n";
        $ret .= "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
        $ret .= "<p><input class=\"btn\" type=\"submit\" name=\"submit\" value=\"Edit profile\">\n";
        $ret .= "</p>\n";
        $ret .= "</form>\n";

        $ret .= "<form action=\"account.php\" method=\"get\">\n";
        $ret .= "<input type=\"hidden\" name=\"action\" value=\"deleteacc\" />\n";
        $ret .= "<p><input class=\"btn btn-danger\" type=\"submit\" name=\"submit\" value=\"Delete profile\">\n";
        $ret .= " Deleting your local profile is an action that cannot be reversed.</p>\n";
        $ret .= "</form>\n";
    }
} else {
    $ret .= "<p>You need to have a local account in order to manage local profiles.</p>\n";
    $ret .= "<p>Would you like to <a href=\"profile.php\">create a local profile</a>?</p>\n";
}

include 'header.php';
echo $ret;
include 'footer.php';
?>		      

