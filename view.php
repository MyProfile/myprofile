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

$ret = "<div><form action=\"lookup.php\" method=\"GET\">\n";
$ret .= "Look for someone else? <input type=\"text\" name=\"search\" value=\"\" style=\"width: 400px;\">\n";
$ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Search \">\n";
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
	$ret .= "</a></h3><p>(view  <a href=\"view.php?html=0&uri=" . urlencode($_REQUEST['uri']) . "\">RDF structure</a>?)</p><br/>\n";

    // graph
    $person = new MyProfile($_REQUEST['uri'], $base_uri, $endpoint);
    $person->load(true);
    
    $graph = $person->get_graph();
    $profile = $person->get_profile();
    $profile->loadSameAs();
    
    // check if the user has subscribed to local messages
    $is_subscribed = (strlen($person->get_hash()) > 0) ? true : false;

    // send messages 
    $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"messages.php\" method=\"GET\">\n";
    $ret .= "<input type=\"hidden\" name=\"new\" value=\"true\">\n";
    $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $_REQUEST['uri'] . "\">\n";
    $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Message \" onclick=\"this.form.target='_blank';return true;\">\n";
    $ret .= "</form></div>\n";

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
        // Post on the user's wall
        $ret .= "<div style=\"padding-right: 10px; float: left;\"><form action=\"wall.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . $person->get_hash() . "\">\n";
        $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Wall \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></div>\n";
    }

    if ($_REQUEST['html'] == '0') {
  		$ret .= $graph->dump();
    } else {
        $ret .= viewProfile($graph, $profile, $_REQUEST['uri'], $base_uri, $endpoint);
    }
    $ret .= '</div>';
}

echo $ret;
include 'footer.php';
?>        
