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

define('INCLUDE_CHECK',true);
set_include_path(get_include_path() . PATH_SEPARATOR . '../');
set_include_path(get_include_path() . PATH_SEPARATOR . 'lib/');

// check if it's a first install
if (!file_exists('config.php')) {
    include 'header.php';
    include 'lib/functions.php';
    echo error('Your MyProfile installation is not complete. Please complete the installation process.');
    echo "<p>Click here to start the installation process.";
    echo "<form action=\"install.php\"><input class=\"btn btn-primary\" type=\"submit\" value=\" MyProfile installation \"></form></p>\n";
    
    include 'footer.php';    
}

// Local includes
require_once 'config.php';
require_once 'lib/functions.php';
require_once 'lib/MyProfile.class.php';

// Logging
require_once 'lib/logger.php';

// WebID auth
require_once 'lib/libAuthentication/lib/Authentication.php';

// Feed stuff
require_once 'lib/feeds/FeedWriter.php';

// RDF stuff
require_once 'lib/EasyRdf.php';
require_once 'lib/graphite.php';

// Get the current document URI
$page_uri = 'http';
$page_uri .= $_SERVER["HTTPS"]=='on'?'s':'';
$page_uri .= '://' . $_SERVER['SERVER_NAME'];
// this is the base uri 
$base_uri = $page_uri;
// add current document
$page_uri .= $_SERVER['REQUEST_URI'];

// Preparing the session
session_name('tzLogin');

// Making the cookie live for 1 day
session_set_cookie_params(24*60*60);

// Create session
if (!isset($_SESSION)) {
    session_start();
    $auth = new Authentication_FoafSSLDelegate();
    $log = new KLogger( "logs/log.txt" , KLogger::DEBUG );

    $_SESSION['base_uri'] = $base_uri;
    $_SESSION['page_uri'] = $page_uri;
}

// If you are logged in, but you don't have the tzRemember cookie (browser restart)
if((isset($_SESSION['id'])) && (!isset($_COOKIE['tzRemember']))) {
    // Destroy the session
    $_SESSION = array();
    session_destroy();	
}

// Logout
if(isset($_REQUEST['logoff'])) {
    # clear WebID session	
    if ($_SESSION['webid'])
        $auth->logout;	

    # clear local session
    $_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

// Authenticate using WebID
if (strlen($auth->webid) > 0) {
    $webid = $auth->webid;

    // do stuff only if authenticated
    if ($auth->isAuthenticated()) {
        if (!isset($_SESSION['myprofile'])) {
            $_SESSION['webid'] = $webid;

            $_SESSION['myprofile'] = new MyProfile($webid, $base_uri);
            // load rest of data only if we can load the profile
            if ($_SESSION['myprofile']->load()) {
                $_SESSION['usr'] = $_SESSION['myprofile']->get_name();
              	$_SESSION['img'] = $_SESSION['myprofile']->get_picture();
	            $_SESSION['feed_hash'] = $_SESSION['myprofile']->get_feed();
                $_SESSION['user_hash'] = $_SESSION['myprofile']->get_hash();
            }
        }

        // Store some data in the session
        setcookie('tzRemember', '0');
        // Log success
        $log->LogInfo("[SUCCESS] Authenticated " . $webid . " => " . $auth->authnDiagnostic);
    } else {
        // log reason why it failed
        $log->LogInfo("[FAILURE] Fail to authenticate " . $webid . " => " . $auth->authnDiagnostic);
    }
}

// Get the number of notifications
if ($_SESSION['webid']) {
    $messages = get_msg_count($_SESSION['webid']);
    $private_msg = get_msg_count($_SESSION['webid'], 1, 0);
    $wall_msg = get_msg_count($_SESSION['webid'], 1, 1);
}

// Bad place to add logic for adding/removing friends.
// add a specific person as friend
if ((isset($_SESSION['myprofile'])) && ($_SESSION['myprofile']->is_local($webid)) && ($_REQUEST['action'] == 'addfriend')) {
    // add friend and display confirmation
    $confirmation = $_SESSION['myprofile']->add_friend(urldecode($_REQUEST['uri']));
    
    $_SESSION['myprofile'] = new MyProfile($webid, $base_uri);
    $_SESSION['myprofile']->load();
}

// add a specific person as friend
if ((isset($_SESSION['myprofile'])) && ($_SESSION['myprofile']->is_local($webid)) && ($_REQUEST['action'] == 'delfriend')) {
    // remove friend and display confirmation    
    $confirmation = $_SESSION['myprofile']->del_friend(urldecode($_REQUEST['uri']));

    $_SESSION['myprofile'] = new MyProfile($webid, $base_uri);
    $_SESSION['myprofile']->load();
}

?>
