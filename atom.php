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
 
include 'include.php';
	
// Get the corresponding webid for the hash
if ($_REQUEST['id'] != 'local') {
	$webid = get_webid_by_hash($_REQUEST['id']);

	// complain if the feed ID is not valid
	if (!$webid) {
	    echo "<font style=\"font-size: 1.3em;\">There is no feed associated to this ID!</font>\n";
	    exit;
	}
} else {
	$webid = $_REQUEST['id'];
}

// IMPORTANT : No need to add id for feed or channel. It will be automatically created from link.

//Creating an instance of FeedWriter class. 
//The constant ATOM is passed to mention the version
$Feed = new FeedWriter(ATOM);

//Setting the channel elements
//Use wrapper functions for common elements
if ($_REQUEST['id'] != 'local')
	$Feed->setTitle('MyProfile Pingbacks/Notifications Feed');
else
	$Feed->setTitle('MyProfile Wall Feed');

$Feed->setLink($base_uri . '/feed.php?id=' . $_REQUEST['id']);
	
//For other channel elements, use setChannelElement() function
$Feed->setChannelElement('updated', date(DATE_ATOM , time()));
$Feed->setChannelElement('author', array('name'=>'WebID Test Suite'));

// fetch notifications for the selected webid
$query = "SELECT * FROM pingback_messages WHERE to_hash='" . mysql_real_escape_string($_REQUEST['id']) . "' ORDER BY date DESC LIMIT 10";
$result = mysql_query($query);

while ($row = mysql_fetch_assoc($result)) {
    //Create an empty FeedItem
    $newItem = $Feed->createNewItem();
 
    // The message sender's name
    $name = $row['name'];

    //Add elements to the feed item
    //Use wrapper functions to add common feed elements
    if ($row['wall'] == 0)
        $newItem->setTitle('Personal notification message.');
    else
        $newItem->setTitle('Wall message.');
    $newItem->setLink($base_uri . '/my_notifications.php');
    $newItem->setDate($row['date']);
    //Internally changed to "summary" tag for ATOM feed
    
    $newItem->setDescription("From: <a href\"" . $row['from_uri'] . "\">" . $name . "</a><br/><br/>\n" . $row['msg']);

    //Now add the feed item	
    $Feed->addItem($newItem);
}


//OK. Everything is done. Now genarate the feed.
$Feed->generateFeed();
  
?>
