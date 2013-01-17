<?php
if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

// Return a list of WebIDs (people) I am friend of
function sparql_get_people_im_friend_of($webid, $endpoint) {
    $sparql = sparql_connect($endpoint);
    
    $query = 'SELECT DISTINCT ?webid WHERE {
                    ?webid a foaf:Person .
                    ?webid foaf:knows <' . trim(urldecode($webid)) . '> .
                    MINUS { ?webid a foaf:Person .
                           FILTER (regex(?webid, "nodeID", "i")) }
                    }';
    $result = $sparql->query($query);

    $webids = array();
    
    while ($row = $result->fetch_array()) {
        array_push($webids, $row['webid']);
    }
    
    return $webids;
}

// Use SPARQL to lookup a user
function sparql_lookup($string, $base_uri, $endpoint) {
    $ret = '';
    $ret .= "<table>\n";
    
    // check if we have a WebID uri in the search input
    if (strpos(urldecode($string), '#') === false) {
        // search the local cache for a match
        $sparql = sparql_connect($endpoint);

        // Try to match against the name, nickname or webid.
        $query = 'SELECT DISTINCT ?webid WHERE {
                    ?webid foaf:name ?name .
                    ?webid foaf:nick ?nick .
                    FILTER (regex(?name, "' . $string . '", "i") || regex(?nick, "' . $string . '", "i") || regex(?webid, "' . $string . '", "i"))
                    MINUS { ?webid a foaf:Person .
                           FILTER (regex(?webid, "nodeID", "i")) .
                           FILTER (regex(?webid, "_:", "i")) }
                    }';
        $result = $sparql->query($query);

        if(!$result)  
            $ret .= error(sparql_errno() . ": " . sparql_error());
            
        
        while ($row = $result->fetch_array($result)) {
            $ret .= viewShortInfo ($row['webid'], $_SESSION['webid'], $base_uri, $endpoint);
        }
    } else {
        // use the WebID source
        $ret .= viewShortInfo ($string, $_SESSION['webid'], $base_uri, $endpoint);
    }
    $ret .= "</table>\n";
    return $ret;
}

