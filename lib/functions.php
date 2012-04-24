<?php

if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

$NAMESPACES = "
	PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> .
  	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
  	PREFIX rdfa: <http://www.w3.org/1999/xhtml/vocab#> .
  	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
	PREFIX owl: <http://www.w3.org/2002/07/owl#> .
  	PREFIX foaf: <http://xmlns.com/foaf/0.1/> .
  	PREFIX dc: <http://purl.org/dc/elements/1.1/> . 
  	PREFIX dcterms: <http://purl.org/dc/terms/> .
  	PREFIX skos: <http://www.w3.org/2004/02/skos/core#> .
  	PREFIX sioc: <http://rdfs.org/sioc/ns#> .
  	PREFIX sioct: <http://rdfs.org/sioc/types#> .
  	PREFIX xfn: <http://gmpg.org/xfn/11#> .
  	PREFIX twitter: <http://twitter.com/> .
  	PREFIX rss: <http://purl.org/rss/1.0/> .
  	PREFIX wot: <http://xmlns.com/wot/0.1/> .
    PREFIX rsa: <http://www.w3.org/ns/auth/rsa#> .
    PREFIX cert: <http://www.w3.org/ns/auth/cert#> .
";

// Returns http links for possible URIs found in a text
// Also adds rel=nofollow attribute to indicate to the search engines that we don't "trust" the link
function put_links($text) {
    return ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\" rel=\"nofollow\">\\0</a>", $text);
}

// verify that an email is valid
function checkEmail($str)
{
	return preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $str);
}

// Display a pretty visual success message
function success($text) {
    $ret = "<br/><div class=\"ui-widget\" style=\"position:relative; width: 820px;\">\n";
    $ret .= "<div class=\"ui-state-highlight ui-corner-all\">\n";
    $ret .= "<p><span class=\"ui-icon ui-icon-info\" style=\"float: left; margin-right: .3em;\"></span>\n";
    $ret .= "<strong>Success!</strong> " . $text;
    $ret .= "</div></div>\n";

    return $ret;
}

