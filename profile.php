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

if (isset($_REQUEST['doit']))  {
        // load specific include files depending on the case
        if ($_REQUEST['action'] == 'edit') {
            // we can include the header here since we're not generating a cert
            include_once 'include.php';
            include_once 'header.php';
        } else {
            include_once 'include.php';
        }

        // Create the graph object in which we will store data
        $graph = new EasyRdf_Graph();
 
        if ($_REQUEST['action'] == 'edit') {
            $user_dir = webid_get_local_path($_SESSION['webid']);
            $webid_base = $base_uri . '/' . $user_dir . '/card';
            $webid = $_SESSION['webid'];
        } else {
             // prepare WebID URI
            $webid_base = $base_uri . '/people/' . $_REQUEST['uri'] . '/card';
            $webid = $webid_base . "#me";
            $user_dir = "people/" . $_REQUEST['uri'];
        }    
        
        // create primary topic
        $pt = $graph->resource($webid_base, 'foaf:PersonalProfileDocument');
        $pt->set('foaf:maker', $webid);
        $pt->set('foaf:primaryTopic', $webid);
        $pt->set('foaf:title', $_REQUEST['foaf:name'] . "'s profile.");

// ----- foaf:Person ----- //
        // create the Person graph
        $me = $graph->resource($webid, 'foaf:Person');
        
        // name
        $me->set('foaf:name', $_REQUEST['foaf:name']);
        
        // first name
        if ((isset($_REQUEST['foaf:givenname'])) && (strlen($_REQUEST['foaf:givenname']) > 0))
            $me->set('foaf:givenname', trim($_REQUEST['foaf:givenname']));
        // last name
        if ((isset($_REQUEST['foaf:family_name'])) && (strlen($_REQUEST['foaf:family_name']) > 0))
            $me->set('foaf:family_name', trim($_REQUEST['foaf:family_name']));
        // title
        if ((isset($_REQUEST['foaf:title'])) && (strlen($_REQUEST['foaf:title']) > 0))
            $me->set('foaf:title', trim($_REQUEST['foaf:title']));
        // depiction
        if ((isset($_REQUEST['foaf:depiction'])) && (strlen($_REQUEST['foaf:depiction']) > 0))
            $me->set('foaf:depiction', trim($_REQUEST['foaf:depiction']));
        // logo
        if ((isset($_REQUEST['foaf:logo'])) && (strlen($_REQUEST['foaf:logo']) > 0)) {
            $me->set('foaf:logo', trim($_REQUEST['foaf:logo']));
        }
        // email
        if ((isset($_REQUEST['foaf:mbox'])) && (strlen($_REQUEST['foaf:mbox'][0]) > 0)) {
            foreach ($_REQUEST['foaf:mbox'] as $val)
                $me->add('foaf:mbox', "mailto:" . trim($val));
        }
        // email_sha1sum
        if ((isset($_REQUEST['foaf:mbox_sha1sum'])) && (strlen($_REQUEST['foaf:mbox_sha1sum'][0]) > 0)) {
            foreach ($_REQUEST['foaf:mbox_sha1sum'] as $val)
                $me->add('foaf:mbox_sha1sum', trim($val));
        }
        // homepage
        if ((isset($_REQUEST['foaf:homepage'])) && (strlen($_REQUEST['foaf:homepage'][0]) > 0)) {
            foreach($_REQUEST['foaf:homepage'] as $val)
                $me->add('foaf:homepage', trim($val));
        }
        // blogs
        if ((isset($_REQUEST['foaf:weblog'])) && (strlen($_REQUEST['foaf:weblog'][0]) > 0)) {
            foreach($_REQUEST['foaf:weblog'] as $val)
                $me->add('foaf:weblog', trim($val));
        }
        // work homepages
        if ((isset($_REQUEST['foaf:workplaceHomepage'])) && (strlen($_REQUEST['foaf:workplaceHomepage'][0]) > 0)) {
            foreach($_REQUEST['foaf:workplaceHomepage'] as $val)
                $me->add('foaf:workplaceHomepage', trim($val));
        }
        // school homepages
        if ((isset($_REQUEST['foaf:schoolHomepage'])) && (strlen($_REQUEST['foaf:schoolHomepage'][0]) > 0)) {
            foreach($_REQUEST['foaf:schoolHomepage'] as $val)
                $me->add('foaf:schoolHomepage', trim($val));
        }     
        // current projects
        if ((isset($_REQUEST['foaf:currentProject'])) && (strlen($_REQUEST['foaf:currentProject'][0]) > 0)) {
            foreach($_REQUEST['foaf:currentProject'] as $val)
                $me->add('foaf:currentProject', trim($val));
        }
        // past projects
        if ((isset($_REQUEST['foaf:pastProject'])) && (strlen($_REQUEST['foaf:pastProject'][0]) > 0)) {
            foreach($_REQUEST['foaf:pastProject'] as $val)
                $me->add('foaf:pastProject', trim($val));
        }
        
// ----- foaf:knows ----- //
        if ((isset($_REQUEST['foaf:knows'])) && (strlen($_REQUEST['foaf:knows'][0]) > 0)) {
            $knows = '';

            foreach($_REQUEST['foaf:knows'] as $key => $person_uri) {
                if (strlen($person_uri) > 0) {
                    $me->add('foaf:knows', $person_uri);
                }
            }
        }
   
// ----- pingback:to relation ----- //
        $me->set('pingback:to', $base_uri . '/notification.php');

        // certificates
        // write certificates' public keys (if we have more than one)
        foreach($_REQUEST['modulus'] as $key => $val) {
            if (strlen($val) > 0) {
                $modulus = preg_replace('/\s+/', '', $val);
                $exponent = (strlen($_REQUEST['exponent'][$key]) > 0) ? trim($_REQUEST['exponent'][$key]) : '65537';
                $cert = $graph->newBNode('cert:RSAPublicKey');
                $cert->add('cert:modulus', array(
                        'type' => 'literal',
                        'datatype' => 'http://www.w3.org/2001/XMLSchema#hexBinary',
                        'value' => $modulus)
                      );
                $cert->add('cert:exponent', array(
                        'type' => 'literal',
                        'datatype' => 'http://www.w3.org/2001/XMLSchema#int',
                        'value' => $exponent)
                        );
                $me->add('cert:key', $cert);
            }
        }
        
// ----- GENERATE CERTIFICATE ----- //
        // Do not generate a certificate if we're just editing the profile
        if (($_REQUEST["action"] == 'new') || ($_REQUEST["action"] == 'import')) {
            // append other webids after the local one
        	$foafLocation = array();
        	$foafLocation[] = $webid;
          	foreach ($_REQUEST['webid_uri'] as $val) {
        	    if (strlen($val) > 0) 
        	        $foafLocation[] = $val;
        	}
        	
        	if (strlen($_REQUEST['countryName']) < 1)
    	    	$countryName = 'EU';
            
    	    // Get the rest of the script parameters
    	    // Not useful for now, might use them later in an advanced config
    	    $countryName		    = $_REQUEST['countryName'];
    	    $stateOrProvinceName	= $_REQUEST['stateOrProvinceName'];
       	    $localityName		    = $_REQUEST['localityName'];
    	    $organizationName	    = $_REQUEST['organizationName'];
    	    $organizationalUnitName = $_REQUEST['organizationalUnitName'];
            $emailAddress           = $_REQUEST['emailAddress'];
    	    $pubkey			        = $_REQUEST["pubkey"];
	
    	    // Create a x509 SSL certificate in DER format
        	$x509 = create_identity_x509($countryName, $stateOrProvinceName, $localityName, $organizationName, $organizationalUnitName, $_REQUEST['foaf:name'], $emailAddress, $foafLocation, $pubkey, $SSLconf, $CApass);
            $command = "openssl x509 -inform der -in " . $x509 . " -modulus -noout";
          	$output = explode('=', shell_exec($command));
            
            // add public key elements to our new webid profile
            $modulus = preg_replace('/\s+/', '', strtolower($output[1]));
            $cert = $graph->newBNode('cert:RSAPublicKey');
            $cert->add('cert:modulus', array(
                        'type' => 'literal',
                        'datatype' => 'http://www.w3.org/2001/XMLSchema#hexBinary',
                        'value' => $modulus)
                      );
            $cert->add('cert:exponent', array(
                        'type' => 'literal',
                        'datatype' => 'http://www.w3.org/2001/XMLSchema#int',
                        'value' => '65337')
                        );
            $me->add('cert:key', $cert);
            
            // autmatically subscribe to local services
            $tiny = substr(md5(uniqid(microtime(true),true)),0,8);
            $user_hash  = substr(md5($webid), 0, 8);
		    $query = "INSERT INTO pingback SET webid='" . $webid . "', feed_hash='" . $tiny . "', user_hash='" . $user_hash . "'";
            $result = mysql_query($query);
            if (!$result) {
                die('Unable to write to the database!');
            } else {
                mysql_free_result($result);
            }
            
            // create dirs
            if (!mkdir($user_dir, 0775))
                die('Failed to create user profile directory!');
    
            // write Rewrite .htaccess file
            $htaccess = fopen($user_dir . '/.htaccess', 'w') or die('Cannot create .htaccess file!');
            // .htaccess content
            $rw = "Options -MultiViews\n";
            $rw .= "AddType \"application/rdf+xml\" .rdf\n";
            $rw .= "RewriteEngine On\n";
            $rw .= "RewriteBase /" . $user_dir . "/\n";
            $rw .= "RewriteCond %{HTTP_ACCEPT} !application/rdf\+xml.*(text/html|application/xhtml\+xml)\n";
            $rw .= "RewriteCond %{HTTP_ACCEPT} text/html [OR]\n";
            $rw .= "RewriteCond %{HTTP_ACCEPT} application/xhtml\+xml [OR]\n";
            $rw .= "RewriteCond %{HTTP_USER_AGENT} ^Mozilla/.*\n";
            $rw .= "RewriteRule ^card$ foaf.txt [R=303]\n";
            $rw .= "RewriteCond %{HTTP_ACCEPT} application/rdf\+xml\n";
            $rw .= "RewriteRule ^card$ foaf.rdf [R=303]\n";
            $rw .= "RewriteRule ^card$ foaf.rdf [R=303]\n";
            // finally write content to file
            fwrite($htaccess, $rw);
            fclose($htaccess);
        }

        // write profile to file
        $data = $graph->serialise('rdfxml');
        if (!is_scalar($data)) 
            $data = var_export($data, true);

        $pf = fopen($user_dir . '/foaf.rdf', 'w') or die('Cannot create profile RDF file in ' . $user_dir . '!');
        fwrite($pf, $data);
        fclose($pf);    
        
        $pf = fopen($user_dir . '/foaf.txt', 'w') or die('Cannot create profile PHP file!');
        fwrite($pf, $data);
        fclose($pf);
      
        // everything is fine
        $ok = true;
    
        // Send the X.509 SSL certificate to the script caller (user) as a file transfer
        if (($_REQUEST['action'] == 'new') || ($_REQUEST['action'] == 'import')) {
            download_identity_x509($x509, $webid);
        } else {
            // We're just editing, so display form view
            include_once 'header.php';
            // add form view
            echo $_SESSION['myprofile']->get_title($_REQUEST['action']);
            if ($ok) {
                echo $_SESSION['myprofile']->success('Your profile has been updated.');
            } else {
                echo $_SESSION['myprofile']->error('Could not update your profile!');
            }
            echo $_SESSION['myprofile']->form($_REQUEST['action']);
        }
} else {
    include_once 'include.php';
    include 'header.php';

    // Display an error message if we got here from the IdP
    if (isset($_REQUEST['error_message']))
        echo error(urldecode($_REQUEST['error_message']));

    // Display form view
    if (!isset($_REQUEST['action']))
        $_REQUEST['action'] = 'new';
        
    // If needed load a bogus profile to be able to display the form
    $profile = (isset($_SESSION['myprofile'])) ? $_SESSION['myprofile'] : new MyProfile(null, $base_uri);
    
    echo $profile->get_title($_REQUEST['action']);
    echo $profile->form($_REQUEST['action']);
}

include 'footer.php';
?>
