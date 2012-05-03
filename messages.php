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
 * display the user's messages
 */ 

require_once 'include.php'; 

// verify if we're logged in or not
check_auth($idp, $page_uri);

$ret = "";
$ret .= "<div class=\"container\">\n";
$ret .= "<font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Messages</font>\n";
$ret .= "</div>\n";

$ret .= "<div class=\"container\">\n";

// verify if we are already registered
if (!is_subscribed($_SESSION['webid'])) {
    $ret .= "<br/><p><font style=\"font-size: 1.3em;\">You have not registered to receive messages! You can register <a href=\"subscription.php\">here</a>.</font></p>\n";
}

// manage received messages/pingbacks
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
        header("Location: messages.php?new=true&to=" . urlencode($_REQUEST['to']));
    }
    
    $messages = get_msg_count($_SESSION['webid']);
    $private_msg = get_msg_count($_SESSION['webid'], 1, 0);
}

// send a new message using the pingback protocol
if ((isset($_REQUEST['doit'])) && (isset($_REQUEST['to']))) {
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
    $comment = $_REQUEST['message'];
        
    // parse the pingback form
    $config = array('auto_extract' => 0);
    $parser = ARC2::getSemHTMLParser($config);
    $parser->parse($pingback_service);
    $parser->extractRDF('rdfa');
    // load triples
    $triples = $parser->getTriples();

    // proceed only if the user has defined a pingback:to relation    
    if ($pingback_service != '[NULL]') {
        if (sizeof($triples) > 0) {
            //echo "<pre>" . print_r($triples, true) . "</pre>\n";
            foreach ($triples as $triple) {
                // proceed only if we have a valid pingback resource
                if ($triple['o'] == 'http://purl.org/net/pingback/Container') {

                    /*    
                    $ret .= '<script>
                        $.ajax({
                            url: "' . $pingback_service . '",
                            type: "POST",
                            data: {source: "' . $source. '", target: "' . $to . '", comment: "' . $comment . '"},
                            statusCode: {
                                200: function () {
                                        alert("Success!");
                                        },
                                201: function () {
                                        alert("Success!");
                                        }
                            }
                            });
                        // ------- OR ------- //
                        var request = $.ajax({
                                    url: "' . $pingback_service . '",
                                    type: "POST",
                                    data: {source: "' . $source. '", target: "' . $to . '", comment: "' . $comment . '"},
                                    dataType: "html"
                            });

                        request.done(function() {
                            alert("Success");
                        });

                        request.done(function(jqXHR, textStatus) {
                            alert( "Request failed: " + textStatus );
                        });
                        </script>';
                    */

                    $fields = array ('source' => $source,
                                    'target' => $to,
                                    'comment' => $comment
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
*/

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
}

// display form to send local messages
if (isset($_REQUEST['new'])) {
    $ret .= "<div class=\"clear\"><br/><br/></div>\n";
    $ret .= "<form name=\"send\" method=\"POST\" action=\"messages.php\">\n";
    $ret .= "<input type=\"hidden\" name=\"doit\" value=\"1\">\n";
    $ret .= "<table border=\"0\">\n";
    $ret .= "<tr valign=\"top\"><td>From WebID: <br/>&nbsp;</td><td><strong>You</strong> <font color=\"grey\"><small>(" . $_SESSION['webid'] . ")</small></font></td></tr>\n";
    $ret .= "<tr valign=\"top\"><td>Target WebID: <br/>&nbsp;</td><td><input size=\"30\" type=\"text\" name=\"to\" value=\"" . $_REQUEST['to'] . "\"></td></tr>\n";
    $ret .= "<tr valign=\"top\"><td>Short message (256): <br/>(optional)</td><td> <textarea style=\"height: 130px;\" name=\"message\"></textarea></td></tr>\n";
    $ret .= "<tr><td><br/><input type=\"submit\" class=\"btn btn-primary\" name=\"submit\" value=\" Send message! \"></td><td></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form>\n";

    $ret .= "<div class=\"clear\"></div>\n";
    $ret .= "</div>\n";
} else {
    $ret .= "<div class=\"clear\"><br/><br/></div>\n";
    
    // display pingbacks/messages
    $query = "SELECT * FROM pingback_messages WHERE to_uri='" . mysql_real_escape_string($_SESSION['webid']) . "' AND wall='0' ORDER BY date DESC LIMIT 100";
    $result = mysql_query($query);
    if (!$result) {
        $ret  = 'Invalid query: ' . mysql_error() . "\n";
        $ret .= 'Whole query: ' . $query;
    } else if (mysql_num_rows($result) == 0){
        $ret .= "<br/><font style=\"font-size: 1.3em;\">You have no messages.</font>\n";
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

            $ret .= "<form method=\"POST\" action=\"messages.php\">\n";
            $ret .= "<input type=\"hidden\" name=\"action\" value=\"1\">\n";
            $ret .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\">\n";
            $ret .= "<input type=\"hidden\" name=\"to\" value=\"" . $row['from_uri'] . "\">\n";
            $ret .= "<table border=\"0\">\n";

            $ret .= "<tr valign=\"top\">\n";
            $ret .= "   <td width=\"80\" align=\"center\">\n";
            $ret .= "       <a href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\"><img title=\"" . $name . "\" alt=\"" . $name . "\" width=\"48\" src=\"" . $pic . "\" style=\"padding: 0px 0px 10px;\" /></a>\n";
            $ret .= "   </td>\n";
            $ret .= "   <td>";
            $ret .= "       <table border=\"0\">\n";
            $ret .= "       <tr valign=\"top\">\n";
            $ret .= "           <td><b><a href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\" style=\"font-color: black;\">" . $name . "</a></b></td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr>\n";
            $ret .= "           <td><pre>" . $text . "</pre></td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr>\n";
            $ret .= "           <td style=\"color: grey;\"><br/><small>" . date('Y-m-d H:i:s', $row['date']) . "</small> \n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr><td><br/>\n";
            if (is_subscribed($row['from_uri']))
                $ret .= "           <input type=\"submit\" class=\"btn btn-primary\" name=\"reply\" value=\" Reply \">";
            if ($new == 1)
                $ret .= "           <input type=\"submit\" class=\"btn btn-primary\" name=\"read\" value=\" Mark as read \">";
            else if ($new == 0)
                $ret .= "           <input type=\"submit\" class=\"btn\" name=\"unread\" value=\" Mark as unread \">";
            $ret .= "           <input type=\"submit\" class=\"btn btn-danger\" name=\"delete\" value=\" Delete \"> ";
            $ret .= "           </td>\n";
            $ret .= "       </tr>\n";
            $ret .= "       <tr><td>&nbsp;</td></tr>\n";
            $ret .= "       </table>\n";
            $ret .= "   </td>\n";
            $ret .= "</tr>\n";
                 
            $ret .= "<tr><td colspan=\"2\"><hr style=\"border: none; height: 1px; color: #cccccc; background: #cccccc;\"/></td></tr>\n";
            $ret .= "</table>\n";
            $ret .= "</form>\n";
            $i++;
        }

    }
}

$ret .= "</div>\n";

include 'header.php';
echo $ret;
include 'footer.php';

?>