// Display several key information about a user (image, name, nick, email, homepage)
function viewShortInfo ($webid, $me, $base_uri, $endpoint) {
    // fetch info for webid
    $ret = '';
    
    $person = new MyProfile($webid, $base_uri, $endpoint);
    $person->load();
    $profile = $person->get_profile();

    // find if he has me in his list of foaf:knows!
    $has_me = false;
    if (in_array($me, $profile->all('foaf:knows')))
        $has_me = true;
      
    // check if the user has subscribed to local messages
    $is_subscribed = (strlen($person->get_hash()) > 0) ? true : false;

    // start populating array
    $friend = array('webid' => (string)$webid,
        'img' => $person->get_picture(),
        'name' => (string)$profile->get("foaf:name"),
        'nick' => (string)$profile->get("foaf:nick"),
        'email' => $profile->get("foaf:mbox"),
        'blog' => $profile->get("foaf:weblog"),
        'pingback' => $profile->get("http://purl.org/net/pingback/to"),
        'hash' => $person->get_hash(),
        'hasme' => $has_me
    );
    if (isset($new)) {
        $friend['new'] = $new;
    }

    $ret .= "<table>\n";
    $ret .= "<tr bgcolor=\"\"><td>\n";
    $ret .= "<table><tr>\n";
    $ret .= "<td width=\"70\" style=\"vertical-align: top; padding: 10px;\">\n";
    $ret .= "<div align=\"left\"><a href=\"view.php?webid=" . urlencode($friend['webid']) . "\" target=\"_blank\">";
    $ret .= "<img title=\"" . $friend['name'] . "\" alt=\"" . $friend['name'] . ".\" width=\"64\" src=\"" . $friend['img'] . "\" />";
    $ret .= "</a></div>\n";
    $ret .= "</td>\n";

    $ret .= "<td><table>\n";
    if ($friend['name'] != null)
        $ret .= "<tr><td><strong>" . $friend['name'] . "</strong>\n";
    else
        $ret .= "<tr><td><strong>Anonymous</strong>\n";
            
    if ($friend['nick'] != null)
        $ret .= "''" . $friend['nick'] . "''";
    $ret .= "</td></tr>\n";

    if ($friend['hasme'] == true) 
        $ret .= "<tr><td><div style=\"color:#60be60;\">Has you as friend.</div></td></tr>\n";

    //$ret .= "<tr><td>&nbsp;</td></tr>\n";

    if ($friend['email'] != null)
        $ret .= "<tr><td>Email: <a href=\"" . $friend['email'] . "\">" . clean_mail($friend['email']) . "</a></td></tr>\n";

    if ($friend['blog'] != null)
        $ret .= "<tr><td>Blog:<a href=\"" . $friend['blog'] . "\">" . $friend['blog'] . "</a></td></tr>\n";

    $ret .= "<tr><td>WebID: <a href=\"view.php?webid=" . urlencode($friend['webid']) . "\">" . $friend['webid'] . "</a></td></tr>\n";
    $ret .= "</table>\n";

    $ret .= "<br/><table>\n";
    $ret .= "<tr>\n";

    // add or remove friends if we have them in our list
    if ((isset($_SESSION['webid'])) && (webid_is_local($_SESSION['webid']))) {
        if ($_SESSION['myprofile']->is_friend($webid)) {
        // remove friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"POST\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"delfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"del_webid\" value=\"" . $friend['webid'] . "\">\n";
            $ret .= "<input src=\"img/actions/remove.png\" type=\"image\" title=\"Remove friend\" name=\"submit\" value=\" Remove \">\n";
            $ret .= "</form></td>\n";
        } else {
        // add friend
            $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"POST\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"addfriend\">\n";
            $ret .= "<input type=\"hidden\" name=\"add_webid\" value=\"" . $friend['webid'] . "\">\n";
            $ret .= "<input src=\"img/actions/add.png\" type=\"image\" title=\"Add friend\" name=\"submit\" value=\" Add \">\n";
            $ret .= "</form></td>\n";
        }
    }

    // send messages using the pingback protocol 
    if ($friend['pingback'] != null) {
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"messages.php\" method=\"POST\">\n";
        $ret .= "<input type=\"hidden\" name=\"new\" value=\"true\">\n";
        $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $friend['webid'] . "\">\n";
        $ret .= "<input src=\"img/actions/message.png\" type=\"image\" title=\"Send a message\" name=\"submit\" value=\" Message \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
    }

    // more functions if the user has previously subscribed to the local services
    if ($is_subscribed) {
        // Post on the user's wall
        $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"wall.php\" method=\"POST\">\n";
        $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . $friend['hash'] . "\">\n";
        $ret .= "<input src=\"img/actions/wall.png\" type=\"image\" title=\"View posts\" name=\"submit\" value=\" Wall \" onclick=\"this.form.target='_blank';return true;\">\n";
        $ret .= "</form></td>\n";
    }

    $ret .= "<td style=\"padding-right: 10px; float: left;\"><form action=\"friends.php\" method=\"GET\">\n";
    $ret .= "<input type=\"hidden\" name=\"webid\" value=\"" . $friend['webid'] . "\">\n";
    $ret .= "<input type=\"hidden\" name=\"me\" value=\"" . $me . "\">\n";
    $ret .= "<input src=\"img/actions/friends.png\" type=\"image\" title=\"View friends\" name=\"submit\" value=\" Friends \">\n";
    $ret .= "</form></td>\n";
    $ret .= "</tr></table></p>\n";

    $ret .= "</td>\n";
    $ret .= "</tr></table>\n";

    $ret .= "</td></tr>\n";
    $ret .= "</table>\n";
    
    return $ret;
}

// Returns http links for possible URIs found in a text
// Also adds rel=nofollow attribute to indicate to the search engines that we don't "trust" the link
function put_links($text) {
    return preg_replace('#[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]#', '<a href="\0" rel="nofollow">\0</a>', $text);
}

// verify that an email is valid
function checkEmail($str)
{
	return preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $str);
}

