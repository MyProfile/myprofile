<?php
//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : pingback.php
// Date       : 21st Apr 2011
//
// Project name: SemPB - Semantical Pingback
// Copyright 2011 fcns.eu
// Author: Andrei Sambra - andrei@fcns.eu
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// See <http://www.gnu.org/licenses/> for a description of this license.

require 'include.php';
require_once 'lib/Mail.php';
require_once 'lib/Mail/mime.php';

$ret = "";

// Process request and deliver pingback
if (isset($_POST['source'])) {
    // fetch the user's profile
    $profile = new MyProfile(trim($_POST['source']), $base_uri, SPARQL_ENDPOINT);
    $profile->load();
    
    // Prepare data to be inserted into the database
    $from   = mysql_real_escape_string(trim($_POST['source']));
    $to     = mysql_real_escape_string(trim($_POST['target']));
    $msg    = mysql_real_escape_string(trim($_POST['comment']));
    $name   = mysql_real_escape_string(trim($profile->get_name()));
    $pic    = mysql_real_escape_string(trim($profile->get_picture()));

    // Return HTTP 400 (bad request)
    if (!isset($_POST['target'])) {
        // No destination user, return a proper HTTP response code with error
        $ret .= header("HTTP/1.1 400 Bad request");
        $ret .= header("Status: 400 Bad request");
        $ret .= "<html><body>\n";
        $ret .= "Bad request: you did not specify the destination user.\n";
        $ret .= "</body></html>\n";
    } else if (!isset($_POST['comment'])) {
        // No message, return a proper HTTP response code with error
        $ret .= header("HTTP/1.1 400 Bad request");
        $ret .= header("Status: 400 Bad request");
        $ret .= "<html><body>\n";
        $ret .= "Bad request: you did not provide a message.\n";
        $ret .= "</body></html>\n";
    } else {
        // write webid uri to database
        $query = "INSERT INTO pingback_messages SET date='" . time() . "', ";
        $query .= "from_uri = '" . $from . "', ";
        $query .= "to_uri = '" . $to . "', ";
        $query .= "name = '" . $name . "', ";
        $query .= "pic = '" . $pic . "', ";
        $query .= "msg = ' " . $msg . "'";
        
        $result = mysql_query($query);
        
        if (!$result) {
            // Database error, return a proper HTTP response code with error
            $ret .= header("HTTP/1.1 500 Internal Error");
            $ret .= header("Status: 500 Internal Error");
            $ret .= "<html><body>\n";
            $ret .= "Internal error: could not deliver the ping (database error).\n";
            $ret .= "</body></html>\n";
        } else {
            mysql_free_result($result);
            // Everything is OK, return a proper HTTP response success code
            
            // Send a mail too if the receiving user allows it
            if ((is_subscribed_email($to)) && ($to_email != '[NULL]')) {
                $person = new MyProfile(trim($_POST['target']), $base_uri, SPARQL_ENDPOINT);
                $person->load();
                $to_name = $person->get_name();
                $to_email = $person->get_email();
                
                $from = 'MyProfile Notification System <' . $email_username . '>';
                $to = '"' . $to_name . '" <' . clean_mail($to_email) . '>';
                $subject = 'You have received a new personal message!';

                $headers = array ('From' => $from,
                                'To' => $to,
                                'Subject' => $subject);

                if (SMTP_AUTHENTICATION == true) {
                    $mail_factory = Mail::factory('smtp', array ('host' => SMTP_SERVER,
                                                         'auth' => SMTP_AUTHENTICATION,
                                                         'username' => SMTP_USERNAME,
                                                         'password' => SMTP_PASSWORD));
                } else {
                    $mail_factory = Mail::factory('mail');
                }
                
                $message = '<html><body>';
                $message .= '<p>Hello ' . $to_name . ',</p>';
                $message .= '<p>You have just received a new message from ' . $name . '! ';
                $message .= '<a href="' . $base_uri . '/messages.php">Click here</a> to see it.</p>'; 
                $message .= '<br/><p><small>You are receiving this email because you enabled Semantic Pingback notification ';
                $message .= '(with email as notification mechanism) for your Personal Profile on <a href="' . $base_uri . '">' . $base_uri . '</a>. ';
                $message .= 'If you would like to stop receiving email notifications, please check your ';
                $message .= '   <a href="' . $base_uri . '/subscription.php">subscription settings</a>.</small></p>';
                $message .= '<p><small>You do not need to respond to this automated email.</small></p>';
                $message .= '</body></html>';
                $crlf = "\n";
                $mime = new Mail_Mime(array('eol' => $crlf));
                $mime->setHTMLBody($message);

                $mimeparams=array(); 
                $mimeparams['html_charset']="UTF-8"; 
                $mimeparams['head_charset']="UTF-8"; 

                $headers = $mime->headers($headers); 
                $body = $mime->get($mimeparams);

                $mail = $mail_factory->send($to, $headers, $body);

                if (PEAR::isError($mail)) {
                    $ret .= error('Sendmail: ' . $mail->getMessage());
                }
            }
            $ret .= header("HTTP/1.1 201 Created");
            $ret .= header("Status: 201 Created");
            $ret .= "<html><body>\n";
            $ret .= "Your message has been successfully delivered!\n";
            $ret .= "</body></html>\n";
        }
    }
} else {
    // Show form
    $ret .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
    $ret .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"  xmlns:pingback=\"http://purl.org/net/pingback/\">\n";
    $ret .= "   <head>\n";
    $ret .= "	<title>Pingback</title>\n";
    $ret .= "	<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />\n";
    $ret .= "   </head>\n";
    $ret .= "   <body typeof=\"pingback:Container\">\n";
    $ret .= "   <form method=\"post\" action=\"pingback.php\">\n";
    $ret .= "       <p>Your WebID: <input size=\"30\" property=\"pingback:source\" type=\"text\" name=\"source\" /></p>\n";
    $ret .= "       <p>Target WebID: <input size=\"30\" property=\"pingback:target\" type=\"text\" name=\"target\" value=\"" . $_GET['target'] . "\" /></p>\n";
    $ret .= "       <p>Comment (optional): <input size=\"30\" maxlength=\"256\" type=\"text\" name=\"comment\" style=\"background-color:#fff; border:dashed 1px grey;\" /></p>\n";
    $ret .= "       <p><input type=\"submit\" name=\"submit\" value=\"Ping!\" /></p>\n";
    $ret .= "   </form>\n";
    $ret .= "   </body>\n";
    $ret .= "</html>\n";
}
// Display
echo $ret;

?>		      

