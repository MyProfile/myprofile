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

include_once 'include.php';

if (isset($_REQUEST['doit']))  {
        // store here visual alert messages: error() or success()
        $alert = '';

        // Depending on action, we prepare the local path to the user's profile dir
        if ($_REQUEST['action'] == 'edit') {
            $user_dir = webid_get_local_path($_SESSION['webid']);
            $webid_base = $base_uri . '/' . $user_dir . '/card';
            $webid = $_SESSION['webid'];
        } else {
             // prepare the new WebID URI
            $webid_base = $base_uri . '/people/' . $_REQUEST['uri'] . '/card';
            $webid = $webid_base . "#me";
            $user_dir = "people/" . $_REQUEST['uri'];
        }    
        
        // Check if the user uploaded a new picture
        if ((isset($_FILES['picture'])) && ($_FILES['picture']['error'] == 0)) {
            // Allow only pictures with a size smaller than 500k
            if ($_FILES['picture']['size'] <= 500000) {
                // Using getimagesize() to avoid fake mime types 
                $image_info = exif_imagetype($_FILES['picture']['tmp_name']);
                switch ($image_info) {
                    case IMAGETYPE_GIF:
                            if (move_uploaded_file($_FILES['picture']['tmp_name'], $user_dir . '/picture.gif'))
                                $local_img = $base_uri . '/' . $user_dir . '/picture.gif';
                            else
                                $alert .= error('Could not copy the picture to the user\'s dir. Please check permissions.');
                        break;
                    case IMAGETYPE_JPEG:
                            if (move_uploaded_file($_FILES['picture']['tmp_name'], $user_dir . '/picture.jpg'))
                                $local_img = $base_uri . '/' . $user_dir . '/picture.jpg';
                            else
                                $alert .= error('Could not copy the picture to the user\'s dir. Please check permissions.');
                        break;
                    case IMAGETYPE_PNG:
                            if (move_uploaded_file($_FILES['picture']['tmp_name'], $user_dir . '/picture.png'))
                                $local_img = $base_uri . '/' . $user_dir . '/picture.png';
                            else
                                $alert .= error('Could not copy the picture to the user\'s dir. Please check permissions.');
                        break;
                    default:
                        $alert .= error('The selected image format is not supported.');
                        break;
                }
            } else {
                $alert .= error('The image size is too large. The maximum allowed size is 30KB.');
            }
        }
        
        // Create the graph object in which we will store data
        $graph = new EasyRdf_Graph();

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
        if ((isset($_REQUEST['foaf:givenName'])) && (strlen($_REQUEST['foaf:givenName']) > 0))
            $me->set('foaf:givenName', trim($_REQUEST['foaf:givenName']));
        // last name
        if ((isset($_REQUEST['foaf:familyName'])) && (strlen($_REQUEST['foaf:familyName']) > 0))
            $me->set('foaf:familyName', trim($_REQUEST['foaf:familyName']));
        // title
        if ((isset($_REQUEST['foaf:title'])) && (strlen($_REQUEST['foaf:title']) > 0))
            $me->set('foaf:title', trim($_REQUEST['foaf:title']));
        // picture (use the uploaded one if it exists)
        if (isset($local_img))
            $me->set('foaf:img', trim($local_img));
        else if ((isset($_REQUEST['foaf:img'])) && (strlen($_REQUEST['foaf:img']) > 0)) 
            $me->set('foaf:img', $_REQUEST['foaf:img']);
        else
            $me->set('foaf:img', 'img/nouser.png');
        // nickname
        if ((isset($_REQUEST['foaf:nick'])) && (strlen($_REQUEST['foaf:nick']) > 0)) {
            $me->set('foaf:nick', trim($_REQUEST['foaf:nick']));
        }
        // email
        if (isset($_REQUEST['foaf:mbox'])) {
            foreach ($_REQUEST['foaf:mbox'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:mbox', 'mailto:' . trim($val));
            }
        }
        // email_sha1sum
        if (isset($_REQUEST['foaf:mbox_sha1sum'])) {
            foreach ($_REQUEST['foaf:mbox_sha1sum'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:mbox_sha1sum', trim($val));
            }
        }
        // sameAs
        if (isset($_REQUEST['owl:sameAs'])) {
            foreach($_REQUEST['owl:sameAs'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'owl:sameAs', trim($val));
            }
        }
        // homepage
        if (isset($_REQUEST['foaf:homepage'])) {
            foreach($_REQUEST['foaf:homepage'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:homepage', trim($val));
            }
        }
        // blogs
        if (isset($_REQUEST['foaf:weblog'])) {
            foreach($_REQUEST['foaf:weblog'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:weblog', trim($val));
            }
        }
        // work homepages
        if (isset($_REQUEST['foaf:workplaceHomepage'])) {
            foreach($_REQUEST['foaf:workplaceHomepage'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:workplaceHomepage', trim($val));
            }
        }
        // school homepages
        if (isset($_REQUEST['foaf:schoolHomepage'])) {
            foreach($_REQUEST['foaf:schoolHomepage'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:schoolHomepage', trim($val));
            }
        }     
        // current projects
        if (isset($_REQUEST['foaf:currentProject'])) {
            foreach($_REQUEST['foaf:currentProject'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:currentProject', trim($val));
            }
        }
        // past projects
        if (isset($_REQUEST['foaf:pastProject'])) {
            foreach($_REQUEST['foaf:pastProject'] as $key => $val) {
                if (strlen($val) > 0)
                    $graph->addResource($me, 'foaf:pastProject', trim($val));
            }
        }
        
// ----- foaf:knows ----- //
        if ((isset($_REQUEST['foaf:knows'])) && (strlen($_REQUEST['foaf:knows'][0]) > 0)) {
            foreach($_REQUEST['foaf:knows'] as $key => $person_uri) {
                if (strlen($person_uri) > 0) {
                    $graph->addResource($me, 'foaf:knows', $person_uri);
                }
            }
        }
   
// ----- pingback:to relation ----- //
        $graph->addResource($me, 'pingback:to', $base_uri . '/pingback.php');

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
        	$x509 = create_identity_x509($countryName, $stateOrProvinceName, $localityName, $organizationName, $organizationalUnitName, $_REQUEST['foaf:name'], $emailAddress, $foafLocation, $pubkey, SSL_CONF, CA_PASS);
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
                        'value' => '65537')
                        );
            $me->add('cert:key', $cert);
            
            // autmatically subscribe to local services
            $tiny = substr(md5(uniqid(microtime(true),true)),0,8);
            $user_hash  = substr(md5($webid), 0, 8);
		    $query = "INSERT INTO pingback SET webid='" . $webid . "', feed_hash='" . $tiny . "', user_hash='" . $user_hash . "'";
            $result = mysql_query($query);
            if (!$result) {
                $alert .= error('Unable to write to the database!');
            } else {
                mysql_free_result($result);
            }
         
            // create dirs
            if (!mkdir($user_dir, 0775))
                $alert .= ('Failed to create user profile directory! Check permissions.');
    
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
            $rw .= "RewriteRule ^card$ " . BASE_URI . "/view.php?uri=" . str_replace('%', '\%', urlencode($webid)) . " [R=303]\n";
            $rw .= "RewriteCond %{HTTP_ACCEPT} application/rdf\+xml\n";
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
            if ($ok) {
                $alert .= success('Your profile has been updated.');
                // reload the profile information
                $_SESSION['myprofile'] = new MyProfile($webid, $base_uri, SPARQL_ENDPOINT);
                $_SESSION['myprofile']->load(true);
            } else {
                $alert .= error('Could not update your profile!');
            }
       }
}

// Display form
include 'header.php';

$ret = '';

// Display an error message if we got redirected here from an IdP
if (isset($_REQUEST['error_message']))
    $ret .= error(urldecode($_REQUEST['error_message']));

// Display form view
if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = 'new';

// Display action title
$ret .= "<div class=\"container\">\n";
$ret .= "   <font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">" . ucwords($_REQUEST['action']) . " Profile</font>\n";
$ret .= "</div>\n";

// Print any error messages here
$ret .= $alert;

// Preload form fields with user's data (also reload data into the graph)
if ($_REQUEST['action'] == 'edit') {
    $graph = $_SESSION['myprofile']->get_graph();
    $profile = $_SESSION['myprofile']->get_profile();
    $ret .= "<!-- " . print_r($graph, true) . " -->\n";    
    // Set variables
    // Full name
    $name = ($profile->get('foaf:name') != null) ? $profile->get('foaf:name') : '';

    // First name
    $firstname = ($profile->get('foaf:givenName') != null) ? $profile->get('foaf:givenName') : '';

    // Lastname
    $familyname = ($profile->get('foaf:familyName')  != null) ? $profile->get('foaf:familyName') : '';

    // Picture
    $picture = $_SESSION['myprofile']->get_picture();

    // Nickname
    $nick = ($profile->get('foaf:nick') != null) ? $profile->get('foaf:nick') : '';

    // Pingback endpoint
    $pingback = ($profile->get('pingback:to') != null) ? $profile->get('pingback:to') : '';

    // Multiple values 
    // Email addresses
    $emails = '';
    if ($profile->get('foaf:mbox') != null) {
        foreach ($profile->all('foaf:mbox') as $email) {
            $emails .= "<tr><td>Email: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . clean_mail($email) . "\" name=\"foaf:mbox[]\"></td></tr>\n";
        }
    } else {
        // Should still display the email field by default
        $emails .= "<tr><td>Email: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"\" name=\"foaf:mbox[]\"></td></tr>\n";
    }
    
    // SHA1 sums
    $sha1sums = '';
    if ($profile->get('foaf:mbox_sha1sum') != null) {
        foreach ($profile->all('foaf:mbox_sha1sum') as $sha1)
            $sha1sums .= "<tr><td>Email SHA1SUM: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $sha1 . "\" name=\"foaf:mbox_sha1sum[]\"></td></tr>\n";
    } 
    
    // sameAs
    $sameAs = '';
    if ($profile->get('owl:sameAs') != null) {
         foreach ($profile->all('owl:sameAs') as $same)
            $sameAs .= "<tr><td>Additional profile: </td><td><input type=\"text\" size=\"50\" value=\"" . $same . "\" name=\"owl:sameAs[]\"></td></tr>\n";
    } 
    
    // Homepages
    $homepages = '';
    if ($profile->get('foaf:homepage') != null) {
         foreach ($profile->all('foaf:homepage') as $homepage)
            $homepages .= "<tr><td>Homepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $homepage . "\" name=\"foaf:homepage[]\"></td></tr>\n";
    } 
    
    // Blogs
    $blogs = '';
    if ($profile->get('foaf:weblog') != null) {
         foreach ($profile->all('foaf:weblog') as $blog)
            $blogs .= "<tr><td>Blog: </td><td><input type=\"text\" size=\"50\" value=\"" . $blog . "\" name=\"foaf:weblog[]\"></td></tr>\n";
    } 
    
    // Work Homepages
    $workHPS = '';
    if ($profile->get('foaf:workplaceHomepage') != null) {
         foreach ($profile->all('foaf:workplaceHomepage') as $workHP)
            $workHPS .= "<tr><td>WorkplaceHomepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $workHP . "\" name=\"foaf:workplaceHomepage[]\"></td></tr>\n";
    } 
    
    // School Homepages
    $schoolHPS = '';
    if ($profile->get('foaf:schoolHomepage') != null) {
         foreach ($profile->all('foaf:schoolHomepage') as $schoolHP)
            $schoolHPS .= "<tr><td>SchoolHomepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $schoolHP . "\" name=\"foaf:schoolHomepage[]\"></td></tr>\n";
    } 
    
    // Current Projects
    $curprojs = '';
    if ($profile->get('foaf:currentProject') != null) {
         foreach ($profile->all('foaf:currentProject') as $curproj)
            $curprojs .= "<tr><td>CurrentProject: </td><td><input type=\"text\" size=\"50\" value=\"" . $curproj . "\" name=\"foaf:currentProject[]\"></td></tr>\n";
    } 
    
    // Past Projects
    $pastprojs = '';
    if ($profile->get('foaf:pastProject') != null) {
         foreach ($profile->all('foaf:pastProject') as $pastproj)
            $pastprojs .= "<tr><td>PastProject: </td><td><input type=\"text\" size=\"50\" value=\"" . $pastproj . "\" name=\"foaf:pastProject[]\"></td></tr>\n";
    } 

    // Friends
    $knows = '';
    if ($profile->get('foaf:knows') != null) {
         foreach ($profile->all('foaf:knows') as $friend)
            $knows .= "<tr><td>Person: </td><td><input type=\"text\" size=\"70\" value=\"" . $friend . "\" name=\"foaf:knows[]\"></td></tr>\n";
    } else {
        $knows .= "<tr><td>Person: </td><td><input type=\"text\" size=\"70\" placeholder=\"https://my-profile.eu/people/deiu/card#me\" name=\"foaf:knows[]\"></td></tr>\n";
    }
    
    // Certs
    $certs = '';
    if ($profile->get('cert:key') != null) {
        foreach ($graph->allOfType('cert:RSAPublicKey') as $cert) {
            $hex = preg_replace('/\s+/', '', strtolower($cert->get('cert:modulus')));
            $int = $cert->get('cert:exponent');
            
            $certs .= "<tr>\n";
            $certs .= "   <td>Modulus: </td>\n";
            $certs .= "   <td>\n";
            $certs .= " <!-- hex=" . $cert->get('cert:modulus') . " -->\n";
            $certs .= "       <table>\n";
            $certs .= "       <tr>\n";
            $certs .= "          <td><textarea style=\"height: 130px;\" onfocus=\"textAreaResize(this)\" name=\"modulus[]\">" . $hex . "</textarea></td>\n";
            $certs .= "          <td> Exponent: <input type=\"text\" size=\"10\" value=\"" . $int . "\" name=\"exponent[]\"></td>\n";
            $certs .= "       </tr>\n";
            $certs .= "       </table>\n";
            $certs .= "   </td>\n";
            $certs .= "</tr>\n";
        }
    }
} else {
    // Set some default values
    $picture = '/img/nouser.png';
}

// rdf types for Person
$values_person = array('foaf:mbox' => 'Email (mbox)',
                'foaf:mbox_sha1sum' => 'Email SHA1',
                'owl:sameAs' => 'Additional profile (sameAs)',
                'foaf:homepage' => 'Homepage',
                'foaf:weblog' => 'Blog',
                'foaf:workplaceHomepage' => 'Workplace homepage',
                'foaf:schoolHomepage' => 'School homepage',
                'foaf:currentProject' => 'Current project URL',
                'foaf:pastProject' => 'Past project URL'
                );
// rdf types for friends
$values_friends = array('foaf:knows' => 'Friend\'s profile address');

// rdf types for accounts
$values_accounts = array('foaf:holdsAccount' => 'Account');

// rdf types for security
$values_security = array('rsa#RSAPublicKey' => 'Certificate Public Key');

$months = array('01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
                );

$ret .= "<div class=\"container\"><br/>\n";
if ($_REQUEST['action'] != 'edit')
    $ret .= "   <p><font style=\"font-size: 1em;\"><strong>Warning:</strong> do not try to refresh the page after submitting the form!</font></p>\n";
$ret .= "   <form action=\"profile.php\" name=\"form_build\" method=\"post\" enctype=\"multipart/form-data\">\n";
$ret .= "   <input type=\"hidden\" name=\"action\" value=\"" . $_REQUEST['action'] . "\">\n";
$ret .= "   <input type=\"hidden\" name=\"doit\" value=\"1\">\n";
$ret .= "   <input type=\"hidden\" name=\"foaf:img\" value=\"" . $picture . "\">\n";
$ret .= "   <div id=\"tabs\">\n";
$ret .= "       <ul class=\"nav nav-tabs\">\n";
$ret .= "           <li class=\"active\"><a data-toggle=\"tab\" href=\"#tabs-1\">Personal information</a></li>\n";
// Interests tab is disabled for now
//$ret .= "             <li><a href=\"#tabs-2\">Interests</a></li>\n";
$ret .= "           <li><a data-toggle=\"tab\" href=\"#tabs-3\">Friends</a></li>\n";
$ret .= "           <li><a data-toggle=\"tab\" href=\"#tabs-5\">Keys</a></li>\n";
$ret .= "       </ul>\n";

$ret .= "<div class=\"tab-content\" style=\"padding-left: 2em;\">\n";
$ret .= "   <div class=\"tab-pane active\" id=\"tabs-1\">\n";
if ($_REQUEST['action'] == 'new') {
    $ret .= "<p>You must provide both username and full name. (accepted characters: a-z 0-9 _ . -)";
    $ret .= "<br/>Your WebID profile will be accessible at: <font color=\"#00BBFF\" style=\"font-size: 1.3em;\">" . $base_uri . "/people/</font>";
    $ret .= "<b>&lt;username&gt;</b><font color=\"#00BBFF\" style=\"font-size: 1.3em;\"> /card#me</font></p>\n";
}
/* ----- Username ------ */
$ret .= "<table id=\"tab1\" border=\"0\" valign=\"middle\">\n";
// Inner table contains text fields to the left and picture to the right
$ret .= "<tr><td>\n";
$ret .= "<table id=\"info\">\n";
// Display username only if we're creating a new profile
if (($_REQUEST['action'] == 'new') || ($_REQUEST['action'] == 'import')) {
    $ret .= "<tr valign=\"middle\">\n";
    $ret .= "<td>Username: </td>\n";
    $ret .= "<td valign=\"top\"><input type=\"text\" size=\"50\" value=\"\" id=\"uri\" name=\"uri\" maxlength=\"32\" onBlur=\"validateReq('" . $base_uri . "/people/', 'uri', 'fullname', 'submit')\">";
    $ret .= " <font color=\"" . $color . "\"> </font></td>\n";
    $ret .= "</tr>\n";
}
/* ----- Full name ------ */
$ret .= "<tr><td>Full name: </td>\n";
$ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $name . "\" id=\"fullname\" name=\"foaf:name\" onBlur=\"validateReq('" . $base_uri . "/people/', 'uri', 'fullname', 'submit')\"></td>\n";
$ret .= "</tr>\n";
/* ----- KEYGEN ------ */
if (($_REQUEST['action'] == 'new') || ($_REQUEST['action'] == 'import')) {
    $ret .= "<tr hidden>\n";
    $ret .= "<td hidden>KEYGEN Key Length</td>\n";
    $ret .= "<td hidden><keygen id=\"pubkey\" name=\"pubkey\" challenge=\"randomchars\"  style=\"border-color: red;\" hidden></td>\n";
    $ret .= "</tr>\n";
}
/* ----- Firstname ------ */
$ret .= "<tr id=\"firstname\">\n";
$ret .= "<td>Firstname: </td>\n";
$ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $firstname . "\" name=\"foaf:givenName\"></td>\n";
$ret .= "</tr>\n";
/* ----- Lastname ------ */
$ret .= "<tr id=\"lastname\">\n";
$ret .= "<td>Lastname: </td>\n";
$ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $familyname . "\" name=\"foaf:familyName\"></td>\n";
$ret .= "</tr>\n";
/* ----- Nickname ------ */
$ret .= "<tr id=\"nickname\">\n";
$ret .= "<td>Nickname: </td>\n";
$ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $nick . "\" name=\"foaf:nick\"></td>\n";
$ret .= "</tr>\n";

/* ----- PERSONAL ------ */

// Add more personal info
$ret .= $emails;
$ret .= $sha1sums;
$ret .= $sameAs;
$ret .= $homepages;
$ret .= $blogs;
$ret .= $workHPS;
$ret .= $schoolHPS;
$ret .= $curprojs;
$ret .= $pastprojs;
$ret .= "</table>\n";
$ret .= "</td>\n";

$ret .= "<td width=\"100\"></td>\n";

/* ----- Picture ------ */
// Here we display the profile picture (avatar)
$ret .= "<td valign=\"top\">\n";
$ret .= "<img width=\"150\" src=\"" . $picture . "\"/>\n";
$ret .= "<input name=\"picture\" type=\"file\" size=\"10\">\n";
$ret .= "</td>\n";

$ret .= "</tr>\n";    
$ret .= "</table>\n";

$ret .= "<p><select name=\"element_tab1\">\n";
foreach($values_person as $key => $value)
    $ret .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
$ret .= "</select>\n";
$ret .= "<input type=\"button\" class=\"btn\" value=\"Add extra info\" onclick=\"addInfo(document.form_build.element_tab1.value, 'info')\"/></p>\n";
$ret .= "</div>\n";

/* ----- KNOWS ------ */  
$ret .= "<div class=\"tab-pane\" id=\"tabs-3\">\n";
$ret .= "<p>Here you can add links to your friends profiles. <font color=\"grey\"><small>[click the button to add more]</small></font><br/>\n";
$ret .= "<small><font color=\"grey\">If you don't have any friends yet, try adding Andrei: <strong>https://my-profile.eu/people/deiu/card#me</strong></font></small></p>\n";
$ret .= "<table id=\"tab3\" border=\"0\">\n";
$ret .= $knows;
$ret .= "</table>\n";
$ret .= "<p><select name=\"element_tab3\">\n";
foreach($values_friends as $key => $value)
    $ret .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
$ret .= "</select>\n";
$ret .= " <input type=\"button\" class=\"btn\" value=\"Add element\" onclick=\"addFriends(document.form_build.element_tab3.value, 'tab3')\"/></p>\n";
$ret .= "</div>\n";
/* ----- Public keys ------ */
$ret .= "<div class=\"tab-pane\" id=\"tabs-5\">\n";
$ret .= "<p>Here you can provide your public keys and certificate information. <font color=\"grey\"><small>[click the button to add more]</small></font><br/>\n";
$ret .= "<font color=\"grey\"><small>[for certificates: Modulus (hexa):<i>95 be 46 ff ...  61 d2 8a</i> Exponent (decimal):<i>65537</i></small></font></p>\n";
$ret .= "<table id=\"tab5\" border=\"0\" valign=\"top\">\n";
$ret .= $certs;
$ret .= "</table>\n";
$ret .= "<p><select name=\"element_tab5\">\n";
foreach($values_security as $key => $value)
    $ret .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
$ret .= "</select>\n";
$ret .= "<input type=\"button\" class=\"btn\" value=\"Add element\" onclick=\"addSecurity(document.form_build.element_tab5.value, 'tab5')\"/></p>\n";
$ret .= "</div>\n";

$ret .= "</div>\n"; // end of <div class="tab-content">
$ret .= "</div>\n";
$ret .= "<br/><br/>\n";
$ret .= "<p><input class=\"btn btn-primary\" type=\"submit\" id=\"submit\" name=\"submit\" value=\"" . ucwords($_REQUEST['action']) . " profile\"";
// Disable the submit button if we need to check if user already exists
if (($_REQUEST['action'] == 'new') || ($_REQUEST['action'] == 'import')) {
    $ret .= " disabled>\n";
    $ret .= "<font color=\"grey\">[Note: a certificate will also be issued and installed in your browser]</font></p>\n";
} else {
    $ret .= ">\n";    
}
$ret .= "</form>\n";
$ret .= "</div>\n";

echo $ret;

include 'footer.php';
?>