// Display a pretty visual success message
function success($text) {
    $ret = "<div class=\"ui-widget\">\n";
    $ret .= "<div class=\"notification-success ui-corner-all\">\n";
    $ret .= "<strong>Success!</strong> " . $text;
    $ret .= "</div></div>\n";

    return $ret;
}

// Display a pretty visual warning message
function warning($text) {
    $ret = "<div class=\"ui-widget\">\n";
    $ret .= "<div class=\"notification-warning ui-corner-all\">\n";
    $ret .= "<strong>Warning!</strong> " . $text;
    $ret .= "</div></div>\n";

    return $ret;
}

// Display a pretty visual error message
function error($text) {
    $ret = "<div class=\"ui-widget\">\n";
    $ret .= "<div class=\"notification-error ui-corner-all\">\n";
    $ret .= "<strong>Error!</strong> " . $text;
    $ret .= "</div></div>\n";

    return $ret;
}

// compute and return an etag for Wall
function compute_etag($time) {
    return md5($time);    
}

// update all etags for a given hash (wall or users)
function update_etags($time, $to_hash) {
    $query = "UPDATE pingback_messages SET etag='" . mysql_real_escape_string(compute_etag($time)) . "' " .
                "WHERE to_hash='" . mysql_real_escape_string($to_hash) . "'";
    $result = mysql_query($query);
    if (!$result) {
        return error("Cannot update etags for user " . $to_hash . ".");
    } else {
        mysql_free_result($result);
        return ''; // tbd
    }
}

// get latest date and etag corresponding to the given user (wall or users)
function get_etag($to_hash) {
    $time = time();

    $query = "SELECT max(updated) AS date, etag FROM pingback_messages WHERE to_hash='" . mysql_real_escape_string($to_hash) . "'";
        $result = mysql_query($query);
    if (!$result) {
        return null;
    } else {
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return $row;
    }
}

// return true if user has subscribed to local services
function is_subscribed($webid) {
    $query = "SELECT id FROM pingback WHERE webid='" . mysql_real_escape_string($webid) . "'";
    $result = mysql_query($query);
    if (!$result) {
        return null;       
    } else {
        if (mysql_num_rows($result) > 0) {
            mysql_free_result($result);
            return true;
        } else {
            mysql_free_result($result);
            return false;
        }
    }        
}

// return true if user has subscribed to local services and allows emails
function is_subscribed_email($webid) {
    $query = "SELECT id FROM pingback WHERE webid='" . mysql_real_escape_string($webid) . "' AND email='1'";
    $result = mysql_query($query);
    if (!$result) {
        return null;
    } else {
        if (mysql_num_rows($result) > 0) {
            mysql_free_result($result);
            return true;
        } else {
            mysql_free_result($result);
            return false;
        }
    }        
}

// get information about a user's subscription
function get_sub_by_webid($webid) {
    $query = "SELECT id, feed_hash, user_hash FROM pingback WHERE webid='" . mysql_real_escape_string($webid) . "'";
    $result = mysql_query($query);
    if (!$result) {
        return null;       
    } else {
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return $row;
    }    
}    

// return the WebID URI of a user based on the hash value provided
function get_webid_by_hash($hash) {
    $query = "SELECT webid FROM pingback WHERE user_hash='" . mysql_real_escape_string($hash) . "'";
    $result = mysql_query($query);

    if (!$result) {
        return null;       
    } else {
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return $row['webid'];
    }
}

// return the feed hash of a user based on the hash value provided
function get_feed_by_hash($hash) {
    $query = "SELECT feed_hash FROM pingback WHERE user_hash='" . mysql_real_escape_string($hash) . "'";
    $result = mysql_query($query);

    if (!$result) {
        return null;       
    } else {
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return $row['feed_hash'];
    }
}

function count_msg_by_hash($hash) {
    $query = "SELECT id FROM pingback_messages WHERE to_hash='" . mysql_real_escape_string($hash) . "' AND wall='1'";
    $result = mysql_query($query);

    if (!$result) {
        return null;       
    } else {
        $total = mysql_num_rows($result);
        mysql_free_result($result);
        return $total;
    }
}

