<?php

class Recovery {
    private $webid = ''; // The WebID of a recovered account
    private $reason = ''; // The reason why a recovery or authentication failed
    
    function __construct() {
        // empty
    }
    
    function isAuthenticated($hash) {
        $query = "SELECT webid FROM recovery WHERE recovery_hash='".mysql_real_escape_string(trim($hash))."'";
        $result = mysql_query($query);
        if (!$result) {
            die('Unable to connect to the database!');
        } else if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $this->webid = $row['webid'];
            mysql_free_result($result);

            // remove the hash to disable it
            $query = "UPDATE recovery SET ".
                "recovery_hash=NULL ".
                "WHERE webid='".mysql_real_escape_string($this->webid)."'";
            $result = mysql_query($query);
            if (!$result) {
                die('Unable to connect to the database!');
            } else {
                mysql_free_result($result);
            }

        } else {
            $this->reason = 'Your recovery code does not match any records in our database.';
            return False;
        }
        return True;
    }
    
    function recover($webid) {
        // hexa string of 20 chars
        $hash = sha1(trim($webid) . uniqid(microtime(true), true));

        $webid = trim($webid);
        
        // find if a recovery email exists or not for the given WebID
        $query = "SELECT email FROM recovery WHERE webid='".mysql_real_escape_string($webid)."'";
        $result = mysql_query($query);
        
        if (!$result) {
            die('Unable to connect to the database!');
        } else if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $email = $row['email'];
            mysql_free_result($result);

            // set the hash
            $query = "UPDATE recovery SET ".
                    "recovery_hash='".$hash."' ".
                    "WHERE webid='".mysql_real_escape_string($webid)."'";
            $result = mysql_query($query);
            
            if (!$result) {
                return error('Unable to connect to the database!');
            } else {
                // send the email
                $person = new MyProfile(trim($webid), BASE_URI, SPARQL_ENDPOINT);
                $person->load();
                $to_name = $person->get_name();
                
                $from = 'MyProfile Notification System <' . SMTP_USERNAME . '>';
                $to = '"' . $to_name . '" <' . clean_mail($email) . '>';
                $subject = 'Instructions to recover your account on '.BASE_URI.'.';

                $headers = array ('From' => $from,
                                'To' => $to,
                                'Subject' => $subject);

                $smtp = Mail::factory('smtp', array ('host' => SMTP_SERVER,
                                                     'auth' => SMTP_AUTHENTICATION,
                                                     'username' => SMTP_USERNAME,
                                                     'password' => SMTP_PASSWORD));

                $message = '<html><body>';
                $message .= '<p>Hello ' . $to_name . ',</p>';
                $message .= '<p>You have requested to recover your personal account on '.BASE_URI.'. ';
                $message .= 'Please click <a href="' . BASE_URI . '/recovery?recovery_code='.$hash.'">this link</a> to proceed.</p>';
                $message .= '<p>Alternatively, you can manually use the recovery code at this page: ';
                $message .= '<a href="'.BASE_URI.'/recovery">'.BASE_URI.'/recovery</a>.</p>';
                $message .= '<p>Your code is: <strong>'.$hash.'</strong></p>';
                $message .= '<br /><p><hr /></p>';
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

                $mail = $smtp->send($to, $headers, $body);

                if (PEAR::isError($mail)) {
                    $ret .= error('Sendmail: ' . $mail->getMessage());
                }

                return success('An email has been sent to the recovery address you have specified.');
            }
        } else {
            return error('You did not provide a recovery email address!');
        }
    }

    // return the WebID of the user
    function get_webid() {
        return $this->webid;
    }
    function get_reason() {
        return $this->reason;
    }
}
