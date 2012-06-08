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


// load a specific webid instead of the logged user
if (isset($_REQUEST['webid'])) {
    $person = new MyProfile(urldecode($_REQUEST['webid']), $base_uri, $endpoint);
    $person->load();
    $profile = $person->get_profile();
} else {
    // verify if we're logged in or not, so we get the user's list of contacts
    check_auth($idp, $page_uri);
    $profile = $_SESSION['myprofile']->get_profile();
}

$user = $profile->get("foaf:name");

$form = "";
$form .= "<div>\n";
$form .= "<form action=\"lookup.php\" method=\"GET\">\n";
$form .= "Look for someone else? <input type=\"text\" name=\"search\" value=\"\" style=\"width: 400px;\">\n";
$form .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Search \">\n";
$form .= "</form></div>\n";

$ret = '';
$ret .= "<div class=\"clear\"></div>\n";
$ret .= "<p><font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">" . $user . "'s Friends</font></p>\n";
$ret .= "<p><small>This page may take a while to finish loading...<small></p>\n";

// display confirmation message here
if (isset($confirmation)) {
    $ret .= $confirmation;
}

// call ajax script here to load each friend's data
$friends = $profile->all('foaf:knows')->join(',');

// show something if there are friends for this webid
if (strlen($friends) > 0) {
	$ret .= '<script type="text/javascript">
        var list = "' . (string)$friends . '";
        var uris = list.split(","); 
        
        // Create placeholders for each contact info
        for (i = 0; i < uris.length; i++) {
            var webid = uris[i];
            $("#content").append("<div id=\"person_"+i+"\"></div>");
        }
        </script>';
        
	$ret .= '<script type="text/javascript">
        var list = "' . (string)$friends . '";
        var uris = list.split(","); 
        
        if (list.length > 0) {
            for (i = 0; i < uris.length; i++) {
                var webid = uris[i];
                //var hash = webid.slice(webid.indexOf("#"));
            
                // script URI that we will call for each user
			    var addr = "load.php?webid="+encodeURIComponent(webid)+"&me="+encodeURIComponent("' . $_SESSION['webid'] . '");            
	
                $("#person_"+i).load(addr);
            }
        }
        </script>';
    
} else {
	$ret .= "The user does not have any friends.<br/>\n";
}
include 'header.php';
echo $form;
echo $ret;
include 'footer.php';   
?>       
