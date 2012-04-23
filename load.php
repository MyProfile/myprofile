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

include_once 'include.php';

$ret = "";

if (isset($_SESSION['webid']))
    $me = $_SESSION['webid'];
else if (isset($_REQUEST['me']))
    $me = urldecode($_REQUEST['me']);

if (isset($_REQUEST['webid'])) {
    $webid = urldecode($_REQUEST['webid']);

    // fetch info for webid
    $person = new MyProfile($webid, $base_uri);
    $person->load();
	$profile = $person->get_profile();
    
    // find if he has me in his list of foaf:knows!
    $all = (string)$profile->all("foaf:knows")->join(',');
    $array = explode(',', $all);
    $has_me = '[NULL]';
    if (in_array($me, $array))
        $has_me = 'true';
      
    // check if the user has subscribed to local notifications
    $is_subscribed = (strlen($person->get_hash()) > 0) ? true : false;

    // start populating array
    $friend = array('webid' => (string)$webid,
        'img' => (string)$person->get_picture(),
        'name' => (string)$profile->get("foaf:name"),
        'nick' => (string)$profile->get("foaf:nick"),
        'email' => (string)$profile->get("foaf:mbox"),
        'blog' => (string)$profile->get("foaf:weblog"),
        'pingback' => (string)$profile->get("http://purl.org/net/pingback/to"),
        'hash' => $person->get_hash(),
        'hasme' => $has_me,
        'new' => $new
    );

    $ret .= "<table>\n";
    $ret .= "<tr bgcolor=\"\"><td>\n";
    $ret .= "<table><tr>\n";
    $ret .= "<td width=\"70\" style=\"vertical-align: top; padding: 10px;\">\n";
    $ret .= "<div align=\"left\"><a href=\"lookup.php?uri=" . urlencode($friend['webid']) . "\" target=\"_blank\"><img title=\"Click to see more information about " . $friend['name'] . "\" alt=\"" . $friend['name'] . ".\" width=\"64\" src=\"" . $friend['img'] . "\" /></a></div>\n";
    $ret .= "</td>\n";

    $ret .= "<td><table>\n";
    if ($friend['name'] != '[NULL]')
        $ret .= "<tr><td><strong>" . $friend['name'] . "</strong></td></tr>\n";
    else
        $ret .= "<tr><td><strong>Anonymous</strong></td></tr>\n";
            
    if ($friend['nick'] != '[NULL]')
        $ret .= "<tr><td>''" . $friend['nick'] . "''</td></tr>\n";

    if ($friend['hasme'] != '[NULL]') 
        $ret .= "<tr><td><div style=\"color:#60be60;\">Has you as friend.</div></td></tr>\n";
 
    //$ret .= "<tr><td>&nbsp;</td></tr>\n";

    if ($friend['email'] != '[NULL]')
        $ret .= "<tr><td><a href=\"" . $friend['email'] . "\">" . clean_mail($friend['email']) . "</a></td></tr>\n";

    if ($friend['blog'] != '[NULL]')
        $ret .= "<tr><td><a href=\"" . $friend['blog'] . "\">" . $friend['blog'] . "</a></td></tr>\n";

    $ret .= "<tr><td><a href=\"" . $friend['webid'] . "\">" . $friend['webid'] . "</a></td></tr>\n";
    $ret .= "</table>\n";
    
    $ret .= "<br/><table>\n";
    $ret .= "<tr>\n";
    $ret .= "<td style=\"padding-right: 10px; float: left;\">\n";
    $ret .= "<form action=\"pingback.php\" method=\"GET\">\n";
    $ret .= "<input type=\"hidden\" name=\"uri\" value=\"" . $friend['webid'] . "\">\n";
    $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Ping \" onclick=\"this.form.target='_blank';return true;\">\n";
    $ret .= "</form>\n";
    $ret .= "</td>\n";
    
    // add or remove friends if we have them in our list
    if (webid_is_local($_SESSION['webid'])) {
        if ($_SESSION['myprofile']->is_friend($webid)) {
        // remove friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"GET\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"delfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"uri\" value=\"" . $friend['webid'] . "\">\n";
            $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Remove \">\n";
            $ret .= "</form></td>\n";
        } else {
        // add friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"GET\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"addfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"uri\" value=\"" . $friend['webid'] . "\">\n";
            $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Add \">\n";
            $ret .= "</form></td>\n";
        }
    }

    // more functions if the user has previously subscribed to the local services
    if ($is_subscribed) {
        // Allow user to send messages if target is subscribed
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"messages.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"new\" value=\"true\">\n";
        $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $friend['webid'] . "\">\n";
        $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Message \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
        // Post on the user's wall
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"wall.php\" method=\"GET\">\n";
        $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . $friend['hash'] . "\">\n";
        $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Wall \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
    }

    $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"GET\">\n";
    $ret .= "<input type=\"hidden\" name=\"webid\" value=\"" . $friend['webid'] . "\">\n";
    $ret .= "<input type=\"hidden\" name=\"me\" value=\"" . $_REQUEST['me'] . "\">\n";
    $ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Friends \">\n";
    $ret .= "</form></td>\n";
    $ret .= "</tr></table></p>\n";
   
    $ret .= "</td>\n";
    $ret .= "</tr></table>\n";

    $ret .= "</td></tr>\n";
    $ret .= "</table>\n";

} else {
    $ret .= "You need to specify a person.";
}
echo $ret;
   
?> 
