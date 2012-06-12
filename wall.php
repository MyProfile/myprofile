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
 
require 'include.php';

$ret = '';
// fetch the WebID of the wall's owner
if ((isset($_REQUEST['user'])) && ((strlen($_REQUEST['user']) > 0) && ($_REQUEST['user'] != 'local'))) {
    check_auth($idp, $page_uri);
    
    $owner_webid = get_webid_by_hash(trim($_REQUEST['user']));
    // fetch owner's profile
    $profile = new MyProfile($owner_webid, $base_uri, SPARQL_ENDPOINT);
    $profile->load();
        
    // display private wall only if the requesting user is a friend or the wall owner
    if (($profile->is_friend($_SESSION['webid'])) || ($_SESSION['user_hash'] == $_REQUEST['user'])) {
        $feed_hash = get_feed_by_hash($_REQUEST['user']);       
        $owner_hash = $_REQUEST['user'];
        
        // mark all wall messages as read when the user checks his personal wall
        if ($_SESSION['webid'] == $owner_webid) {
            $query = "UPDATE pingback_messages SET new='0' WHERE ";
            $query .= "to_hash='" . mysql_real_escape_string($_SESSION['user_hash']). "' ";
            $query .= "AND wall='1'";
            $result = mysql_query($query);

            if (!$result) {
                $ret  .= error('Database error while trying to update message status!');
            } else if ($result !== true) {
                mysql_free_result($result);
            }

            $messages = get_msg_count($_SESSION['webid']);
            $wall_msg = get_msg_count($_SESSION['webid'], 1, 1);
        }
        $owner_name = $profile->get_name();
    } else {
        // display main wall for unauthenticated users
        $ret .= success("You are not allowed to view " . $profile->get_name() . "'s Wall.");
        $feed_hash = 'local';
        $owner_webid = 'local';
        $owner_hash = 'local';
    }
} else {
    // generic wall
    $feed_hash = 'local';
    $owner_webid = 'local';
    $owner_hash = 'local';
}

// delete a post
if (isset($_REQUEST['del'])) {
    // verify if we're logged in or not
    check_auth($idp, $page_uri);
    
    $webid = mysql_real_escape_string($_SESSION['webid']);
    $del = mysql_real_escape_string($_REQUEST['del']);
    $reason = '';

    // check if we are allowed to delete?
    $query = "SELECT id FROM pingback_messages WHERE (from_uri='" . $webid . "' OR to_uri='" . $webid . "') AND id='" . $del . "'";
    $result = mysql_query($query);
    if (!$result) {
        $ok = 0;
        $reason = 'The message has NOT been deleted [SQL error 1].';
    } else if (mysql_num_rows($result) > 0){
        $query = "DELETE FROM pingback_messages WHERE id='" . $del . "'";
        $result = mysql_query($query);
        if (!$result) {
            $ok = 0;
            $reason = 'The message has NOT been deleted [SQL error 2].';
        } else {
            $ok = 1;
            $reason = 'The message has been successfully deleted.';
        }
        if ($result !== true && $result !== false) {
            mysql_free_result($result);
        }
    } else {
        $ok = 0;
        $reason = 'The message has NOT been deleted. [unknown cause]';
    }
    
    // display visual confirmation
    if ($ok == 1)
        $confirmation = success($reason);
    else if ($ok == 0)
        $confirmation = error($reason);
}