// Display a pretty visual error message
function error($text) {
    $ret = "<br/><div class=\"ui-widget\" style=\"position:relative; width: 820px;\">\n";
    $ret .= "<div class=\"ui-state-error ui-corner-all\">\n";
    $ret .= "<p><span class=\"ui-icon ui-icon-alert\" style=\"float: left; margin-right: .3em;\"></span>\n";
    $ret .= "<strong>Error!</strong> " . $text;
    $ret .= "</div></div>\n";

    return $ret;
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

/* return the number of messages with the given parameters 
 * @webid = URI 
 * @new = 0 (viewed) / 1 (new)
*/
function get_msg_count($webid, $new=1, $wall) {
    $sql = "SELECT id FROM pingback_messages WHERE ";
    $sql .= "to_uri='" . mysql_real_escape_string($webid) . "' ";
    $sql .= "AND new='" . mysql_real_escape_string($new) . "' ";
    if (isset($wall))
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

// return a clean email address without mailto: component
function clean_mail($email) {
    $ret = explode(':', $email);
    return $ret[1];
}

// Check if the user is authenticated or not
function check_auth($idp, $url) {
    if (!isset($_SESSION['webid'])) {
        include 'header.php';
        
        echo "<div class=\"container\">\n";
        echo "<p><font style=\"font-size: 1.3em;\">You must be authenticated in order to access this page! Click <a href=\"" . $idp . "" . $url . "\">here</a> to authenticate with your WebID.</font></p>\n";
        echo "<div class=\"clear\"></div>\n";
        echo "</div>\n";
    
        include 'footer.php';
        exit;
    }
}

// return an array with all feeds belonging to a person
function get_rss_links($person, $graph) {
    $feeds = array();
    if ($person->get("foaf:rssFeed") != '[NULL]') {
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
            $ret .= "<h3 class=\"demoHeaders\"><b><a href=\"" . $link . "\">" . $title . "</a></b></h3>\n";
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
        echo $keyreq."<br/>\n";
		echo "<pre>";
		echo $output;
		echo "</pre>";
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

// Print the profile page in a prettier way
function dumpHTML($graph, $me, $webid) {   
    // main table with one row which holds data in the left cell, and pics in the right cell
    $ret = "";
    $ret .= "<table align=\"center\" border=\"0\">\n";
    $ret .= "<tr><td>";

    $ret .= "<table border=\"0\">";

    // identity    
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"demoHeaders\">Identity: </h3>";
    $ret .= "<a href=\"" . $webid . "\">$webid</a>";
    $ret .= "</td>";
    $ret .= "</tr>\n";

    // name    
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"demoHeaders\">Full name: </h3>";
    $ret .= "";
    if ($me->get("foaf:name") != '[NULL]') {
        $ret .= $me->get("foaf:name");
    } else {
        $first = $me->get('foaf:givenName');
        $last = $me->get('foaf:familyName');

        $name = ''; 
        if ($first != '[NULL]')
            $name .= $first . ' ';
        if ($last != '[NULL]')
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
    if ($me->get("foaf:nick") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Nickname: </h3>";
        $ret .= "<dd>" . $me->get("foaf:nick") . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }    

    // b-day    
    if ($me->get("foaf:birthday") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Birthday: </h3>";
        $ret .= "<dd>" . $me->get("foaf:birthday") . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // contact info
    if ($me->get("http://www.w3.org/2000/10/swap/pim/contact#home") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        foreach ($me->all('http://www.w3.org/2000/10/swap/pim/contact#home') as $cont) {
            $ret .= "<td><h3 class=\"demoHeaders\">Contact info: </h3>";
            $contres = $graph->resource($cont);
            $address = $graph->resource($contres->get('http://www.w3.org/2000/10/swap/pim/contact#address'));
            
            $ret .= "<dd><h3 class=\"demoHeaders\">Street</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#street') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"demoHeaders\">City</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#city') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"demoHeaders\">Zip code</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#postalCode') . "</dd>\n";   
            $ret .= "<dd><h3 class=\"demoHeaders\">Country</h3> " . $address->get('http://www.w3.org/2000/10/swap/pim/contact#country') . "</dd>\n";   

            $ret .= "</td>";
        }
        $ret .= "</tr>\n";
    }
    
    // email   
    if ($me->get("foaf:mbox") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Email: </h3>";
        foreach ($me->all("foaf:mbox") as $mail)
            $ret .= "<dd>" . clean_mail($mail) . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // mbox_sha1   
    if ($me->get("foaf:mbox_sha1sum") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">mbox sha1sum: </h3>";
        $ret .= "<dd>" . $me->all("foaf:mbox_sha1sum")->join( "<br>" ) . "</dd>";
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // homepage
    if ($me->get("foaf:homepage") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Homepage: </h3>";
        foreach ($me->all("foaf:homepage") as $homepage) {
            $ret .= "<dd>" . $graph->resource($homepage)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // blogs
    if ($me->get("foaf:weblog") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Blog: </h3>";
        foreach ($me->all("foaf:weblog") as $blog) {
            $ret .= "<dd>" . $graph->resource($blog)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
        
    // workplaceHomepage  
    if ($me->get("foaf:workplaceHomepage") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Workplace Homepage: </h3>";
        foreach ($me->all("foaf:workplaceHomepage") as $workpage) {
            $ret .= "<dd>" . $graph->resource($workpage)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // schoolHomepage
    if ($me->get("foaf:schoolHomepage") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">School Homepage: </h3>";
        foreach ($me->all("foaf:schoolHomepage") as $schoolpage) {
            $ret .= "<dd>" . $graph->resource($schoolpage)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
        
    // current proj
    if ($me->get("foaf:currentProject") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Current projects: </h3>";
        foreach ($me->all("foaf:currentProject") as $currproj) {
            $ret .= "<dd>" . $graph->resource($currproj)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // past proj
    if ($me->get("foaf:pastProject") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Past projects: </h3>";
        foreach ($me->all("foaf:pastProject") as $pastproj) {
            $ret .= "<dd>" . $graph->resource($pastproj)->link() . "</dd>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // rss feeds
    if ($me->get("foaf:rssFeed") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">RSS feeds: </h3>";
        foreach ($me->all('foaf:rssFeed') as $rss) {
			// each feed is a separate resource
            $contres = $graph->resource($rss);
            if ($contres->get('http://purl.org/rss/1.0/title') != '[NULL]')          
                $ret .= "<dd><h3 class=\"demoHeaders\">Title</h3> " . $contres->get('http://purl.org/rss/1.0/title') . "</dd>\n";
            if ($contres->get('http://purl.org/rss/1.0/link') != '[NULL]')
                $ret .= "<dd><h3 class=\"demoHeaders\">Link</h3> <a href=\"" . $contres->get('http://purl.org/rss/1.0/link') . "\">" . $contres->get('http://purl.org/rss/1.0/link') . "</a></dd>\n";
            if ($contres->get('http://purl.org/rss/1.0/description') != '[NULL]')
                $ret .= "<dd><h3 class=\"demoHeaders\">Description</h3> " . $contres->get('http://purl.org/rss/1.0/description') . "</dd>\n";
            $ret .= "<br/>\n";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // knows
    if ($me->get("foaf:knows") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Knows: </h3>";

        foreach ($me->all('foaf:knows') as $friend)
            $ret .= "<dd><a href=\"view.php?uri=" . urlencode($friend) . "\">" . $friend . "</a></dd>";

        $ret .= "</td>";
        $ret .= "</tr>\n";
    }

    // holds account
    if ($me->get("foaf:holdsAccount") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Holds account: </h3>";
        foreach ($me->all('foaf:holdsAccount') as $accounts) {
            // each account is a separate resource
            $account = $graph->resource($accounts);
            if ($account->get("rdfs:label") != '[NULL]')
            $ret .= "<dd>" . $account->get("rdfs:label") . "</dd>";
            if ($account->get("foaf:accountProfilePage") != '[NULL]')
                $ret .= "<dd>" . $account->get("foaf:accountProfilePage")->link() . "</dd>\n";
            if ($account->get("foaf:accountServiceHomepage") != '[NULL]')
                $ret .= "<dd>" . $account->get("foaf:accountServiceHomepage")->link() . "</dd>";
            $ret .= "<br/>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // interests
    if ($me->get("foaf:interest") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Interests: </h3>";
        foreach ($me->all('foaf:interest') as $interests) {
            // each interest is a separate resource
            $interest = $graph->resource($interests);
            $label = ($interest->label() == '[NULL]') ? $interest->toString() : $interest->label();
            $ret .= "<dd><a href=\"" . $interest->toString() . "\">" . $label . "</a></dd>\n";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // public keys
    if ($me->get("wot:hasKey") != '[NULL]') {
        $ret .= "<tr valign=\"top\">";
        $ret .= "<td><h3 class=\"demoHeaders\">Public key: </h3>";
        foreach ($me->all('wot:hasKey') as $keys) {
            // each key is a separate resource
            $key = $graph->resource($keys);
    
            if ($key->get("wot:fingerprint") != '[NULL]')
                $ret .= "<dd><h3 class=\"demoHeaders\">Fingerprint:</h3></dd><dd>" . $key->get("wot:fingerprint") . "</dd>\n";
            if ($key->get("wot:hex_id") != '[NULL]')
                $ret .= "<dd><h3 class=\"demoHeaders\">Hex ID:</h3></dd><dd>" . $key->get("wot:hex_id") . "</dd>";
            $ret .= "<br/>";
        }
        $ret .= "</td>";
        $ret .= "</tr>\n";
    }
    
    // certificates
    $ret .= "<tr valign=\"top\">";
    $ret .= "<td><h3 class=\"demoHeaders\">Certificates: </h3>";
    foreach ($graph->allOfType('http://www.w3.org/ns/auth/cert#RSAPublicKey') as $certs) {

        // get corresponding resources for modulus and exponent
        if (substr($certs->get('http://www.w3.org/ns/auth/cert#modulus'), 0, 2) == '_:') {
            $mod = $graph->resource($certs->get('http://www.w3.org/ns/auth/cert#modulus'));
            $hex = (string)$mod->get('http://www.w3.org/ns/auth/cert#hex');
        } else {
           $hex = (string)$certs->get('http://www.w3.org/ns/auth/cert#modulus');
        }

        if (substr($certs->get('http://www.w3.org/ns/auth/cert#exponent'), 0, 2) == '_:') {
            $exp = $graph->resource($certs->get('http://www.w3.org/ns/auth/cert#exponent'));
            if ($exp->get('http://www.w3.org/ns/auth/cert#decimal') != '[NULL]')
                $exponent = $exp->get('http://www.w3.org/ns/auth/cert#decimal');
            else if ($exp->get('http://www.w3.org/ns/auth/cert#integer') != '[NULL]')
                $exponent = $exp->get('http://www.w3.org/ns/auth/cert#integer');
            else
                $exponent = 'NULL';
        } else {
            $exponent = $certs->get('http://www.w3.org/ns/auth/cert#exponent');
        }

        if (is_array($hex)) {
            foreach ($hex as $modulus) {
                if (is_array($modulus))
                    $modulus = (string)$modulus[0];

                $ret .= "<dd><h3 class=\"demoHeaders\">Modulus:</h3></dd><dd>" . wordwrap($modulus, 70, "<br />\n", 1) . "</dd><br/>\n";
            }
        } else {
            $ret .= "<dd><h3 class=\"demoHeaders\">Modulus:</h3></dd><dd>" . wordwrap($hex, 70, "<br />\n", 1) . "</dd><br/>\n";
        }
        $ret .= "<dd><h3 class=\"demoHeaders\">Public exponent:</h3></dd><dd>" . $exponent . "</dd>\n";
        $ret .= "<br/>\n";
    }

    $ret .= "</tr>\n";
    $ret .= "</table>\n";
    
    // load all pictures
    $ret .= "</td>";
    $ret .= "<td valign=\"top\">";
    foreach ($me->all("foaf:img") as $picture) {
        $ret .= "<img width=\"200\" src=\"" . $picture . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    foreach ($me->all("foaf:depiction") as $picture) {
        $ret .= "<img width=\"200\" src=\"" . $picture . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    foreach ($me->all("foaf:logo") as $logo) {
        $ret .= "<img width=\"200\" src=\"" . $logo . "\"></img>\n";
        $ret .= "<br/><br/>\n";
    }
    $ret .= "</td>\n";
    $ret .= "</tr>\n";
    $ret .= "</table>\n";
    
    return $ret;
}

?>
