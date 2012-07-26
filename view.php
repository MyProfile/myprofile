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

if (isset($_REQUEST['webid'])) {
    $ret .= '<div>';
	$ret .= "<h3 class=\"demoHeaders\">Details for WebID: <a href=\"" . urldecode($_REQUEST['webid']) . "\">";
	if (strlen($_REQUEST['webid']) > 50)
    	$ret .= substr(urldecode($_REQUEST['webid']), 0, 47) . '...';
    else
        $ret .= urldecode($_REQUEST['webid']);
	$ret .= "</a></h3><p>(view  <a href=\"view.php?html=0&webid=" . urlencode($_REQUEST['webid']) . "\">RDF</a> or \n";
	$ret .= "</a></h3><a href=\"view.php?html=1&webid=" . urlencode($_REQUEST['webid']) . "\">normal</a>?)</p><br/>\n";

    // graph
    $person = new MyProfile(urldecode($_REQUEST['webid']), BASE_URI, SPARQL_ENDPOINT);
    $person->load(true);
    
    $graph = $person->get_graph();
    $profile = $person->get_profile();
    // sameAs is disabled until further notice
    //$profile->loadSameAs();
    
    // check if the user has subscribed to local messages
    $is_subscribed = (strlen($person->get_hash()) > 0) ? true : false;

    $ret .= "<table><tr>\n";
    // add or remove friends if we have them in our list
    if ((isset($_SESSION['webid'])) && (webid_is_local($_SESSION['webid']))) {
        if ($_SESSION['myprofile']->is_friend($_REQUEST['webid'])) {
        // remove friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"POST\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"delfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"webid\" value=\"" . $_REQUEST['webid'] . "\">\n";
            $ret .= "<input src=\"img/actions/remove.png\" type=\"image\" title=\"Remove friend\" name=\"submit\" value=\" Remove \">\n";
            $ret .= "</form></td>\n";
        } else {
        // add friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"POST\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"addfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"webid\" value=\"" . $_REQUEST['webid'] . "\">\n";
            $ret .= "<input src=\"img/actions/add.png\" type=\"image\" title=\"Add friend\" name=\"submit\" value=\" Add \">\n";
            $ret .= "</form></td>\n";
        }
    }

    // send messages using the pingback protocol 
    if ($person->get_pingback() != null) {
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"messages.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"new\" value=\"true\">\n";
        $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $_REQUEST['webid'] . "\">\n";
        $ret .= "<input src=\"img/actions/message.png\" type=\"image\" title=\"Send a message\" name=\"submit\" value=\" Message \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
    }

    // more functions if the user has previously subscribed to the local services
    if ($is_subscribed) {
        // Post on the user's wall
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"wall.php\" method=\"POST\">\n";
        $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . $person->get_hash() . "\">\n";
        $ret .= "<input src=\"img/actions/wall.png\" type=\"image\" title=\"View posts\" name=\"submit\" value=\" Wall \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
    }
    $ret .= "</tr></table>\n";

    if ((isset($_REQUEST['html'])) && ($_REQUEST['html'] == '0')) {
  		$ret .= $graph->dump();
    } else {
        $ret .= viewProfile($graph, $profile, urldecode($_REQUEST['webid']), BASE_URI, SPARQL_ENDPOINT);
    }
    $ret .= '</div>';
}

echo $ret;
include 'footer.php';
?>        
