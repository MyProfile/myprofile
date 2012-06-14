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

    private $sparql;
    private $endpoint;
    private $ttl;
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
    private $email;

    // Build the selectors for adding more form content (default ttl is 24h)
    function __construct($webid, $base_uri, $endpoint, $ttl = 86400) {
        $this->webid = $webid;
        
        if (isset($base_uri))
            $this->base_uri = $base_uri;
        // set cache dir
        $this->cache_dir = 'cache/';
        
        // set the SPARQL endpoint address
        $this->endpoint = $endpoint; 
        
        // set cache time to live (default is 24h)
        $this->ttl = $ttl;
    }
    
    // Cache user data into a SPARQL triplestore
    function sparql_cache() {
        // Insert only real WebIDs (i.e. skip bnodes)
        if ((strpos($this->webid, 'node') !== false) || (strpos($this->webid, '_:') !== false)) {
            $db = sparql_connect($this->endpoint);
            // first delete previous data for the graph
            $sql = "CLEAR GRAPH <" . $this->webid . ">";
            $res = sparql_query($sql);
                
            // Load URI into the triple store
            $sql = "LOAD <" . $this->webid . ">";
            $res = sparql_query($sql);
            
            // Add the timestamp for the date at which it was inserted
            $time = time();
            $date = date("Y", $time) . '-' . date("m", $time) . '-' . date("d", $time) . 'T' . date("H", $time) . ':' . date("i", $time) . ':' . date("s", $time);
            $sql = 'INSERT DATA INTO GRAPH IDENTIFIED BY <' . $this->webid . '> {<' . $this->webid . '> dc:date "' . $date . '"^^xsd:dateTime . }';
            $res = sparql_query($sql);
        
            return true;
        } else {
            return false;
        }
    }   
    
    // Load profile data using SPARQL
    // returns true
    function sparql_graph() {
        // cache data is refreshed if it's older than the given TTL
        $time = time() - $this->ttl;
        $date = date("Y", $time) . '-' . date("m", $time) . '-' . date("d", $time) . 'T' . date("H", $time) . ':' . date("i", $time) . ':' . date("s", $time);
        
        $db = sparql_connect($this->endpoint);
        $query = 'SELECT * FROM <' . $this->webid . '> WHERE {?person dc:date ?date . FILTER (?date > "' . $date . '"^^xsd:dateTime)}';
        $result = sparql_query($query);

        // fallback to Graphite if there's a problem with the SPARQL endpoint
        if (!$result) {
            $this->direct_graph();
        } else {
            // cache data into the triple store if it's the first time we see it
            $count = sparql_num_rows($result);

            // force refresh of data if cache expired
            if ($count == 0)
                $this->sparql_cache();

            $query = "CONSTRUCT { ?s ?p ?o } WHERE { GRAPH <" . $this->webid . "> { ?s ?p ?o } . }";
            $graph = new Graphite();
            $graph->loadSPARQL($this->endpoint, $query);
            
            $this->graph = $graph;
        }
        return true;
    }
    
    // Load profile data using Graphite
    // returns true
    function direct_graph() {
        // Load the RDF graph data
        $graph = new Graphite();
        $graph->load($this->webid);
        $graph->cacheDir($this->cache_dir);

        $this->graph = $graph;

        return true;
    }
    
    // Load the user's data (either through SPARQL or directly)
    function load($refresh = false) {
        // check if we have a SPARQL endpoint configured
        if (strlen($this->endpoint) >0) {
            // force a cache refresh
            if ($refresh == true)
                $this->sparql_cache();
            // use the SPARQL endpoint
            $this->sparql_graph();
        } else {
            // use the direct method (Graphite)
            $this->direct_graph();
        }
        
        // try to get primary topic, else go with default uri (some people don't use #)
        $pt = $this->graph->resource('foaf:PersonalProfileDocument');
        $this->primarytopic = $pt->get('foaf:primaryTopic');
        if ($this->primarytopic != '[NULL]') 
            $profile = $this->graph->resource($this->primarytopic);
        else
            $profile = $this->graph->resource($this->webid);

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
        
        // get the user's first email address
        if ($profile->get('foaf:mbox') != '[NULL]')
            $this->email = $profile->get('foaf:mbox');
            
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
    
    // get the user's nickname
    function get_nick() {
        return $this->profile->get("foaf:nick");
    }
    
    // get the user's picture
    function get_picture() {
        return $this->picture;
    }
    
    // get the user's email address
    function get_email() {
        return $this->email;
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
        $pf = fopen($path . '/foaf.rdf', 'w') or error('Cannot open profile RDF file!');
        fwrite($pf, $data);
        fclose($pf);    
        
        $pf = fopen($path . '/foaf.txt', 'w') or error('Cannot open profile PHP file!');
        fwrite($pf, $data);
        fclose($pf);

        // everything is fine
        return success("You have just added " . $uri . " to your friends.");
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
        return success("You have just removed " . $uri . " from your friends.");
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
            return error('Unable to connect to the database!');
        } else {
            if ($result !== true) {
                mysql_free_result($result);
            }
            return success('You have successfully subscribed to local services.');
        }
    }
    
    // unsubscribe from local services
    // returns a visual confirmation in html
    function unsubscribe() {
        $query = "DELETE FROM pingback WHERE webid='" . mysql_real_escape_string($this->webid) . "'";
        $result = mysql_query($query);
        if (!$result) {
            return error('Unable to connect to the database!');
        } else { 
            // delete any pingbacks addressed to me
            $query = "DELETE FROM pingback_messages WHERE to_uri='" . mysql_real_escape_string($this->webid) . "'";
            $result = mysql_query($query);
            if (!$result) {
                return error('Unable to connect to the database!');
            } else {
                mysql_free_result($result);
                return success('Your WebID has been successfully unregistered.');
            }
        }
    }
    
    // subscribe to receive email notifications
    // returns a visual confirmation in html
    function subscribe_email() {
        // subscribe only if we are not subscribed already
        if (is_subscribed_email($this->webid) == false) {
            $query = "UPDATE pingback SET email='1' WHERE webid='" . mysql_real_escape_string($this->webid) . "'";
            $result = mysql_query($query);
            if (!$result) {
                return error('Unable to connect to the database!');
            } else {
                return success('You have successfully subscribed to receiving email notifications.');
            }
        }
    }
    
    // unsubscribe from receiving email notifications
    // returns a visual confirmation in html
    function unsubscribe_email() {
        // unsubscribe only if we are already subscribed
        if (is_subscribed_email($this->webid) == true) {
            $query = "UPDATE pingback SET email='0' WHERE webid='" . mysql_real_escape_string($this->webid) . "'";
            $result = mysql_query($query);
            if (!$result) {
                return error('Unable to connect to the database!');
            } else {
                mysql_free_result($result);
                return success('You have unsubscribed from receiving email notifications.');
            }
        }
    }
}
 
?>