// ADD a post
if (isset($_REQUEST['comment'])) {
	// verify if we're logged in or not
	check_auth($idp, $page_uri);
    if ((isset($_REQUEST['user'])) && (strlen($_REQUEST['user']) > 0))
        $to_hash = $_REQUEST['user'];
    else
        $to_hash = 'local';

    $msg = trim(substr($_REQUEST['comment'], 0, 10000));

    if (isset($_REQUEST['new'])) {
    // Insert into databse
    $query = "INSERT INTO pingback_messages SET ";
    $query .= "date='" . time() . "', ";
    $query .= "from_uri = '" . mysql_real_escape_string($_SESSION['webid']) . "', ";
    $query .= "to_hash='" . $to_hash . "', ";
    if ($owner_webid != 'local')
        $query .= "to_uri = '" . mysql_real_escape_string($owner_webid) . "', ";
    $query .= "name = '" . mysql_real_escape_string($_SESSION['usr']) . "', ";
    $query .= "pic = '" . mysql_real_escape_string($_SESSION['img']) . "', ";
    $query .= "msg = '" . mysql_real_escape_string($msg) . "', ";
    $query .= "wall='1'";

    $result = mysql_query($query);
    if (!$result) {
        $ret  .= error('Database error while trying to insert new message!');
    } else if ($result !== true) {
        mysql_free_result($result);
    }

    }

    // Update the message with new text
    if (isset($_REQUEST['edit'])) {
        $query = "UPDATE pingback_messages SET "; 
        $query .= "msg = '" . mysql_real_escape_string($msg) . "' ";
        $query .= "WHERE id = '" . mysql_real_escape_string($_REQUEST['edit']) . "' ";
        $query .= "AND from_uri = '" . mysql_real_escape_string($_SESSION['webid']) . "'";

        $result = mysql_query($query);
        if (!$result) {
            $ret  .= error('Database error while updating post!');
        } else if ($result !== true) {
            mysql_free_result($result);
        }
    }

    // Ugly hack until we implement proper caching
    // Update all previous posts with fresh profile data (name and pic)
    $query = "UPDATE pingback_messages SET "; 
    $query .= "name = '" . mysql_real_escape_string($_SESSION['usr']) . "', ";
    $query .= "pic = '" . mysql_real_escape_string($_SESSION['img']) . "' ";
    $query .= "WHERE from_uri = '" . mysql_real_escape_string($_SESSION['webid']) . "'";

    $result = mysql_query($query);
    if (!$result) {
        $ret  .= error('Database error while updating user info!');
    } else if ($result !== true) {
        mysql_free_result($result);
    }
}

// Display the wall's title
if ((isset($owner_name)) && (strlen($owner_name) > 0))
    $title = $owner_name . "'s ";
else
    $title = "MyProfile";

// Form allowing to post messages on the wall
if (isset($_SESSION['webid'])) {
    $form_area = "<form name=\"write_wall\" method=\"POST\" action=\"" . htmlentities($_SERVER['PHP_SELF']) . "\">\n";
    $form_area .= "<input type=\"hidden\" name=\"user\" value=\"" . $_REQUEST['user'] . "\" />\n";
    $form_area .= "<input type=\"hidden\" name=\"new\" value=\"1\" />\n";
    $form_area .= "<table border=\"0\">\n";
    $form_area .= "<tr valign=\"top\">\n";
    $form_area .= "   <td style=\"width: 90px\"><p><a href=\"view.php?uri=" . urlencode($_SESSION["webid"]) . "\" target=\"_blank\">\n";
    $form_area .= "       <img class=\"rounded\" title=\"" . $_SESSION['usr'] . "\" alt=\"" . $_SESSION['usr'] . "\" width=\"64\" src=\"" . $_SESSION['img'] . "\" />\n";
    $form_area .= "   </a></p></td>\n";
    $form_area .= "   <td>\n";
    $form_area .= "       <table border=\"0\">\n"; 
    $form_area .= "       <tr><td><p><b>What's on your mind, <a href=\"view.php?uri=" . urlencode($_SESSION["webid"]) . "\" target=\"_blank\">" . $_SESSION['usr'] . "</a>?</b></p></td></tr>\n";
    $form_area .= "       <tr><td><textarea name=\"comment\" style=\"background-color:#fff; border:solid 1px grey;\" cols=\"80\" rows=\"2\"></textarea><br/><br/></td></tr>\n";
    $form_area .= "       <tr><td><p><input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Post \" /> <font color=\"grey\">";
    $form_area .= "       <small>[Note: you can always delete your message after]</small></font></p></td></tr>\n";
    $form_area .= "       </table>\n";
    $form_area .= "   </td>\n";
    $form_area .= "</tr>\n";
    $form_area .= "</table>\n";
    $form_area .= "</form>\n";
} else {
    $form_area = "<p><font style=\"font-size: 1.3em;\"><a href=\"" . IDP . "" . $page_uri . "\">Login</a> with your WebID to post messages.</font></p>\n";
}

// Page title (User's Wall)
$ret .= "<div>";
$ret .= "<p><font align=\"left\" style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">" . $title . " Wall</font></p>\n";
$ret .= "<p>Subscribe now using this <a href=\"" . $base_uri . "/atom.php?id=" . $owner_hash . "\">Atom feed</a>.</p>\n";
$ret .= "</div>";

// main page
$ret .= "<div class=\"container\">\n";

// Add confirmation message
if (isset($confirmation))
    $ret .= $confirmation;

// Add message form 
$ret .= $form_area;

