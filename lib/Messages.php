<?php

if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');


// Returns a short handle based on a given WebID
function preg_get_handle_by_webid($array) {
    $webid = urldecode($array[1]);
    $person = new MyProfile($webid, BASE_URI, SPARQL_ENDPOINT);
    $person->load();
    $name = $person->get_name();
    $nick = $person->get_nick();
    
    $ret = '';
    $ret .= ' <a href="view.php?webid=' . urlencode($webid) . '">';
    $ret .= ($nick != null) ? '@' . $nick : '@' . $name;
    $ret .= '</a>';
    return $ret;
}

// Attempt to ping someone using Semantic Pingback.
function sendPing ($to, $message, $base_uri, $verbose = false) {
    $ret = "<br/>\n";
    
    $to = trim($to);

    // fetch the user's profile
    $person = new MyProfile($to, $base_uri, SPARQL_ENDPOINT);
    $person->load();
    $profile = $person->get_profile();
    
    $to_name = $person->get_name();
    $to_email = $person->get_email();
    $pingback_service = $profile->get("http://purl.org/net/pingback/to");
    
    // set form data
    $source = $_SESSION['webid'];
        
    // parse the pingback form
    $config = array('auto_extract' => 0);
    $parser = ARC2::getSemHTMLParser($config);
    $parser->parse($pingback_service);
    $parser->extractRDF('rdfa');
    // load triples
    $triples = $parser->getTriples();

    // proceed only if the user has defined a pingback:to relation    
    if ($pingback_service != null) {
        if (sizeof($triples) > 0) {
            //echo "<pre>" . print_r($triples, true) . "</pre>\n";
            foreach ($triples as $triple) {
                // proceed only if we have a valid pingback resource
                if ($triple['o'] == 'http://purl.org/net/pingback/Container') {

                    $fields = array ('source' => $source,
                                    'target' => $to,
                                    'comment' => $message
                                );
                    
                    // Should really replace curl with an ajax call
                    //open connection to pingback service
                    $ch = curl_init();

                    //set the url, number of POST vars, POST data
                    curl_setopt($ch,CURLOPT_URL,$pingback_service);
                    curl_setopt($ch,CURLOPT_POST,count($fields));
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

                    //execute post
                    $return = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
                    //close connection
                    curl_close($ch);

                    if (($httpCode == '201') || ($httpCode == '202')) {
                        $ret .= success('Message delivered!');
                    } else {
                        $ret .= error('Something happened and I couldn\'t deliver the message!');
                        $ret .= "<p>Details:</p>\n";
                        $ret .= "</p>" . $return . "</p>\n";
                    }

                    break;
                }
            }
        } else {
            $ret .= "   <p>$pingback_service does not comply with semantic pingback standards! Showing the pingback service page instead.</p>\n";
            // show frame
            $ret .= "   <iframe src=\"$pingback_service\" width=\"100%\" height=\"300\">\n";
            $ret .= "   <p>Your browser does not support iframes.</p>\n";
            $ret .= "   </iframe>\n";
        }
    } else {
        // no valid pingback service found, fallback to AKSW 
        $ret .= "   <p>Could not find a pingback service for the given WebID. Here is a generic pingback service provided by http://pingback.aksw.org/.</p>\n";
        $ret .= "   <iframe src=\"http://pingback.aksw.org/\" width=\"100%\" height=\"300\">\n";
        $ret .= "   <p>Your browser does not support iframes.</p>\n";
        $ret .= "   </iframe>\n";
    }
    if ($verbose)
        return $ret;
}

/* Display voting counters and buttons
 * @webid = the user who votes
 * @message_id = the message the user votes on
 * returns HTML 
*/
function add_vote_buttons($message_id) {
    $ret = '';
    
    $yes_votes = get_yes_votes ($message_id);
    if ($yes_votes == null)
        $yes_votes = 0;
    $no_votes = get_no_votes ($message_id);
    if ($no_votes == null)
        $no_votes = 0;

    if (isset($_SESSION['webid'])) {
        // Check if the user has already cast a vote 
        $vote = has_voted($_SESSION['webid'], $message_id);
            
        $yes_link = "<a onClick=\"setVote('yes_" . $message_id . "', 'yes', '" . $message_id . "')\" style=\"text-decoration: none; cursor: pointer;\">";
        $no_link = "<a onClick=\"setVote('no_" . $message_id . "', 'no', '" . $message_id . "')\" style=\"text-decoration: none; cursor: pointer;\">";
        
        if ($vote == 1) {
            $yes_link = "<a style=\"text-decoration: none; cursor: pointer;\">";
        } else if ($vote == 0) {
            $no_link = "<a style=\"text-decoration: none; cursor: pointer;\">";
        }
    
        $ret .= $yes_link . "<img src=\"img/yes-vote.png\" /> <span id=\"yes_" . $message_id . "\">" . $yes_votes . "</span></a>\n";
        $ret .= " | ";
        $ret .= $no_link . "<img src=\"img/no-vote.png\" /> <span id=\"no_" . $message_id . "\">" . $no_votes . "</span></a>\n";
    } else {
        $ret .= "<img src=\"img/yes-vote.png\" /> <span id=\"yes_" . $message_id . "\">" . $yes_votes . "</span>\n";
        $ret .= " | ";
        $ret .= "<img src=\"img/no-vote.png\" /> <span id=\"no_" . $message_id . "\">" . $no_votes . "</span>\n";
    }
    
    return $ret;
}

