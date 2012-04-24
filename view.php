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
include 'header.php'; 

$ret = "<div><form action=\"view.php\" method=\"GET\">\n";
$ret .= "Try someone else's WebID? <input type=\"text\" name=\"uri\" placeholder=\"http://fcns.eu/people/andrei/card#me\" value=\"\" style=\"width: 400px;\">\n";
$ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" View \">\n";
$ret .= "</form></div>\n";

// Display any alerts here
if (isset($confirmation))
    $ret .= $confirmation;

if (isset($_REQUEST['uri'])) {
    $ret .= '<div>';
	$ret .= "<h3 class=\"demoHeaders\">Details for WebID: <a href=\"" . urldecode($_REQUEST['uri']) . "\">";
	if (strlen($_REQUEST['uri']) > 50)
    	$ret .= substr(urldecode($_REQUEST['uri']), 0, 47) . '...';
    else
        $ret .= urldecode($_REQUEST['uri']);
	$ret .= "</a></h3><p>(view  <a href=\"view.php?html=0&uri=" . $_REQUEST['uri'] . "\">RDF structure</a>?)</p><br/>\n";

    $person = new MyProfile($_REQUEST['uri'], $base_uri);
    $person->load();
    $graph = $person->get_graph();
    // check if the user has subscribed to local notifications
    $is_subscribed = (strlen($person->get_hash()) > 0) ? true : false;

    // display controls for adding/removing friend
    if ((webid_is_local($_SESSION['webid'])) && ($_SESSION['webid'] != $_REQUEST['uri'])) {
        if ($_SESSION['myprofile']->is_friend(urldecode($_REQUEST['uri']))) {
        // remove friend
            $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"\" method=\"GET\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"delfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"uri\" value=\"" . $_REQUEST['uri'] . "\">\n";
            $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Remove from friends \">\n";
            $ret .= "</form></div>\n";
        } else {
        // add friend
            $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"\" method=\"GET\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"addfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"uri\" value=\"" . $_REQUEST['uri'] . "\">\n";
            $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Add to friends \">\n";
            $ret .= "</form></div>\n";
        }
    }   
    // more functions if the user has previously subscribed to the local services
    if ($is_subscribed) {
        // Allow user to send notification if target is subscribed
        $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"messages.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"new\" value=\"true\">\n";
        $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $_REQUEST['uri'] . "\">\n";
        $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Message \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></div>\n";
        // Post on the user's wall
        $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"wall.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . $person->get_hash() . "\">\n";
        $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Wall \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></div>\n";
    }

    if ($_REQUEST['html'] == '0') {
  		$ret .= $graph->dump();
    } else {
        $ret .= dumpHTML($graph, $person->get_profile(), $_REQUEST['uri']);
    }
    $ret .= '</div>';
}

echo $ret;
include 'footer.php';
?>        
