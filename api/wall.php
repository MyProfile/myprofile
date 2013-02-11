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
$notification = '';

// generic wall
$feed_hash = 'local';
$owner_webid = 'local';
$owner_hash = 'local';


// Compute the offset based on user request (display older/newer messages)
if (isset($_REQUEST['start']))
    $offset = $_REQUEST['start'];
    
// Limit number of displayed messages to a default value
if (isset($_REQUEST['items']))
    $limit = $_REQUEST['items'];
else
    $limit = 50;

// get the last 50 wall messages for a user
$query = 'SELECT * FROM pingback_messages WHERE ' . 
            'to_hash=\''.mysql_real_escape_string($owner_hash).'\' ' .
            'AND wall=\'1\' ' . 
            'ORDER by date DESC ' .
            'LIMIT '.mysql_real_escape_string($limit);
// Contains the offset value for fetching wall messages
if (isset($offset))
    $query .= ' OFFSET ' . mysql_real_escape_string($offset);   

$result = mysql_query($query);

if (!$result)
    $ret .= error('Unable to connect to the database, to display wall posts!');
else
    $rows = mysql_num_rows($result);

// Get total number of messages specific to the given hash
$total = count_msg_by_hash($owner_hash);

$posts[] = array();

// populate table
$i = 0;
while ($row = mysql_fetch_assoc($result)) {
    // get name
    $name = $row['name'];
    // get picture
    $pic = ($row['pic'] == 'img/nouser.png') ? $pic = 'http://my-profile.eu/img/nouser.png' : $row['pic'];
    $timestamp = $row['date'];

    //$posts[$i] = array('created_at' => date("D, d M Y H:i:s z",$timestamp), 
    $posts[$i] = array('created_at' => date("r",$timestamp), 
                        'from_user' => $row['from_uri'],
                        'from_user_name' => $name,
                        'profile_image_url' => $pic,
                        'text' => $row['msg']
                        );
    $i++;
}

mysql_free_result($result);

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
header('Content-type: application/jsonp');

if (isset($_REQUEST['callback']))
    echo $_REQUEST['callback'].'(';

echo json_encode(array('results' => $posts));

if (isset($_REQUEST['callback']))
    echo ');';

