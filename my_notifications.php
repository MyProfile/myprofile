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

/*
 * display the user's notifications
 */ 

require_once 'include.php'; 

// verify if we're logged in or not
check_auth($idp, $page_uri);

$ret = "";
$ret .= "<div class=\"container\">\n";
$ret .= "<font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Notifications</font>\n";
$ret .= "<div class=\"clear\"><br/><br/></div>\n";
$ret .= "</div>\n";

$ret .= "<div class=\"container\">\n";

if (isset($_REQUEST['id'])) {
    $ok = true;
    $id = mysql_real_escape_string($_REQUEST['id']);
    $me = mysql_real_escape_string($_SESSION['webid']);
    // delete
    if (isset($_REQUEST['delete'])) {
        $query = "DELETE FROM pingback_messages WHERE id='" . $id . "' AND to_uri='" . $me . "'";
        $result = mysql_query($query);
        if (!$result) {
            $ok = false;
            $ret  = 'Invalid query: ' . mysql_error() . "\n";
            $ret .= 'Query: ' . $query;
        }
    } else if (isset($_REQUEST['read'])) {
        // set status to read
        $query = "UPDATE pingback_messages SET new=0 WHERE id='" . $id . "' AND to_uri='" . $me . "'";
        $result = mysql_query($query);
        if (!$result) {
            $ok = false;
            $ret  = 'Invalid query: ' . mysql_error() . "\n";
            $ret .= 'Query: ' . $query;
        }
    } else if (isset($_REQUEST['unread'])) {
        // set status to unread
        $query = "UPDATE pingback_messages SET new=1 WHERE id='" . $id . "' AND to_uri='" . $me . "'";
        $result = mysql_query($query);
        if (!$result) {
            $ok = false;
            $ret  = 'Invalid query: ' . mysql_error() . "\n";
            $ret .= 'Query: ' . $query;
        }
    } else if (isset($_REQUEST['reply'])) {
        header("Location: notification.php?to=" . urlencode($_REQUEST['to']));
    }
    
    $messages = get_msg_count($_SESSION['webid']);
    $private_msg = get_msg_count($_SESSION['webid'], 1, 0);
}

// verify if we are already registered
if (!is_subscribed($_SESSION['webid'])) {
    $ret .= "<br/><p><font style=\"font-size: 1.3em;\">You have not registered to receive notifications! You can register <a href=\"subscription.php\">here</a>.</font></p>\n";
}
// display pingbacks
else {
    $query = "SELECT * FROM pingback_messages WHERE to_uri='" . mysql_real_escape_string($_SESSION['webid']) . "' AND wall='0' ORDER BY date DESC LIMIT 100";
    $result = mysql_query($query);
    if (!$result) {
        $ret  = 'Invalid query: ' . mysql_error() . "\n";
        $ret .= 'Whole query: ' . $query;
    } else if (mysql_num_rows($result) == 0){
        $ret .= "<br/><font style=\"font-size: 1.3em;\">You have no notifications.</font>\n";
    } else {
        // populate table
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {  
            $id = $row['id'];        
            $name = $row['name'];
            if ($name == '[NULL]')
                $name = $row['name'];
            // Get picture
            $pic = $row['pic'];
            $new = $row['new'];

            $text = htmlspecialchars($row["msg"]);
            $text = put_links($text);

            $ret .= "<form name=\"view_pingbacks\" method=\"POST\" action=\"\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"1\">\n";
            $ret .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\">\n";
            $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $row['from_uri'] . "\">\n";
            $ret .= "<table border=\"0\">\n";

            $ret .= "<tr valign=\"top\">\n";
            $ret .= "   <td width=\"80\" align=\"center\">\n";
            $ret .= "       <a href=\"lookup.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\"><img title=\"" . $name . "\" alt=\"" . $name . "\" width=\"48\" src=\"" . $pic . "\" style=\"padding: 0px 0px 10px;\" /></a>\n";
            $ret .= "   </td>\n";
            $ret .= "   <td>";
            $ret .= "       <table border=\"0\">\n";
            $ret .= "       <tr valign=\"top\">\n";
            $ret .= "           <td><b><a href=\"lookup.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\" style=\"font-color: black;\">" . $name . "</a></b></td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr>\n";
            $ret .= "           <td><pre>" . $text . "</pre></td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr>\n";
            $ret .= "           <td style=\"color: grey;\"><br/><small>" . date('Y-m-d H:i:s', $row['date']) . "</small> \n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr><td><br/>\n";
            if (is_subscribed($row['from_uri']))
                $ret .= "           <input type=\"submit\" class=\"button ui-button-primary\" name=\"reply\" value=\" Reply \">";
            if ($new == 1)
                $ret .= "           <input type=\"submit\" class=\"button ui-button-primary\" name=\"read\" value=\" Mark as read \">";
            else if ($new == 0)
                $ret .= "           <input type=\"submit\" class=\"button\" name=\"unread\" value=\" Mark as unread \">";
            $ret .= "           <input type=\"submit\" class=\"button ui-button-error\" name=\"delete\" value=\" Delete \"> ";
            $ret .= "           </td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr><td>&nbsp;</td></tr>\n";
            $ret .= "       </table>\n";
            $ret .= "   </td>\n";
            $ret .= "</tr>\n";
                 
            $ret .= "<tr><td colspan=\"2\"><hr style=\"border: none; height: 1px; color: #cccccc; background: #cccccc;\"/><br/><br/></td></tr>\n";
            $ret .= "</table>\n";
            $ret .= "</form>\n";
            $i++;
        }

    }
}

$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';

?>