// return a clean email address without mailto: component
function clean_mail($email) {
    $ret = explode(':', $email);
    return $ret[1];
}

// Check if the user is authenticated or not
function check_auth($idp, $page_uri) {
    if (!isset($_SESSION['webid'])) {
        include 'header.php';

        echo "<div class=\"content relative shadow clearfix main\">\n";
        echo "<p><font style=\"font-size: 1.3em;\">You must be authenticated in order to access this page! Click <a href=\"" . $idp . $page_uri . "\">here</a> to authenticate with your WebID.</font></p>\n";
        echo "<div class=\"clear\"></div>\n";
        echo "</div>\n";

        include 'footer.php';
        exit;
    }
}

// return an array with all feeds belonging to a person
function get_rss_links($person, $graph) {
    $feeds = array();
    if ($person->get("foaf:rssFeed") != null) {
        foreach ($person->all('foaf:rssFeed') as $rss) {
            // each feed is a separate resource
            $contres = $graph->resource($rss);
            $feeds[] = $contres->get('http://purl.org/rss/1.0/link'); 
        }
        return $feeds;
    } else {
        return false;
    }
}

// RSS reader
function rss_reader($url) {
    $ret = '';
    $rss = simplexml_load_file($url);
    
    if ($rss) {
        $ret .= "<h1><a href=\"" . $rss->channel->link . "\">" . $rss->channel->title . "</a></h1>\n";
        $ret .= "<dd>" . $rss->channel->pubDate . "</dd>\n";
        if ($rss->channel->item)
            $items = $rss->channel->item;
        else
            $items = $rss->item;

        $i = 0;
        foreach($items as $key => $item) {
            // stop at 5 items per feed
            if ($i == 5)
                break;

            $title = $item->title;
            $link = $item->link;
            $published_on = $item->pubDate;
            $description = $item->description;
			if (strlen($description) > 1300)
				$description = substr($description, 0, 1300) . "...<br/><a href=\"" . $link . "\">Read more »</a>\n";
            $ret .= "<h3 class=\"profileHeaders\"><b><a href=\"" . $link . "\">" . $title . "</a></b></h3>\n";
            if (strlen($published_on) > 0)
                $ret .= "<span>(" . $published_on . ")</span>\n";
            $ret .= "<p>" . $description . "</p>\n";
            $i++;
        }
    }
    return $ret;
}

// check if the user has a local profile or not
function is_local($webid, $host) {
    if (strpos($webid, $host) !== false)
        return true;
    else
        return false;
}

// checks if the webid is a local and return the corresponding account name
function webid_is_local($webid) {
    if ($loc = strstr($webid, $_SERVER['SERVER_NAME']))
        return $loc;
    else
        return false;
}

// get the local path to the webid file
function webid_get_local_path($webid) {
        // verify if it's local or not
        if ($loc = webid_is_local($webid)) {
            $path = explode('/', $loc);
            $path = $path[1] . "/" . $path[2];
            return $path;
        } else {
            return false;
        }
}

// recursively delete a dir
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
            }
        }
        reset($objects);
        rmdir($dir);
        return true;
    } else {
        return false;
    }
}

