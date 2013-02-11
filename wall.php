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
$notification = '';

// fetch the WebID of the wall's owner
if ((isset($_REQUEST['user'])) && ((strlen($_REQUEST['user']) > 0) && ($_REQUEST['user'] != 'local'))) {
    $wall_on = 'wall-on';
    check_auth(IDP, $page_uri);
    
    $owner_webid = get_webid_by_hash(trim($_REQUEST['user']));
    // fetch owner's profile
    $profile = new MyProfile($owner_webid, $base_uri, SPARQL_ENDPOINT);
    $profile->load();
    $owner_name = $profile->get_name();
    $feed_hash = get_feed_by_hash($_REQUEST['user']);       
    $owner_hash = $_REQUEST['user'];

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
    } else {
        // display a warning for the user
        $warning = true;
    }
} else {
    // generic wall
    $feed_hash = 'local';
    $owner_webid = 'local';
    $owner_hash = 'local';
}


if ((isset($_SESSION['webid'])) && (isset($_REQUEST['activity']))) {
    $title = 'Activity';
    $news_on = 'news-on';
    $home_on = '';
} else {
    // Display the wall's title
    if ((isset($owner_name)) && (strlen($owner_name) > 0))
        $title = $owner_name . "'s Wall";
    else
        $title = "Home";
    
    $home_on = 'home-on';
}


// delete a post
if (isset($_REQUEST['del'])) {
    // verify if we're logged in or not
    check_auth(IDP, $page_uri);
    
    $notification .= delete_message($_SESSION['webid'], $_REQUEST['del']);
}

// ADD a post
if (isset($_REQUEST['comment'])) {
	// verify if we're logged in or not
	check_auth(IDP, $page_uri);
    if ((isset($_REQUEST['user'])) && (strlen($_REQUEST['user']) > 0))
        $to_hash = $_REQUEST['user'];
    else
        $to_hash = 'local';

    // Limit the message to 10k characters
    $msg = trim(substr($_REQUEST['comment'], 0, 10000));
            
    // Get the list of mentioned WebIDs from the message 
    preg_match_all("/<(.*)>/Ui", $msg, $out, PREG_PATTERN_ORDER);  
    $webids = $out[1];

    // Save the time of the request
    $time = time();
    
    if (isset($_REQUEST['new'])) {
        // Insert into databse
        $query = "INSERT INTO pingback_messages SET ";
        $query .= "date='" . $time . "', ";
        $query .= "updated='" . $time . "', ";
        $query .= "etag='" . compute_etag($time). "', ";
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
        } else {
            // get last ID
            $post_id = mysql_insert_id();
            mysql_free_result($result);
           
            // update etags
            $ret .= update_etags($time, $to_hash);
 
            // send a notification to each mentioned user
            foreach ($webids as $to) {
                $ping_msg = 'I have just mentioned you in a ';
                $ping_msg .= ($owner_webid != 'local') ? 'private':'public';
                $ping_msg .=' wall post. You can see it here: ' . $base_uri . '/wall';
                if ($owner_webid != 'local')
                    $ping_msg .= '?user=' . $to_hash;
                $ping_msg .= '#post_'.$post_id;
                // send only if the source != target
                if (($_SESSION['webid'] != $to) && (preg_match('/^http(s?):/', $to)))
                    sendPing($to, $ping_msg, $base_uri, false);
            }
        }

    }

    // Update the message with new text
    if (isset($_REQUEST['edit'])) {
        $query = "UPDATE pingback_messages SET ";
        $query .= "updated='" . $time . "', "; 
        $query .= "msg = '" . mysql_real_escape_string($msg) . "' ";
        $query .= "WHERE id = '" . mysql_real_escape_string($_REQUEST['edit']) . "' ";
        $query .= "AND from_uri = '" . mysql_real_escape_string($_SESSION['webid']) . "'";

        $result = mysql_query($query);
        if (!$result) {
            $ret  .= error('Database error while updating post!');
        } else if ($result !== true) {
            mysql_free_result($result);
        }

        // update etag for wall posts
        $ret .= update_etags($time, $to_hash);
    }

    // Ugly hack until we implement proper caching
    // Update all previous posts with fresh profile data (name and pic)
    $query = "UPDATE pingback_messages SET "; 
    $query .= "name = '" . mysql_real_escape_string($_SESSION['usr']) . "', ";
    $query .= "pic = '" . mysql_real_escape_string($_SESSION['img']) . "' ";
    $query .= "WHERE from_uri = '" . mysql_real_escape_string($_SESSION['webid']) . "'";

    $result = mysql_query($query);
    if (!$result) {
        $ret .= error('Database error while updating user info!');
    } else if ($result !== true) {
        mysql_free_result($result);
    }
}

