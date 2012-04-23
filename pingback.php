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

// only allow to send pingbacks if the sender is logged in
check_auth($idp, $page_uri);

$ret = "";
$ret .= "<font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Send a WebID Pingback</font>\n";

if (isset($_REQUEST['to'])) {
    $ret .= "<br/>\n";
    
    $to = trim($_REQUEST['to']);

    // fetch the user's profile
    $person = new MyProfile($to, $base_uri);
    $person->load();
    $profile = $person->get_profile();
    
    $to_name = $person->get_name();
    $pingback_service = $profile->get("http://purl.org/net/pingback/to");
    
    // set form data
    $source = $_SESSION['webid'];
    $comment = $_REQUEST['comment'];
        
    // parse the pingback form
    $config = array('auto_extract' => 0);
    $parser = ARC2::getSemHTMLParser($config);
    $parser->parse($pingback_service);
    $parser->extractRDF('rdfa');

    $triples = $parser->getTriples();

    //debug
    //echo "<pre>" . print_r($triples, true) . "</pre>\n";
    
    if ($pingback_service != '[NULL]') {
        if (sizeof($triples) > 0) {
            //echo "<pre>" . print_r($triples, true) . "</pre>\n";
            foreach ($triples as $triple) {
                // proceed only if we have a valid pingback resource
                if ($triple['o'] == 'http://purl.org/net/pingback/Container') {

                    $fields = array ('source' => $source,
                                    'target' => $to,
                                    'comment' => $comment
                                );
                    
                    //open connection to pingback service
                    $ch = curl_init();

                    //set the url, number of POST vars, POST data
                    curl_setopt($ch,CURLOPT_URL,$pingback_service);
                    curl_setopt($ch,CURLOPT_POST,count($fields));
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

                    //execute post
                    $success = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
                    //close connection
                    curl_close($ch);

                    if (($httpCode == '201') || ($httpCode == '202'))
                        $ret .= success('The ping was successful!');
                    else
                        $ret .= error('Something happened and the ping was NOT successful!' . $success);
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
        $ret .= "   <p>Could not find a pingback service for the given WebID. Here is a generic pingback service provided by http://pingback.aksw.org/.</p>\n";
        // show frame
        $ret .= "   <iframe src=\"http://pingback.aksw.org/\" width=\"100%\" height=\"300\">\n";
        $ret .= "   <p>Your browser does not support iframes.</p>\n";
        $ret .= "   </iframe>\n";
    }

} else {
    // show pingback form 
    $ret .= "   <div class=\"clear\"><br/><br/></div>\n";
    $ret .= "   <p>Attempt to 'ping' someone using the pingback service found in their profile.</p>\n"; 
    $ret .= "   <p>The destination WebID must contain a relation of type pingback:to (http://purl.org/net/pingback/to), pointing to pingback service.</p>\n";
    $ret .= "   <form name=\"lookup_pingback\" method=\"POST\" action=\"\"><br/>\n";
    $ret .= "       Destination WebID: <input size=\"50\" type=\"text\" name=\"to\" value=\"" . $_REQUEST['uri'] . "\"><br/><br/>\n";
    $ret .= "       Optional comment: <input size=\"50\" maxlength=\"256\" type=\"text\" name=\"comment\" value=\"\" style=\"background-color:#fff; border:dashed 1px grey;\"> <small>(max 256 characters)</small><br/><br/>\n";
    $ret .= "       <input type=\"submit\" name=\"submit\" value=\" Ping! \" class=\"btn btn-primary\">\n";
    $ret .= "   </form>\n";
}

echo $ret;
include 'footer.php';
?>		      