// create a x509 certificate
function create_identity_x509($countryName,  $stateOrProvinceName, $localityName, $organizationName, $organizationalUnitName, $commonName, $emailAddress, $foafLocation, $pubkey, $conf, $pass) {
    // Remove any whitespace in teh supplied SPKAC
    $keyreq = "SPKAC=".str_replace(str_split(" \t\n\r\0\x0B"), '', $pubkey);

    $SAN="";
		
    // Create the DN for the openssl call
    if ($countryName)
        $keyreq .= "\ncountryName=".$countryName;
	
    if ($stateOrProvinceName)
        $keyreq .= "\nstateOrProvinceName=".$stateOrProvinceName;

    if ($localityName)
        $keyreq .= "\nlocalityName=".$localityName;

    if ($organizationName)
        $keyreq .= "\norganizationName=".$organizationName;

    if ($organizationalUnitName)
        $keyreq .= "\n0.OU=".$organizationalUnitName;

    if ($commonName)
        $keyreq .= "\nCN=".$commonName;
    if ($emailAddress) {
        $keyreq .= "\nemailAddress=".$emailAddress;
        $SAN="email:" . $emailAddress . ",";
    }
        
    // Setup the contents of the subjectAltName
    if ($foafLocation) {
        foreach($foafLocation as $key => $val) {
            if (strlen($val) > 0) {
                $SAN .= "URI:$val";
                if (strlen($foafLocation[$key+1]) > 0)
                    $SAN .= ",";
            }
        }
    }

    // Export the subjectAltName to be picked up by the openssl.cnf file
    if ($SAN)
        putenv("SAN=$SAN");
	
    // Create temporary files to hold the input and output to the openssl call.
    $tmpSPKACfname = "/tmp/SPK" . md5(time().rand());
    $tmpCERTfname  = "/tmp/CRT" . md5(time().rand());

    // Write the SPKAC and DN into the temporary file
    $handle = fopen($tmpSPKACfname, "w");
    fwrite($handle, $keyreq);
    fclose($handle);

    // TODO - This should be more easily configured
	$command = "openssl ca -config $conf -verbose -batch -notext -spkac $tmpSPKACfname -out $tmpCERTfname -passin pass:'" . $pass . "' 2>&1";

	// Run the command;
	$output = shell_exec($command);
	//echo $output;

	// TODO - Check for failures on the command
	if (preg_match("/Data Base Updated/", $output)==0)
	{
		echo "Failed to create X.509 Certificate<br><br>";
        //echo $keyreq."<br/>\n";
		//echo "<pre>";
		//echo $output;
		//echo "</pre>";
		// Remove unneeded files    
        //unlink($tmpSPKACfname);
        //unlink($tmpCERTfname);
	
		return;
	} 
	// Delete the temporary SPKAC file
	unlink($tmpSPKACfname);

    return $tmpCERTfname;
}
	
// Send the p12 encoded SSL certificate
// Notice: it is IMPERATIVE that no html data gets transmitted to the user before the header is sent!
function download_identity_x509($certLocation, $foafLocation) {
	$length = filesize($certLocation);	
	header('Last-Modified: ' . date('r+b'));
	header('Accept-Ranges: bytes');
	header('Content-Length: ' . $length);
	header('Content-Type: application/x-x509-user-cert');
	readfile($certLocation);

	// Delete the temporary CRT file
	unlink($certLocation);

	exit;
}

// send email using php
function send_mail($from,$to,$subject,$body)
{
	$headers = '';
	$headers .= "From: $from\n";
	$headers .= "Reply-to: $from\n";
	$headers .= "Return-Path: $from\n";
	$headers .= "Message-ID: <" . md5(uniqid(time())) . "@" . $_SERVER['SERVER_NAME'] . ">\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Date: " . date('r', time()) . "\n";

	mail($to,$subject,$body,$headers);
}

function mail_utf8($to, $from_user, $from_email, $subject = '(No subject)', $message = '')
{ 
  $from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
  $subject = "=?UTF-8?B?".base64_encode($subject)."?=";

  $headers = "From: $from_user <$from_email>\r\n". 
           "MIME-Version: 1.0" . "\r\n" . 
           "Content-type: text/html; charset=UTF-8" . "\r\n"; 

 return mail($to, $subject, $message, $headers); 
}

// get the primary topic of a profile 
function getPrimaryTopic ($graph) {
    foreach ($graph->allOfType('foaf:PersonalProfileDocument') as $ppd) {
        $pt = $ppd->get('foaf:primaryTopic');
        break;
    }
    if ($pt)
        return $pt;
    else
        return;
}