// Form allowing to post messages on the wall
if (isset($_SESSION['webid'])) {
    $form_area = "<div class=\"wall-new r5\">\n";
    $form_area .= "<form method=\"post\" action=\"" . htmlentities($_SERVER['PHP_SELF']) . "\">\n";
    $form_area .= "<input type=\"hidden\" name=\"user\" value=\"" . $owner_hash . "\" />\n";
    $form_area .= "<input type=\"hidden\" name=\"new\" value=\"1\" />\n";
    $form_area .= "<table border=\"0\">\n";
    $form_area .= "<tr valign=\"top\">\n";
    $form_area .= "   <td style=\"width: 80px\"><p><a href=\"view?webid=" . urlencode($_SESSION["webid"]) . "\" target=\"_blank\">\n";
    $form_area .= "       <img class=\"r5\" title=\"" . $_SESSION['usr'] . "\" alt=\"" . $_SESSION['usr'] . "\" width=\"64\" src=\"" . $_SESSION['img'] . "\" />\n";
    $form_area .= "   </a></p></td>\n";
    $form_area .= "   <td>\n";
    $form_area .= "       <table border=\"0\">\n"; 
    $form_area .= "       <tr><td><p><b>What's on your mind, <a href=\"view?webid=" . urlencode($_SESSION["webid"]) . "\" target=\"_blank\">" . $_SESSION['usr'] . "</a>?</b></p></td></tr>\n";
    $form_area .= "       <tr><td><textarea id=\"comment\" name=\"comment\" onfocus=\"textAreaResize(this)\" class=\"textarea-wall\"></textarea></td></tr>\n";
    $form_area .= "       <tr><td><br/><input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Write \" /></td></tr>\n";
    $form_area .= "       </table>\n";
    $form_area .= "   </td>\n";
    $form_area .= "</tr>\n";
    $form_area .= "</table>\n";
    $form_area .= "</form>\n";
    $form_area .= "</div>\n";
} else {
    $form_area = "<div class=\"wall-new\"><p><font style=\"font-size: 1.3em;\"><a href=\"" . IDP . $page_uri . "\">Login</a> with your WebID to post messages.</font></p></div>\n";
}

$ret .= "<div class=\"content relative shadow clearfix main\">\n";

// Page title (User's Wall)
$ret .= "<div>";
$ret .= "   <p>Subscribe to the current wall using <a href=\"" . $base_uri . "/atom.php?id=" . $owner_hash . "\">this Atom feed</a>.</p>\n";
$ret .= "</div>";

// Add notification message
if (strlen($notification) > 0)
    $ret .= $notification;

// Add message form 
$ret .= $form_area;

// Display warning if the user isn't allowed to view a certain wall
if (isset($warning)) {
    $ret .= "<h3>You are not allowed to see this page because you are not a friend of ";
    $ret .= "<a href=\"view?webid=" . urlencode($owner_webid) . "\">" . $profile->get_name() . ".</a></h3>";
} else {
    // Display messages

    $w = new Wall($owner_hash);
    $posts = $w->load(20, 0, $_REQUEST['activity']);
    $offset = $w->get_offset();
    
    // page content
    $ret .= "<div id=\"wall\">\n";
    
    $ret .= $posts;
    
    // add Load more button
    $ret .= "</div>\n";
    $ret .= "<p></p>\n";
}
$ret .= "</div>\n";

// prepare etag
$etag_array = get_etag($owner_hash);

$lastmod = gmdate('D, d M Y H:i:s \G\M\T', $etag_array['date']);
$etag = $etag_array['etag'];

$ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastmod : null; 
$iftag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] == $etag : null; 

if (($ifmod || $iftag) && ($ifmod !== false && $iftag !== false)) { 
    header('Not Modified',true,304);
} else {
    header("Last-Modified: $lastmod"); 
    header("ETag: \"" . $etag . "\"");
}

require 'header.php';
echo $ret;
include 'footer.php';

