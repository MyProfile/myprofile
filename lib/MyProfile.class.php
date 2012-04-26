<?php
if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

/*
 * Profile class
 * Methods: 
 * load() - returns true if successful
 * success($string) - returns html with visual confirmation element
 * error($string) - retuns html with visual confirmation element
 * is_local($webid) - returns true if the profile is local
 * get_local_path($webid) - returns the patch to the local profile (e.g. 'people/username')
 * add_friend($uri, $user_dir, $format='rdfxml') - returns html with visual confirmation
 * del_friend($uri, $user_dir, $format='rdfxml') - returns html with visual confirmation
 * delete_account() - returns true
 * subscribe() - returns html with visual confirmation element
 * unsubscribe() - returns html with visual confirmation element
 * show_wall($user_hash) - returns html with all messages belonging to a user
 * get_title() - returns html with the name of the owner
 * form() - returns html form for managing user profile data
*/ 
class MyProfile {

    private $webid;
    private $base_uri;
    private $cache_dir;
    private $primarytopic;
    private $graph;
    private $profile;
    private $name;
    private $picture;
    private $feed_hash;
    private $user_hash;
    private $firstname;
    private $familyname;
    private $title;
    private $pic;
    private $nick;
    private $pingback;
    private $emails;
    private $sha1sums;
    private $homepages;
    private $blogs;
    private $workHPS;
    private $schoolHPS;
    private $curprojs;
    private $pastprojs;
    private $knows;
    private $interests;
    private $certs;

    // Build the selectors for adding more form content
    function __construct($webid, $base_uri) {
        $this->webid = $webid;
        
        if (isset($base_uri))
            $this->base_uri = $base_uri;
        
        $this->cache_dir = 'cache/';
    }
    