// Uses Ajax calls to load a user's list of friends asynchronously
function viewFriends($me) {
    // foaf:knows
    if ($me->get("foaf:knows") != null) {
        $friends = implode(',', $profile->all('foaf:knows'));        

        // show something if there are friends for this webid
        if (strlen($friends) > 0) {
	        $ret .= '<script type="text/javascript">
                var list = "' . (string)$friends . '";
                var uris = list.split(","); 
                
                // Create placeholders for each contact info
                for (i = 0; i < uris.length; i++) {
                    var webid = uris[i];
                    $("#content").append("<div id=\"person_"+i+"\"></div>");
                }
                </script>';
                
	        $ret .= '<script type="text/javascript">
                var list = "' . (string)$friends . '";
                var uris = list.split(","); 
                
                if (list.length > 0) {
                    for (i = 0; i < uris.length; i++) {
                        var webid = uris[i];
                        //var hash = webid.slice(webid.indexOf("#"));
                    
                        // script URI that we will call for each user
			            var addr = "load.php?webid="+encodeURIComponent(webid)+"&me="+encodeURIComponent("' . $_SESSION['webid'] . '");            
	
                        $("#person_"+i).load(addr);
                    }
                }
                </script>';
        } else {
            $ret .= "You do not have any friends.\n";
        }          
    }
    return $ret;
}