// display wall messages
// get the last 100 messages
$query = "SELECT * FROM pingback_messages WHERE to_hash='" . mysql_real_escape_string($owner_hash) . "' AND wall='1' ORDER by date DESC LIMIT 100";
$result = mysql_query($query);

if (!$result) {
    $ret .= error('Unable to connect to the database!');
} else if (mysql_num_rows($result) == 0){
    $ret .= "<p><font style=\"font-size: 1.3em;\">There are no messages.</font></p>\n";
} else {
    $ret .= "<form method=\"GET\" action=\"\">\n";
    $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . htmlspecialchars($owner_hash) . "\" />\n";    
    $ret .= "<table border=\"0\">\n";
        
    // populate table
    $i = 0;
    while ($row = mysql_fetch_assoc($result)) {
        // Get name
        $name = $row['name'];
        if ($name == '[NULL]')
            $name = $row['name'];
        // Get picture
        $pic = $row['pic'];
        // Get the date and multiply by 1000 for milliseconds, otherwise moment.js breaks
        $timestamp = $row['date'] * 1000;

        $text = htmlspecialchars($row["msg"]);

        // add horizontal line to separate messages
        $ret .= "<tr><td colspan=\"2\">\n";
        $ret .= "<a name=\"post_" . $row['id'] . "\"><hr style=\"border: none; height: 1px; color: #cccccc; background: #cccccc;\"/></a>\n";
        $ret .= "</td></tr>\n";
        
        $ret .= "<tr valign=\"top\">\n";
        $ret .= "<td width=\"80\" align=\"center\">\n";
        // image
        $ret .= "<a class=\"avatar-link\" href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\"><img title=\"" . $name . "\" alt=\"" . $name . "\" width=\"50\" src=\"" . $pic . "\" class=\"rounded\" property=\"sioc:avatar\"/></a>\n";
        $ret .= "</td>\n";
        $ret .= "<td>";
        $ret .= "<table style=\"width: 700px;\" border=\"0\">\n";
        $ret .= "<tr valign=\"top\">\n";
        $ret .= "<td>\n";
        // author's name
        $ret .= "<b><a href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\" style=\"font-color: black;\">";
        $ret .= "   <span property=\"sioc:UserAccount\">" . $name . "</span>";
        $ret .= "</a></b>";
        // time of post
        $ret .= "<font color=\"grey\"> wrote <span property=\"dcterms:created\">" . date("Y-m-d H:m:s", $row['date']) . "</span> ";
        $ret .= "<span id=\"date_" . $row['id'] . "\">";
        $ret .= "<script type=\"text/javascript\">$('#date_" . $row['id'] . "').text(moment(" . $timestamp . ").from());</script>";
        $ret .= "</span></font>\n";
        $ret .= "</td>\n";
        $ret .= "</tr>\n";
        $ret .= "<tr>\n";
        // message
        $ret .= "<td><p><pre id=\"message_" . $row['id'] . "\"><span property=\"sioc:Post\" id=\"message_text_" . $row['id'] . "\">\n";
        $ret .= put_links($text);
        $ret .= "</span></pre></p></td>\n";
        $ret .= "</tr>\n";
        $ret .= "<tr>\n";
        $ret .= "<td><small>";
        // show options only if we are the source of the post
        if (
            isset($_SESSION['webid'])
            && (
                ($_SESSION['webid'] == $row['from_uri'])
                || (
                    ($_SESSION['webid'] == $row['to_uri'])
                    && (isset($_REQUEST['user']))
                    && ($_REQUEST['user'] != 'local')
                )
            )
        ) {
            $add = '?user=' . $owner_hash;
            // add option to edit post
            $ret .= "<a onClick=\"updateWall('message_text_" . $row['id'] . "', 'wall.php" . $add . "', '" . $row['id'] . "')\" style=\"cursor: pointer;\">Edit</a>";
            // add option to delete post
            $ret .= " <a href=\"wall.php" . $add . "&del=" . $row['id'] . "\">Delete</a>\n";
        }
        $ret .= "</small></td>\n";
        $ret .= "</tr>\n";
        $ret .= "</table>\n";
        $ret .= "</td>\n";
        $ret .= "</tr>\n";
    $i++; 
    }
    mysql_free_result($result);

    $ret .= "</table>\n";
    $ret .= "</form>\n";
    $ret .= "</div>\n";
}

require 'header.php';
echo $ret;
include 'footer.php';
?>        