    // Load profile data for the specified WebID
    // returns true
    function load() {
        // Load the RDF graph data
        $graph = new Graphite();
        $graph->load($this->webid);
        $graph->cacheDir($this->cache_dir);

        // try to get primary topic, else go with default uri (some people don't use #)
        $pt = $graph->resource('foaf:PersonalProfileDocument');
        $this->primarytopic = $pt->get('foaf:primaryTopic');
        if ($this->primarytopic != '[NULL]') 
            $profile = $graph->resource($this->primarytopic);
        else
            $profile = $graph->resource($this->webid);
        
        $this->graph = $graph;
        $this->profile = $profile;
    
        // get user's name and picture info for display purposes    
        $this->name = $profile->get('foaf:name');
        if ($this->name == '[NULL]')
        // combine firstname and lastname if name is null
        if ($this->name == '[NULL]') {
            $first = $profile->get('foaf:givenName');
            $last = $profile->get('foaf:familyName');

            $name = ''; 
            if ($first != '[NULL]')
                $name .= $first . ' ';
            if ($last != '[NULL]')
                $name .= $last;
            if (strlen($name) > 0)
                $this->name = $name;
            else
                $this->name = 'Anonymous';
        }

        // get the user's picture
        if ($profile->get('foaf:img') != '[NULL]')
            $this->picture = $profile->get('foaf:img'); 
        else if ($profile->get('foaf:depiction') != '[NULL]')
            $this->picture = $profile->get('foaf:depiction');
        else
            $this->picture = 'img/nouser.png'; // default image
            
        // get user hash and feed hash
        $result = mysql_query("SELECT feed_hash, user_hash FROM pingback WHERE webid='" . mysql_real_escape_string($this->webid) . "'");
        if (!$result) {
            die('Unable to connect to the database!');
        } else if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $this->feed_hash = $row['feed_hash'];
            $this->user_hash = $row['user_hash'];
            mysql_free_result($result);
        }
        return true;
    }
    
    // get the user's raw graph object
    function get_graph() {
        return $this->graph;
    }
    
    // get the user's raw profile object
    function get_profile() {
        return $this->profile;
    }

    // get the user's profile object
    function get_primarytopic() {
        return $this->primarytopic;
    }
    
    // get the user's feed hash (feed id)
    function get_feed() {
        return $this->feed_hash;
    }
    
    // get the user's hash (local id)
    function get_hash() {
        return $this->user_hash;
    }
    
    // get the user's full name
    function get_name() {
        return $this->name;
    }
    
    // get the user's picture
    function get_picture() {
        return $this->picture;
    }
    
    // Display a pretty visual success message
    // returns string
    function success($text) {
        $ret = "<br/><div class=\"ui-widget\" style=\"position:relative; width: 820px;\">\n";
        $ret .= "<div class=\"ui-state-highlight ui-corner-all\">\n";
        $ret .= "<p><span class=\"ui-icon ui-icon-info\" style=\"float: left; margin-right: .3em;\"></span>\n";
        $ret .= "<strong>Success!</strong> " . $text;
        $ret .= "</div></div>\n";

        return $ret;
    }

    // Display a pretty visual error message
    // returns string
    function error($text) {
        $ret = "<br/><div class=\"ui-widget\" style=\"position:relative; width: 820px;\">\n";
        $ret .= "<div class=\"ui-state-error ui-corner-all\">\n";
        $ret .= "<p><span class=\"ui-icon ui-icon-alert\" style=\"float: left; margin-right: .3em;\"></span>\n";
        $ret .= "<strong>Error!</strong> " . $text;
        $ret .= "</div></div>\n";

        return $ret;
    } 
       
    // check if the given webid is in the user's list of foaf:knows
    function is_friend($webid) {
        if (!isset($this->profile)) {
            $this->load();
        }
        $profile = $this->profile;        
        $friends = explode(',', $profile->all('foaf:knows')->join(','));
        if (in_array($webid, $friends))
            return true;
        else
            return false;
    }
    
    // Checks if the webid is a local and return the corresponding account name
    // returns string if true, otherwise false
    function is_local($webid) {
        $webid = (isset($webid)) ? $webid : $this->webid;
        if (strstr($webid, $_SERVER['SERVER_NAME']))
            return true;
        else
            return false;
    }
    
    // get local path for user (if local)
    // returns the user's local path (e.g. people/username), otherwise false
    function get_local_path($webid) {
        // verify if it's a local user or not
        if ($this->is_local($webid)) {
            $location = strstr($webid, $_SERVER['SERVER_NAME']);
            $path = explode('/', $location);
            $path = $path[1] . "/" . $path[2];
            return $path;
        } else {
            return false;
        }
    }
  
    // Add a foaf:knows relation to the graph
    // returns a visual confirmation in html
    function add_friend($uri, $format='rdfxml') {
        $uri = urldecode($uri);
        $path = $this->get_local_path($this->webid);
        
        // Create the new graph object in which we store data
        $graph = new EasyRdf_Graph($this->webid);
        $graph->load();
        $me = $graph->resource($this->webid);
        $me->add('foaf:knows', trim($uri));
        
        // reserialize graph
        $data = $graph->serialise($format);
        if (!is_scalar($data))
            $data = var_export($data, true);
        else
            $data = print_r($data, true);
        // write profile to file
        $pf = fopen($path . '/foaf.rdf', 'w') or $this->error('Cannot open profile RDF file!');
        fwrite($pf, $data);
        fclose($pf);    
        
        $pf = fopen($path . '/foaf.txt', 'w') or $this->error('Cannot open profile PHP file!');
        fwrite($pf, $data);
        fclose($pf);

        // everything is fine
        return $this->success("You have just added " . $uri . " to your friends.");
    }
    
    // remove a foaf:knows relation
    // returns a visual confirmation in html
    function del_friend($uri, $format='rdfxml') {
        $uri = urldecode($uri);
        $path = $this->get_local_path($this->webid);

        // Create the new graph object in which we store data
        $graph = new EasyRdf_Graph($this->webid);
        $graph->load();
        $graph->delete($graph->resource($this->webid), 'foaf:knows', trim($uri));
        
        // write profile to file
        $data = $graph->serialise($format);
        if (!is_scalar($data))
            $data = var_export($data, true);
        else
            $data = print_r($data, true);

        $pf = fopen($path . '/foaf.rdf', 'w') or die('Cannot open profile RDF file!');
        fwrite($pf, $data);
        fclose($pf);    
        
        $pf = fopen($path . '/foaf.txt', 'w') or die('Cannot open profile PHP file!');
        fwrite($pf, $data);
        fclose($pf);

        // everything is fine
        return $this->success("You have just removed " . $uri . " from your friends.");
    }
    
    // delete user from database
    // return true or false if failure
    function delete_account() {
        $webid = mysql_real_escape_string($this->webid);
        
        $result = mysql_query("DELETE FROM pingback WHERE webid='" . $webid . "'");
        if (!$result) {
            return false;       
        } else {
            mysql_free_result($result);
        }
        
        $result = mysql_query("DELETE FROM pingback_messages WHERE from_uri='" . $webid . "'");
        if (!$result) {
            return false;       
        } else {
            mysql_free_result($result);
        }
        return true;
    }
    
    // subscribe to local services
    // returns a visual confirmation in html
    function subscribe() {
        $webid      = $this->webid;
        $feed_hash  = substr(md5(uniqid(microtime(true),true)),0,8);
        $user_hash  = substr(md5($webid), 0, 8);

        $this->feed_hash = $feed_hash;
        $this->user_hash = $user_hash;        
                
        // write webid uri to database
        $query = "INSERT INTO pingback SET webid='" . mysql_real_escape_string($webid) . "', feed_hash='" . mysql_real_escape_string($feed_hash) . "', user_hash='" . mysql_real_escape_string($user_hash) . "'";
        $result = mysql_query($query);

        if (!$result) {
            return $this->error('Unable to connect to the database!');
        } else {
            if ($result !== true) {
                mysql_free_result($result);
            }
            return $this->success('You have successfully subscribed to local services.');
        }
    }
    
    // unsubscribe from local services
    // returns a visual confirmation in html
    function unsubscribe() {
        $query = "DELETE FROM pingback WHERE webid='" . mysql_real_escape_string($this->webid) . "'";
        $result = mysql_query($query);
        if (!$result) {
            return $this->error('Unable to connect to the database!');
        } else { 
            // delete any pingbacks addressed to me
            $query = "DELETE FROM pingback_messages WHERE to_uri='" . mysql_real_escape_string($this->webid) . "'";
            $result = mysql_query($query);
            if (!$result) {
                return $this->error('Unable to connect to the database!');
            } else {
                mysql_free_result($result);
                return $this->success('Your WebID has been successfully unregistered.');
            }
        }
    }
    
    // Display Wall messages
    // return html with all messages belonging to the given user
    function show_wall($user_hash) {
        // get the last 100 messages
        $query = "SELECT * FROM pingback_messages WHERE to_hash='" . mysql_real_escape_string($user_hash) . "' AND wall='1' ORDER by date DESC LIMIT 100";
        $result = mysql_query($query);

        $ret = '';
        if (!$result) {
            $ret .= $this->error('Unable to connect to the database!');
        } else if (mysql_num_rows($result) == 0){
            $ret .= "<p><font style=\"font-size: 1.3em;\">There are no messages.</font></p>\n";
        } else {
            $ret .= "<form name=\"view_wall\" method=\"GET\" action=\"\">\n";
            $ret .= "<input type=\"hidden\" name=\"user\" value=\"" . htmlspecialchars($user_hash) . "\">\n";    
            $ret .= "<table border=\"0\">\n";
                
            // populate table
            $i = 0;
            while ($row = mysql_fetch_assoc($result)) {
                // Get name
                $name = $row['name'];
                if ($name == '[NULL]')
                    $name = $row['name'];
                // Get picture
                $pic = $row['pic'];
                // Get the date and multiply by 1000 for milliseconds, otherwise moment.js breaks
                $timestamp = $row['date'] * 1000;

                $text = htmlspecialchars($row["msg"]);

                // add horizontal line to separate messages
                $ret .= "<tr><td colspan=\"2\">\n";
                $ret .= "<a name=\"post_" . $row['id'] . "\"><hr style=\"border: none; height: 1px; color: #cccccc; background: #cccccc;\"/></a>\n";
                $ret .= "</td></tr>\n";

                $ret .= "<tr valign=\"top\" property=\"sioc:Post\">\n";
                $ret .= "<td width=\"80\" align=\"center\">\n";
                // image
                $ret .= "<a href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\"><img title=\"" . $name . "\" alt=\"" . $name . "\" width=\"48\" src=\"" . $pic . "\" style=\"padding: 0px 0px 10px;\" /></a>\n";
                $ret .= "</td>\n";
                $ret .= "<td>";
                $ret .= "<table style=\"width: 700px;\" border=\"0\">\n";
                $ret .= "<tr valign=\"top\">\n";
                $ret .= "<td>\n";
                // author's name
                $ret .= "<b><a href=\"view.php?uri=" . urlencode($row['from_uri']) . "\" target=\"_blank\" style=\"font-color: black;\">" . $name . "</a></b>";
                // time of post
                $ret .= "<font color=\"grey\"> wrote <span id=\"date_" . $row['id'] . "\">";
                $ret .= "<script type=\"text/javascript\">$('#date_" . $row['id'] . "').text(moment(" . $timestamp . ").from());</script>";
                $ret .= "</span></font>\n";
                $ret .= "</td>\n";
                $ret .= "</tr>\n";
                $ret .= "<tr>\n";
                // message
                $ret .= "<td><p><pre id=\"message_" . $row['id'] . "\"><span id=\"message_text_" . $row['id'] . "\">" . put_links($text) . "</span></pre></p></td>\n";
                $ret .= "</tr>\n";
                $ret .= "<tr>\n";
                $ret .= "<td><small>";
                // show options only if we are the source of the post
                if (
                    isset($_SESSION['webid'])
                    && (
                        ($_SESSION['webid'] == $row['from_uri'])
                        || (
                            ($_SESSION['webid'] == $row['to_uri'])
                            && (isset($_REQUEST['user']))
                            && ($_REQUEST['user'] != 'local')
                        )
                    )
                ) {
                    $add = '?user=' . $user_hash;
                    // add option to edit post
                    $ret .= "<a onClick=\"updateWall('message_text_" . $row['id'] . "', 'wall.php" . $add . "', '" . $row['id'] . "')\" style=\"cursor: pointer;\">Edit</a>";
                    // add option to delete post
                    $ret .= " <a href=\"wall.php" . $add . "&del=" . $row['id'] . "\">Delete</a>\n";
                }
                $ret .= "</small></td>\n";
                $ret .= "</tr>\n";
                $ret .= "</table>\n";
                $ret .= "</td>\n";
                $ret .= "</tr>\n";
            $i++; 
            }
            mysql_free_result($result);

            $ret .= "</table>\n";
            $ret .= "</form>\n"; 
        }
        
        return $ret;
    }    
    
    // Set title for the form
    // returns html
    function get_title($action) {
        $ret = "";
        $ret .= "<div class=\"container\">\n";
    	$ret .= "   <font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">" . ucwords($action) . " Profile</font>\n";
    	$ret .= "</div>\n";
        return $ret;
    }	

    // display a form for managing the user's profile data
    // returns html
    function form($action) {
        // preload form fields with user's data (also reload data into the graph)
        if (($action == 'edit') && ($this->load())) {
            $graph = $this->graph;
            $profile = $this->profile;
        
            // Set variables
            // Full name
            $this->name = ($profile->get('foaf:name') != '[NULL]') ? $profile->get('foaf:name') : '';

            // First name
            $this->firstname = ($profile->get('foaf:givenName') != '[NULL]') ? $profile->get('foaf:givenName') : '';

            // Lastname
            $this->familyname = ($profile->get('foaf:familyName')  != '[NULL]') ? $profile->get('foaf:familyName') : '';

            // Title
            $this->title = ($profile->get('foaf:title') != '[NULL]') ? $profile->get('foaf:title') : '';

            // Picture
            if ($profile->get('foaf:img') != '[NULL]')
                $this->pic = $profile->get('foaf:img');
            else if ($profile->get('foaf:depiction') != '[NULL]')
                $this->pic = $profile->get('foaf:depiction');
            else
                $this->pic = '';

            // Nickname
            $this->nick = ($profile->get('foaf:nick') != '[NULL]') ? $profile->get('foaf:nick') : '';

            // Pingback endpoint
            $this->pingback = ($profile->get('pingback:to') != '[NULL]') ? $profile->get('pingback:to') : '';
                
            // multiple
            // Email addresses
            $this->emails = '';
            if ($profile->get("foaf:mbox") != '[NULL]') {
                foreach ($profile->all('foaf:mbox') as $email) {
                    $email = explode(':', $email);
                    $this->emails .= "<tr><td>Email: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $email[1] . "\" name=\"foaf:mbox[]\"></td><td> (foaf:mbox)</td></tr>\n";
                }
            } else {
                    $this->emails .= "<tr><td>Email: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"\" name=\"foaf:mbox[]\"></td><td> (foaf:mbox)</td></tr>\n";
            }
            
            // SHA1 sums
            $this->sha1sums = '';
            if ($profile->get("foaf:mbox_sha1sum") != '[NULL]') {
                foreach ($profile->all('foaf:mbox_sha1sum') as $sha1)
                    $sha1sums .= "<tr><td>Email SHA1SUM: </td><td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $sha1 . "\" name=\"foaf:mbox_sha1sum[]\"></td><td> (foaf:mbox_sha1sum)</td></tr>\n";
            } 
            
            // Homepages
            $this->homepages = '';
            if ($profile->get("foaf:homepage") != '[NULL]') {
                 foreach ($profile->all('foaf:homepage') as $homepage)
                    $this->homepages .= "<tr><td>Homepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $homepage . "\" name=\"foaf:homepage[]\"></td><td> (foaf:homepage)</td></tr>\n";
            } 
            
            // Blogs
            $this->blogs = '';
            if ($profile->get("foaf:weblog") != '[NULL]') {
                 foreach ($profile->all('foaf:weblog') as $blog)
                    $this->blogs .= "<tr><td>Blog: </td><td><input type=\"text\" size=\"50\" value=\"" . $blog . "\" name=\"foaf:weblog[]\"></td><td> (foaf:weblog)</td></tr>\n";
            } 
            
            // Work Homepages
            $this->workHPS = '';
            if ($profile->get("foaf:workplaceHomepage") != '[NULL]') {
                 foreach ($profile->all('foaf:workplaceHomepage') as $workHP)
                    $this->workHPS .= "<tr><td>WorkplaceHomepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $workHP . "\" name=\"foaf:workplaceHomepage[]\"></td><td> (foaf:workplaceHomepage)</td></tr>\n";
            } 
            
            // School Homepages
            $this->schoolHPS = '';
            if ($profile->get("foaf:schoolHomepage") != '[NULL]') {
                 foreach ($profile->all('foaf:schoolHomepage') as $schoolHP)
                    $this->schoolHPS .= "<tr><td>SchoolHomepage: </td><td><input type=\"text\" size=\"50\" value=\"" . $schoolHP . "\" name=\"foaf:schoolHomepage[]\"></td><td> (foaf:schoolHomepage)</td></tr>\n";
            } 
            
            // Current Projects
            $this->curprojs = '';
            if ($profile->get("foaf:currentProject") != '[NULL]') {
                 foreach ($profile->all('foaf:currentProject') as $curproj)
                    $this->curprojs .= "<tr><td>CurrentProject: </td><td><input type=\"text\" size=\"50\" value=\"" . $curproj . "\" name=\"foaf:currentProject[]\"></td><td> (foaf:currentProject)</td></tr>\n";
            } 
            
            // Past Projects
            $this->pastprojs = '';
            if ($profile->get("foaf:pastProject") != '[NULL]') {
                 foreach ($profile->all('foaf:pastProject') as $pastproj)
                    $this->pastprojs .= "<tr><td>PastProject: </td><td><input type=\"text\" size=\"50\" value=\"" . $pastproj . "\" name=\"foaf:pastProject[]\"></td><td> (foaf:pastProject)</td></tr>\n";
            } 

            // Friends
            $this->knows = '';
            if ($profile->get("foaf:knows") != '[NULL]') {
                 foreach ($profile->all('foaf:knows') as $friend)
                    $this->knows .= "<tr><td>Person: </td><td><input type=\"text\" size=\"70\" value=\"" . $friend . "\" name=\"foaf:knows[]\"> (foaf:knows)</td></tr>\n";
            } else {
                $this->knows .= "<tr><td>Person: </td><td><input type=\"text\" size=\"70\" value=\"\"  placeholder=\"http://fcns.eu/people/andrei/card#me\" name=\"foaf:knows[]\"> (foaf:knows)</td></tr>\n";
            }
            
            // Interests
            $this->interests = '';
            if ($profile->get("foaf:interest") != '[NULL]') {
                foreach ($profile->all('foaf:interest') as $interest) {
                    // each interest is a separate resource
                    $interest = $graph->resource($interest);
                    $label = ($interest->label() == '[NULL]') ? $interest->toString() : $interest->label();
                    $this->interests .= "<tr><td>Interest name: </td><td><input type=\"text\" size=\"20\" value=\"" . $label . "\" name=\"dc:title[]\"> URI: <input type=\"text\" size=\"40\" value=\"" . $interest->toString() . "\" name=\"foaf:interest[]\"> (foaf:interest)</td></tr>\n";
                }
            } else {
                $this->interests .= "<tr><td>Interest name: </td><td><input type=\"text\" placeholder=\"Movies..\" size=\"20\" value=\"\" name=\"dc:title[]\"> URI: <input type=\"text\" size=\"40\" placeholder=\"http://...\" value=\"\" name=\"foaf:interest[]\"> (foaf:interest)</td></tr>\n";
            }

            // Certs
            $this->certs = '';
            if ($profile->get("cert:key") != '[NULL]') {
                foreach ($graph->allOfType('cert:RSAPublicKey') as $cert) {
                    $hex = preg_replace('/\s+/', '', strtolower($cert->get('cert:modulus')));
                    $int = $cert->get('cert:exponent');
                    
                    $this->certs .= "<tr>\n";
                    $this->certs .= "   <td>Modulus: </td>\n";
                    $this->certs .= "   <td>\n";
                    $this->certs .= "       <table>\n";
                    $this->certs .= "       <tr>\n";
                    $this->certs .= "          <td><textarea style=\"height: 130px;\" name=\"modulus[]\">" . $hex . "</textarea></td>\n";
                    $this->certs .= "          <td> Exponent: <input type=\"text\" size=\"10\" value=\"" . $int . "\" name=\"exponent[]\"></td>\n";
                    $this->certs .= "       </tr>\n";
                    $this->certs .= "       </table>\n";
                    $this->certs .= "   </td>\n";
                    $this->certs .= "</tr>\n";
                }
            } else {
                foreach ($graph->allOfType('cert:RSAPublicKey') as $cert) {
                    $hex = preg_replace('/\s+/', '', strtolower($cert->get('cert:modulus')));
                    $int = $cert->get('cert:exponent');
                    if ($hex == '[NULL]')
                        $hex = '';
                    if ($int == '[NULL]')
                        $int = '';
                    
                    $this->certs .= "<tr>\n";
                    $this->certs .= "   <td>Modulus: </td>\n";
                    $this->certs .= "   <td>\n";
                    $this->certs .= "       <table>\n";
                    $this->certs .= "       <tr>\n";
                    $this->certs .= "          <td><textarea style=\"height: 130px;\" name=\"modulus[]\">" . $hex . "</textarea></td>\n";
                    $this->certs .= "          <td> Exponent: <input type=\"text\" size=\"10\" value=\"" . $int . "\" name=\"exponent[]\"></td>\n";
                    $this->certs .= "       </tr>\n";
                    $this->certs .= "       </table>\n";
                    $this->certs .= "   </td>\n";
                    $this->certs .= "</tr>\n";
                }
            }
        }

        // rdf types for Person
        $values_person = array("foaf:mbox" => "Email",
                        "foaf:mbox_sha1sum" => "Email SHA1",
                        "foaf:homepage" => "Homepage",
                        "foaf:weblog" => "Blog",
                        "foaf:workplaceHomepage" => "Workplace homepage",
                        "foaf:schoolHomepage" => "School homepage",
                        "foaf:currentProject" => "Current project URL",
                        "foaf:pastProject" => "Past project URL"
                        );
        // rdf types for friends
        $values_friends = array("foaf:knows" => "Friend's profile");
        
        // rdf types for interest
        $values_interest = array("foaf:interest" => "Interest");

        // rdf types for accounts
        $values_accounts = array("foaf:holdsAccount" => "Account");

        // rdf types for security
        $values_security = array("rsa#RSAPublicKey" => "Certificate Public Key");

        $months = array("01" => "January",
                        "02" => "February",
                        "03" => "March",
                        "04" => "April",
                        "05" => "May",
                        "06" => "June",
                        "07" => "July",
                        "08" => "August",
                        "09" => "September",
                        "10" => "October",
                        "11" => "November",
                        "12" => "December",
                    );
        $ret = '';
        $ret .= "<div class=\"container\"><br/>\n";
        if ($action != 'edit')
            $ret .= "   <p><font style=\"font-size: 1em;\"><strong>Warning:</strong> do not try to refresh the page after submitting the form!</font></p>\n";
        $ret .= "   <form action=\"profile.php\" name=\"form_build\" method=\"post\">\n";
        $ret .= "   <input type=\"hidden\" name=\"action\" value=\"" . $action . "\">\n";
        $ret .= "   <input type=\"hidden\" name=\"doit\" value=\"1\">\n";
        $ret .= "   <div id=\"tabs\">\n";
        $ret .= "       <ul class=\"nav nav-tabs\">\n";
        $ret .= "           <li class=\"active\"><a data-toggle=\"tab\" href=\"#tabs-1\">Personal information</a></li>\n";
        //$ret .= "             <li><a href=\"#tabs-2\">Interests</a></li>\n";
        $ret .= "           <li><a data-toggle=\"tab\" href=\"#tabs-3\">Friends</a></li>\n";
        $ret .= "           <li><a data-toggle=\"tab\" href=\"#tabs-5\">Keys</a></li>\n";
        $ret .= "       </ul>\n";

        $ret .= "<div class=\"tab-content\" style=\"padding-left: 2em;\">\n";
        $ret .= "   <div class=\"tab-pane active\" id=\"tabs-1\">\n";
        if ($action == 'new') {
            $ret .= "<p>Here you can provide personal information about yourself.<br/>A default certificate will also be created for you. (you must provide both username and full name)";
            $ret .= "<br/>Your WebID profile will be accessible at: <font color=\"#00BBFF\" style=\"font-size: 1.3em;\">" . $this->base_uri . "/people/</font>";
            $ret .= "<b>&lt;username&gt;</b><font color=\"#00BBFF\" style=\"font-size: 1.3em;\"> /card#me</font></p>\n";
        }
    /* ----- Username ------ */
	    $ret .= "<table id=\"tab1\" border=\"0\" valign=\"middle\">\n";
        // Display username only if we're creating a new profile
        if (($action == 'new') || ($action == 'import')) {
            $ret .= "<tr valign=\"middle\">\n";
            $ret .= "<td>Username: </td>\n";
            $ret .= "<td valign=\"top\"><input type=\"text\" size=\"50\" value=\"\" id=\"uri\" name=\"uri\" maxlength=\"32\" onBlur=\"validateReq('" . $this->base_uri . "/people/', 'uri', 'fullname', 'submit')\"></td>\n";
            $ret .= "<td><font color=\"" . $color . "\"> (accepted: a-z 0-9 _ . -)</font></td>\n";
            $ret .= "</tr>\n";
        }
    /* ----- Full name ------ */
        $ret .= "<tr><td>Full name: </td>\n";
        $ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $this->name . "\" id=\"fullname\" name=\"foaf:name\" onBlur=\"validateReq('" . $this->base_uri . "/people/', 'uri', 'fullname', 'submit')\"></td>\n";
        $ret .= "<td><font color=\"" . $color . "\"> (foaf:name)</font></td>\n";
        $ret .= "</tr>\n";
    /* ----- KEYGEN ------ */
        if (($action == 'new') || ($action == 'import')) {
            $ret .= "<tr>\n";
            $ret .= "<td hidden>KEYGEN Key Length</td>\n";
            $ret .= "<td><keygen id=\"pubkey\" name=\"pubkey\" challenge=\"randomchars\"  style=\"border-color: red;\" hidden></td>\n";
            $ret .= "<td hidden><font color=\"red\"> (certificate key strength)</font></td>\n";
            $ret .= "</tr>\n";
        }
    /* ----- Firstname ------ */
        $ret .= "<tr>\n";
        $ret .= "<td>Firstname: </td>\n";
        $ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $this->firstname . "\" name=\"foaf:givenName\"></td><td> (foaf:givenName)</td>\n";
        $ret .= "</tr>\n";
    /* ----- Lastname ------ */
        $ret .= "<tr>\n";
        $ret .= "<td>Lastname: </td>\n";
        $ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $this->familyname . "\" name=\"foaf:familyName\"></td>\n";
        $ret .= "<td> (foaf:familyName)</td>\n";
        $ret .= "</tr>\n";
    /* ----- Nickname ------ */
        $ret .= "<tr>\n";
        $ret .= "<td>Nickname: </td>\n";
        $ret .= "<td><input type=\"text\" size=\"50\" maxlength=\"64\" value=\"" . $this->nick . "\" name=\"foaf:nick\"></td>\n";
        $ret .= "<td> (foaf:nick)</td>\n";
        $ret .= "</tr>\n";

/* ----- PERSONAL ------ */

    /* ----- Picture ------ */
        $ret .= "<tr>\n";
        $ret .= "<td>Photo location: </td>\n";
        $ret .= "<td><input type=\"text\" placeholder=\"http://...\" size=\"50\" maxlength=\"64\" value=\"" . $this->pic . "\" name=\"foaf:img\"></td>\n";
        $ret .= "<td> (foaf:img)</td>\n";
        $ret .= "</tr>\n";

        // Add more personal info
        $ret .= $this->emails;
        $ret .= $this->sha1sums;
        $ret .= $this->homepages;
        $ret .= $this->blogs;
        $ret .= $this->workHPS;
        $ret .= $this->schoolHPS;
        $ret .= $this->curprojs;
        $ret .= $this->pastprojs;
        
        $ret .= "</table>\n";

        $ret .= "<p><select name=\"element_tab1\">\n";
        foreach($values_person as $key => $value)
            $ret .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
        $ret .= "</select>\n";
        $ret .= "<input type=\"button\" class=\"btn\" value=\"Add extra info\" onclick=\"addInfo(document.form_build.element_tab1.value, 'tab1')\"/></p>\n";
        $ret .= "</div>\n";
        
    /* ----- KNOWS ------ */  
        $ret .= "<div class=\"tab-pane\" id=\"tabs-3\">\n";
        $ret .= "<p>Here you can add links to your friends profiles. <font color=\"grey\"><small>[click the button to add more]</small></font><br/>\n";
        $ret .= "<small><font color=\"grey\">If you don't have any friends yet, try adding Andrei: <strong>http://fcns.eu/people/andrei/card#me</strong></font></small></p>\n";
        $ret .= "<table id=\"tab3\" border=\"0\">\n";
        $ret .= $this->knows;
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
        $ret .= $this->certs;
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
        $ret .= "<p><input class=\"btn btn-primary\" type=\"submit\" id=\"submit\" name=\"submit\" value=\"" . ucwords($action) . " profile\"";
        // Disable the submit button if we need to check if user already exists
        if (($action == 'new') || ($action == 'import')) {
            $ret .= " disabled>\n";
            $ret .= "<font color=\"grey\">[Note: a certificate will also be issued and installed in your browser]</font></p>\n";
        } else {
            $ret .= ">\n";    
        }
        $ret .= "</form>\n";
        $ret .= "</div>\n";
        
        return $ret;
    }
}
 
?>