// Print the profile page in a prettier way
// @graph - an EasyRdf object
// @me - the user requesting the view
// @webid - the webid of the user we're viewing
// @base_uri - local host FQDN
function viewProfile($graph, $profile, $webid, $base_uri) {
    // main table with one row which holds data in the left cell, and pics in the right cell
    $ret = "";
    $ret .= "<table align=\"center\" border=\"0\">\n";
    $ret .= "<tr><td>";

    $ret .= "<table border=\"0\">";

    // identity    
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"profileHeaders\">Identity: </h3>";
    $ret .= "<a href=\"" . $webid . "\">$webid</a>";
    $ret .= "</td>";
    $ret .= "</tr>\n";

    // name    
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"profileHeaders\">Full name: </h3>";
    $ret .= "";
    if ($profile->get("foaf:name") != null) {
        $ret .= $profile->get("foaf:name");
    } else {
        $first = $profile->get('foaf:givenName');
        $last = $profile->get('foaf:familyName');

        $name = ''; 
        if ($first != null)
            $name .= $first . ' ';
        if ($last != null)
            $name .= $last;
        if (strlen($name) == 0)
            $ret .= $name;
        else
            $ret .= 'Anonymous';
    }
    $ret .= "";
    $ret .= "</td>";
    $ret .= "</tr>\n";
    
    // nickname
    if ($profile->get("foaf:nick") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Nickname: </h3>";
        $ret .= "<dd>" . $profile->get("foaf:nick") . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }    

    // b-day    
    if ($profile->get("foaf:birthday") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Birthday: </h3>";
        $ret .= "<dd>" . $profile->get("foaf:birthday") . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // contact info
    if ($profile->get("http://www.w3.org/2000/10/swap/pim/contact#home") != null) {
        $ret .= "<tr valign=\"top\">";
        foreach ($profile->all('http://www.w3.org/2000/10/swap/pim/contact#home') as $cont) {
            $ret .= "<td><h3 class=\"profileHeaders\">Contact info: </h3>";
            $contres = $graph->resource($cont);
            $address = $graph->resource($contres->get('http://www.w3.org/2000/10/swap/pim/contact#address'));
            
            $ret .= "<dd><h3 class=\"profileHeaders\">Street</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#street') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"profileHeaders\">City</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#city') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"profileHeaders\">Zip code</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#postalCode') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"profileHeaders\">Country</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#country') . "</dd>\n";   

            $ret .= "</td>";
        }
        $ret .= "</tr>\n";
    }
    
    // email   
    if ($profile->get("foaf:mbox") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Email: </h3>";
        foreach ($profile->all("foaf:mbox") as $mail) {
            $ret .= "<dd><a href=\"".$mail."\">".clean_mail($mail)."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // mbox_sha1   
    if ($profile->get("foaf:mbox_sha1sum") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">mbox sha1sum: </h3>";
        $ret .= "<dd>" . $profile->join("foaf:mbox_sha1sum", "<br>") . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // sameAs
    if ($profile->get("owl:sameAs") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Additional profiles: </h3>";
        foreach ($profile->all("owl:sameAs") as $sameAs) {
            $same = $graph->resource($sameAs);
            $ret .= "<dd><a href=\"".$same."\">".$same."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // homepage
    if ($profile->get("foaf:homepage") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Homepage: </h3>";
        foreach ($profile->all("foaf:homepage") as $homepage) {
            $home = $graph->resource($homepage);
            $ret .= "<dd><a href=\"".$home."\">".$home."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // blogs
    if ($profile->get("foaf:weblog") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Blog: </h3>";
        foreach ($profile->all("foaf:weblog") as $blog) {
            $blog = $graph->resource($blog);
            $ret .= "<dd><a href=\"".$blog."\">".$blog."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
        
    // workplaceHomepage  
    if ($profile->get("foaf:workplaceHomepage") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Workplace Homepage: </h3>";
        foreach ($profile->all("foaf:workplaceHomepage") as $workpage) {
            $work = $graph->resource($workpage);
            $ret .= "<dd><a href=\"".$work."\">".$work."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // schoolHomepage
    if ($profile->get("foaf:schoolHomepage") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">School Homepage: </h3>";
        foreach ($profile->all("foaf:schoolHomepage") as $schoolpage) {
            $school = $graph->resource($schoolpage);
            $ret .= "<dd><a href=\"".$school."\">".$school."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
        
    // current proj
    if ($profile->get("foaf:currentProject") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Current projects: </h3>";
        foreach ($profile->all("foaf:currentProject") as $currproj) {
            $proj = $graph->resource($currproj);
            $ret .= "<dd><a href=\"".$proj."\">".$proj."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // past proj
    if ($profile->get("foaf:pastProject") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Past projects: </h3>";
        foreach ($profile->all("foaf:pastProject") as $pastproj) {
            $proj = $graph->resource($pastproj);
            $ret .= "<dd><a href=\"".$proj."\">".$proj."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // web apps
    if ($profile->get("socweb:webapp") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Web apps: </h3>";
        foreach ($profile->all("socweb:webapp") as $webapp) {
            $app = $graph->resource($webapp);
            $ret .= "<dd><a href=\"".$app."\">".$app."</a></dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // rss feeds
    if ($profile->get("foaf:rssFeed") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">RSS feeds: </h3>";
        foreach ($profile->all('foaf:rssFeed') as $rss) {
			// each feed is a separate resource
            $contres = $graph->resource($rss);
            if ($contres->get('http://purl.org/rss/1.0/title') != null)          
                $ret .= "<dd><h3 class=\"profileHeaders\">Title</h3> " . $contres->get('http://purl.org/rss/1.0/title') . "</dd>\n";
            if ($contres->get('http://purl.org/rss/1.0/link') != null)
                $ret .= "<dd><h3 class=\"profileHeaders\">Link</h3> <a href=\"" . $contres->get('http://purl.org/rss/1.0/link') . "\">" . $contres->get('http://purl.org/rss/1.0/link') . "</a></dd>\n";
            if ($contres->get('http://purl.org/rss/1.0/description') != null)
                $ret .= "<dd><h3 class=\"profileHeaders\">Description</h3> " . $contres->get('http://purl.org/rss/1.0/description') . "</dd>\n";
            $ret .= "<br/>\n";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // holds account
    if ($profile->get("foaf:holdsAccount") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Holds account: </h3>";
        foreach ($profile->all('foaf:holdsAccount') as $accounts) {
            // each account is a separate resource
            $account = $graph->resource($accounts);
            if ($account->get("rdfs:label") != null)
            $ret .= "<dd>" . $account->get("rdfs:label") . "</dd>";
            if ($account->get("foaf:accountProfilePage") != null)
                $ret .= "<dd><a href=\"".$account->get("foaf:accountProfilePage")."\">".$account->get("foaf:accountProfilePage")."</a></dd>\n";
            if ($account->get("foaf:accountServiceHomepage") != null)
                $ret .= "<dd><a href=\"".$account->get("foaf:accountServiceHomepage")."\">".$account->get("foaf:accountServiceHomepage")."</a></dd>";
            $ret .= "<br/>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
     
    // interests
    if ($profile->get("foaf:interest") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Interests: </h3>";
        foreach ($profile->all('foaf:interest') as $interests) {
            // each interest is a separate resource
            if (! $interests->isBnode()) {
                $interest = $graph->resource($interests);
                $label = ($interest->label() == null) ? $interest->__toString() : $interest->label();
                $ret .= "<dd><a href=\"" . $interest->__toString() . "\">" . $label . "</a></dd>\n";
            }
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // foaf:knows
    if ($profile->get("foaf:knows") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td align=\"left\"><br/><h3 class=\"profileHeaders\">Knows: </h3></td>";
        $ret .= "</tr>\n";
        $ret .= "<tr>\n";
        $ret .= "<td id=\"knows\">\n";
       
        $friends = implode(',', $profile->all('foaf:knows'));
 
        // show something if there are friends for this webid
        if (strlen($friends) > 0) {
	        $ret .= '<script type="text/javascript">
                var list = "' . (string)$friends . '";
                var uris = list.split(","); 
                
                // Create placeholders for each contact info
                for (i = 0; i < uris.length; i++) {
                    var webid = uris[i];
                    $("#knows").append("<div id=\"person_"+i+"\"></div>");
                }
                </script>';
                
	        $ret .= '<script type="text/javascript">
                var list = "' . (string)$friends . '";
                var uris = list.split(","); 
                
                if (list.length > 0) {
                    for (i = 0; i < uris.length; i++) {
                        var webid = uris[i];
                        //var hash = webid.slice(webid.indexOf("#"));
                    
                        // script URI that we will call for each user
			            var addr = "load.php?webid="+encodeURIComponent(webid)+"&me="+encodeURIComponent("' . $_SESSION['webid'] . '");            
	
                        $("#person_"+i).load(addr);
                    }
                }
                </script>';
        } else {
            $ret .= "You do not have any friends.\n";
        }          

        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // public keys
    if ($profile->get("wot:hasKey") != null) {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"profileHeaders\">Public key: </h3>";
        foreach ($profile->all('wot:hasKey') as $keys) {
            // each key is a separate resource
            $key = $graph->resource($keys);
    
            if ($key->get("wot:fingerprint") != null)
                $ret .= "<dd><h3 class=\"profileHeaders\">Fingerprint:</h3></dd><dd>" . $key->get("wot:fingerprint") . "</dd>\n";
            if ($key->get("wot:hex_id") != null)
                $ret .= "<dd><h3 class=\"profileHeaders\">Hex ID:</h3></dd><dd>" . $key->get("wot:hex_id") . "</dd>";
            $ret .= "<br/>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // certificates
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"profileHeaders\">Certificates: </h3>";
    foreach ($graph->allOfType('cert:RSAPublicKey') as $cert) {
        // get corresponding resources for modulus and exponent
        if ($cert->isBnode() == true) {
            $bnode = $graph->resource($cert);
 
            $hex = $bnode->get('cert:modulus');
            $exp = $bnode->get('cert:exponent');
        } else { 
            $hex = $cert->get('cert:modulus');
            $exp = $cert->get('cert:exponent');
        }

        $ret .= "<dd><h3>Modulus:</h3></dd><dd>" . wordwrap($hex, 70, "<br />\n", true) . "</dd><br/>\n";
        $ret .= "<dd><h3>Public exponent:</h3></dd><dd>" . $exp . "</dd>\n";
        $ret .= "<br/>\n";
    }

    $ret .= "</tr>\n";
    $ret .= "</table>\n";
    
    // load all pictures
    $ret .= "</td>";
    $ret .= "<td valign=\"top\">";
    foreach ($profile->all("foaf:img") as $picture) {
        $ret .= "<img class=\"r5\" width=\"200\" src=\"" . $picture . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    foreach ($profile->all("foaf:depiction") as $picture) {
        $ret .= "<img class=\"r5\" width=\"200\" src=\"" . $picture . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    foreach ($profile->all("foaf:logo") as $logo) {
        $ret .= "<img class=\"r5\" width=\"200\" src=\"" . $logo . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    $ret .= "</td>\n";
    $ret .= "</tr>\n";
    $ret .= "</table>\n";
    
    return $ret;
}

?>