/* Check if the user has already cast a vote 
 * @webid = the user who votes
 * @message_id = the message the user votes on
 * @check (true = return a boolean response / false = return the actual vote)
 * vote = 0 (no) / 1 (yes)
 * returns null or the corresponding vote for the user
*/
function has_voted ($webid, $message_id, $check = false) {
    $sql = "SELECT vote FROM votes WHERE ";
    $sql .= "webid='" . mysql_real_escape_string($webid) . "' ";
    $sql .= "AND message_id='" . mysql_real_escape_string($message_id) . "'";
    
    $result = mysql_query($sql);
    if (!$result) {
        die ("Database Error while checking if user has voted!");
    } else if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_row($result);
        mysql_free_result($result);
        return ($check == true) ? true : $row[0];
    } else {
        return ($check == true) ? false : null;
    }
}

function cast_vote ($webid, $message_id, $vote) {
    // insert a new vote if the user didn't vote already
    if (has_voted($webid, $message_id, true) == false) {
        $sql = "INSERT INTO votes SET ";
        $sql .= "webid='" . mysql_real_escape_string($webid) . "', ";
        $sql .= "timestamp='" . time() . "', ";
        $sql .= "message_id='" . mysql_real_escape_string($message_id) . "', ";
        $sql .= "vote='" . mysql_real_escape_string($vote) . "'";
         
        $result = mysql_query($sql);
        if (!$result) {
            return $sql;
        } else {
            // return the current counter for the given message
            mysql_free_result($result);

            if ($vote == 1)
                return get_yes_votes ($message_id);
            else if ($vote == 0)
                return get_no_votes ($message_id);
        }
    } else {
        // update the user's vote (yes -> no / no -> yes)
        $sql = "UPDATE votes SET ";
        $sql .= "timestamp='" . time() . "', ";
        $sql .= "vote='" . mysql_real_escape_string($vote) . "' ";
        $sql .= "WHERE webid='" . mysql_real_escape_string($webid) . "' ";
        $sql .= "AND message_id='" . mysql_real_escape_string($message_id) . "'";
        
        $result = mysql_query($sql);
        if (!$result) {
            return 0;
        } else {
            // return the current counter for the given message
            if ($vote == 1)
                return get_yes_votes ($message_id);
            else if ($vote == 0)
                return get_no_votes ($message_id);
        }
    }
}

/* return the number of yes votes for a given WebID and message
 * @message_id = the message the user votes on
 * vote = 0 (no) / 1 (yes)
 * returns null or the number of votes
*/
function get_yes_votes ($message_id) {
    $sql = "SELECT id FROM votes WHERE ";
    $sql .= "vote = 1 ";
    $sql .= "AND message_id='" . mysql_real_escape_string($message_id) . "'";
    
    $result = mysql_query($sql);
    if (!$result) {
        return null;
    } else {
        $votes = mysql_num_rows($result);
        mysql_free_result($result);
        return $votes;
    }
}

/* return the number of votes for a given WebID and message
 * @message_id = the message the user votes on
 * vote = 0 (no) / 1 (yes)
 * returns null or the number of votes
*/
function get_no_votes ($message_id) {
    $sql = "SELECT id FROM votes WHERE ";
    $sql .= "vote = 0 ";
    $sql .= "AND message_id='" . mysql_real_escape_string($message_id) . "'";
    
    $result = mysql_query($sql);
    if (!$result) {
        return null;
    } else {
        $votes = mysql_num_rows($result);
        mysql_free_result($result);
        return $votes;
    }
}


/* return the number of messages with the given parameters 
 * @webid = URI 
 * @new = 0 (viewed) / 1 (new)
*/
function get_msg_count ($webid, $new=1, $wall=null) {
    $sql = "SELECT id FROM pingback_messages WHERE ";
    $sql .= "to_uri='" . mysql_real_escape_string($webid) . "' ";
    $sql .= "AND new='" . mysql_real_escape_string($new) . "' ";
    if ($wall !== null)
        $sql .= "AND wall='" . mysql_real_escape_string($wall) . "' ";

    $result = mysql_query($sql);
    if (!$result) {
        return null;
    } else {
        $messages = mysql_num_rows($result);
        mysql_free_result($result);
        return $messages;
    }
}

/* delete a given message
 * @webid = the user performing the action
 * @message_id = the message the user votes on
 * returns an acknowledgement message
*/
function delete_message($webid, $message_id) {
    $webid = mysql_real_escape_string($webid);
    $message_id = mysql_real_escape_string($message_id);

    $reason = '';
    $ok = 1;

    // check if we are allowed to delete?
    $query = "SELECT id FROM pingback_messages WHERE (from_uri='" . $webid . "' OR to_uri='" . $webid . "') AND id='" . $message_id . "'";
    $result = mysql_query($query);
    if (!$result) {
        $ok = 0;
        $reason = 'The message has NOT been deleted [SQL error 1].';
    } else if (mysql_num_rows($result) > 0){
        $query = "DELETE FROM pingback_messages WHERE id='" . $message_id . "'";
        $result = mysql_query($query);
        if (!$result) {
            $ok = 0;
            $reason = 'The message has NOT been deleted [SQL error 2].';
        } else {
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
    return ($ok == 1) ? success($reason) : error($reason);
}

?>
