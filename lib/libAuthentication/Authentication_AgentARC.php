<?php

//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_AgentARC.php
// Date       : 14th Feb 2010
//
// See Also   : https://foaf.me/testLibAuthentication.php
//
// Copyright 2008-2010 foaf.me
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
//
// "Everything should be made as simple as possible, but no simpler."
// -- Albert Einstein
//
//-----------------------------------------------------------------------------------------------------------------------------------

require_once(dirname(__FILE__)."/../arc/ARC2.php");
require_once(dirname(__FILE__)."/Authentication_AgentAbstract.php");
/**
 * @author Akbar Hossain
 * Foaf parser leveraging ARC functionality
 * It takes URI of an user/agent and looks up properties (e.g. public key)
 * of the corresponding Foaf profile.
 *
 * This class relies on the ARC RDF triple store.
 */
class Authentication_AgentARC extends Authentication_AgentAbstract {

    private $ARCConfig = NULL;
    private $ARCStore  = NULL;

    public function __construct($ARCConfig, $agentURI = NULL, $ARCStore = NULL) {

        $this->ARCConfig = $ARCConfig;
        $this->ARCStore  = $ARCStore;

        parent::__construct($agentURI);
    }

    protected function loadAgent() {

        $this->createStore();

    }

    protected function loadErrors() {

        if (isset($this->ARCStore) && ($errs = $this->ARCStore->getErrors())) {
            $this->errors = $errs;
        }
    }

    protected function getAgentProperties() {

        $agent    = NULL;

        if ($this->agentURI && $this->agentId && $this->ARCStore) {

            // Get pseudonyms
            if ( $nyms = $this->getAgentNyms() ) {
                $agent = $nyms;
            }

            // Get the key
            if ($agentRSAKey = $this->getFoafRSAKey()) {
                $agent = Authentication_Helper::safeArrayMerge($agent, array('RSAKey'=>$agentRSAKey));
            }

            // Get openID
            if ($openID = $this->getOpenID()) {
                $agent = Authentication_Helper::safeArrayMerge($agent, $openID);
            }

            // Get name parts
            if ($names = $this->getNameParts()) {
                $agent = Authentication_Helper::safeArrayMerge($agent, $names);
            }

            // Get Friends
            if ($friends = $this->getAllFriends()) {
                $agent = Authentication_Helper::safeArrayMerge($agent, array('knows'=>$friends));
            }

            // Set up Agent
            $agent = Authentication_Helper::safeArrayMerge(array("webid"=>$this->agentId), $agent);
        }

        return($agent);
    }

    protected function getAgentId() {

        $agentID = ($agentId = $this->getPrimaryProfile())?$agentId:$this->agentURI;

        return($agentID);
    }

    private function createStore() {

        if (!isset($this->ARCStore)) {

            $store = ARC2::getStore($this->ARCConfig);

            if (!$store->isSetUp()) {
                $store->setUp();
            }

            $this->ARCStore = $store;
        }

        $this->ARCStore->reset();

        /* LOAD will call the Web reader, which will call the
	   format detector, which in turn triggers the inclusion of an
	   appropriate parser, etc. until the triples end up in the store. */
        $this->ARCStore->query('LOAD <'.$this->agentURI.'>');

    }

    /* Returns an array of the modulus and exponent in the supplied RDF */
    protected function getFoafRSAKey() {

        $modulus = NULL;
        $exponent = NULL;
        $res = NULL;

        /* list names */
        $q = " PREFIX cert: <http://www.w3.org/ns/auth/cert#>
              PREFIX rsa: <http://www.w3.org/ns/auth/rsa#>

              SELECT ?m ?e ?mod ?exp ?person
              WHERE {
                       [] cert:identity ?person ;
                       rsa:modulus ?m ;
                       rsa:public_exponent ?e .
                       OPTIONAL { ?m cert:hex ?mod . }
                       OPTIONAL { ?e cert:decimal ?exp . }
                    } ";

        if ($rows = $this->ARCStore->query($q, 'rows')) {

            foreach ($rows as $row) {
                if ($row['person']==$this->agentId) {

                    if (isset($row['mod']))
                        $modulus =  $row['mod'];
                    elseif (isset($row['m']))
                        $modulus =  $row['m'];

                    if (isset($row['exp']))
                        $exponent = $row['exp'];
                    elseif (isset($row['e']))
                        $exponent = $row['e'];

                    $modulus =  Authentication_Helper::cleanHex($modulus);
                    $exponent = Authentication_Helper::cleanHex($exponent);

                    $res[] = array( 'modulus'=>$modulus, 'exponent'=>$exponent );

                }
            }
        }

        if (isset($res))
            $res = Authentication_Helper::arrayUnique($res);
        /*
        print "getFoafRSAKey<pre>";
        print_r($res);
        print "<pre/>";
        */
        return($res);
    }


    protected function getNameParts() {

        $res =  NULL;

        if ($this->ARCStore && $this->agentId) {

            /* list names */

            $q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	          PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

                  SELECT ?webid ?firstname ?lastname
                  WHERE {
                    ?webid foaf:givenName ?firstname .
                    ?webid foaf:familyName ?lastname .
                  }";


            if ($rows = $this->ARCStore->query($q, 'rows')) {

                foreach ($rows as $row) {
                    if (strcmp($row['webid'],$this->agentId)==0) {

                        if (isset($row['firstname']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('firstname'=>$row['firstname']));

                        if (isset($row['lastname']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('lastname'=>$row['lastname']));

                    }
                }
            }

            $q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	          PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

                  SELECT ?webid ?firstname ?lastname
                  WHERE {
                          ?webid foaf:givenname ?firstname .
                          ?webid foaf:family_name ?lastname .
                  }";

            if ($rows = $this->ARCStore->query($q, 'rows')) {


                foreach ($rows as $row) {
                    if (strcmp($row['webid'],$this->agentId)==0) {
                        if (isset($row['firstname']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('firstname'=>$row['firstname']));

                        if (isset($row['lastname']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('lastname'=>$row['lastname']));

                    }
                }
            }
        }
        /*
        print "NameParts <pre>";
        print_r($res);
        print "<pre/>";
        */
        return $res;
    }

    protected function webid($seeAlso, $about, $homepage, $mbox) {
        if ($seeAlso)
            return $seeAlso;

        if ($about)
            return $about;

        if ($homepage)
            return $homepage;

        return $mbox;
    }

    protected function getAllFriends() {

        $results = NULL;

        if ($this->ARCStore && $this->agentId) {

            /* list names */
            $q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                 PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

		 SELECT ?name ?seeAlso ?y ?mbox ?homepage ?x ?accountName
                 WHERE {
		         ?x foaf:knows ?y .
		         OPTIONAL { ?y foaf:name ?name } .
		         OPTIONAL { ?y rdfs:seeAlso ?seeAlso } .
		         OPTIONAL { ?y foaf:mbox ?mbox } .
			 OPTIONAL { ?y foaf:homepage ?homepage } .
		         OPTIONAL { ?y foaf:accountName ?accountName } .
	        }";

            if ($rows = $this->ARCStore->query($q, 'rows')) {
                $res=NULL;
                foreach ($rows as $row) {
                    if ( (strcmp($row['x'],$this->agentId)==0) && (strcmp($row['y'],$this->agentId)!=0) ) {

                        if (isset($row['y']) && (strcmp($row['y type'],'uri')==0) )
                            $y = $row['y'];
                        else
                            $y = NULL;

                        if (isset($row['seeAlso']) && (strcmp($row['seeAlso type'],'uri')==0) )
                            $seeAlso = $row['seeAlso'];
                        else
                            $seeAlso = NULL;

                        $webid = $this->webid($seeAlso, $y, $row['homepage'], $row['mbox']);

                        if ($webid != $prevWebid) {

                            if (isset($row['name']))
                                $res = array('name'=>$row['name']);

                            if (isset($row['seeAlso']) && (strcmp($row['seeAlso type'],'uri')==0) )
                                $res = Authentication_Helper::safeArrayMerge($res, array('seeAlso'=>$seeAlso));

                            if (isset($row['mbox']))
                                $res = Authentication_Helper::safeArrayMerge($res, array('mbox'=>array($row['mbox'])));

                            if (isset($row['homepage']))
                                $res = Authentication_Helper::safeArrayMerge($res, array('homepage'=>$row['homepage']));

                            if (isset($y))
                                $res = Authentication_Helper::safeArrayMerge($res, array('about'=>$y));

                            $res = Authentication_Helper::safeArrayMerge($res, array('webid'=>$webid));

                            if (isset($res)) {
                                $results[] = $res;
                                $res = NULL;
                            }

                            $prevWebid = $webid;
                        }
                        else {
                            if (isset($row['mbox']))
                                $res = Authentication_Helper::safeArrayMerge($res, array('mbox'=>Authentication_Helper::safeArrayMerge($res['mbox'], array($row['mbox']))));
                        }
                    }
                }

                if (isset($results)) {
                    foreach ($results as $key => $row) {
                        $name[$key]  = isset($row['name'])?$row['name']:str_replace('mailto:', '', $row['webid']);
                        $seeAlso[$key] = $row['seeAlso'];
                        $mbox[$key] = $row['mbox'];
                        $homepage[$key] = $row['homepage'];
                        $about[$key] = $row['about'];
                        $webid[$key] = $row['webid'];
                    }

                    if (is_array($name)) {
                        $name_lowercase = array_map('strtolower', $name);

                        array_multisort($name_lowercase, SORT_ASC, SORT_STRING, $results);
                    }
                }
            }
        }
        /*
        print "getAllFriends<pre>";
        print_r($results);
        print "<pre/>";
        */
        return $results;

    }

    protected function getAgentNyms() {
        $res =  NULL;

        if ($this->ARCStore && $this->agentId) {
            /* list names */
            $q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                  PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

		  SELECT ?x ?name ?seeAlso ?mbox ?homepage ?nick ?img ?weblog ?depiction ?y ?mbox_sha1sum ?accountProfilePage
                  WHERE {
                          ?x foaf:nick ?nick ;
                          OPTIONAL { ?x foaf:name ?name }.
		          OPTIONAL { ?x rdfs:seeAlso ?seeAlso } .
		          OPTIONAL { ?x foaf:mbox ?mbox } .
		          OPTIONAL { ?x foaf:mbox_sha1sum ?mbox_sha1sum } .
			  OPTIONAL { ?x foaf:homepage ?homepage } .
		          OPTIONAL { ?x foaf:img ?img } .
		          OPTIONAL { ?x foaf:depiction ?depiction } .
		          OPTIONAL { ?x foaf:holdsAccount ?y } .
                          OPTIONAL { ?x foaf:weblog ?weblog } .
			  OPTIONAL { ?y foaf:accountProfilePage ?accountProfilePage } .
		        }";

            if ($rows = $this->ARCStore->query($q, 'rows')) {
                print_r($con);

                foreach ($rows as $row) {
                    if ( (strcmp($row['x'],$this->agentId)==0) ) {

                        if (isset($row['name']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('name'=>$row['name']));

                        if ( isset($row['seeAlso'])  && (strcmp($row['seeAlso type'],'uri')==0) )
                            $res = Authentication_Helper::safeArrayMerge($res, array('seeAlso'=>array_unique(Authentication_Helper::safeArrayMerge($res['seeAlso'], array($row['seeAlso'])))));

                        if (isset($row['mbox']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('mbox'=>array_unique(Authentication_Helper::safeArrayMerge($res['mbox'], array($row['mbox'])))));

                        if (isset($row['mbox_sha1sum']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('mbox_sha1sum'=>array_unique(Authentication_Helper::safeArrayMerge($res['mbox_sha1sum'], array($row['mbox_sha1sum'])))));

                        if (isset($row['homepage']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('homepage'=>array_unique(Authentication_Helper::safeArrayMerge($res['homepage'], array($row['homepage'])))));

                        if (isset($row['nick']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('nick'=>array_unique(Authentication_Helper::safeArrayMerge($res['nick'], array($row['nick'])))));

                        if (isset($row['accountProfilePage']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('accountProfilePage'=>array_unique(Authentication_Helper::safeArrayMerge($res['accountProfilePage'], array($row['accountProfilePage'])))));

                        if ( isset($row['y']) && (strcmp($row['y type'],'uri')==0) )
                            $res = Authentication_Helper::safeArrayMerge($res, array('holdsAccount'=>array_unique(Authentication_Helper::safeArrayMerge($res['holdsAccount'], array($row['y'])))));

//                          if (isset($row['holdsAccountHomepage']))
//                              $res = Authentication_Helper::safeArrayMerge($res, array('holdsAccount'=>array_unique(Authentication_Helper::safeArrayMerge($res['holdsAccount'], array($row['holdsAccountHomepage'])))));


                        if (isset($row['weblog']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('weblog'=>$row['weblog']));

                        if (isset($row['img']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('img'=>$row['img']));

                        if (isset($row['depiction']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('depiction'=>$row['depiction']));
                    }
                }
            }
        }
        /*
        print "getNyms2<pre>";
        print_r($res);
        print "<pre/>";
        */
        return $res;
    }

    protected function getOpenID() {

        $res =  NULL;

        if ($this->ARCStore && $this->agentId) {

            /* list names */
            $q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	          PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

                  SELECT ?webid ?openid
                  WHERE {
                          ?webid foaf:openid ?openid .
                  } ";

            if ($rows = $this->ARCStore->query($q, 'rows')) {

                foreach ($rows as $row) {
                    if (strcmp($row['webid'],$this->agentId)==0) {

                        if (isset($row['openid']))
                            $res = Authentication_Helper::safeArrayMerge($res, array('openid'=>array_unique(Authentication_Helper::safeArrayMerge($res['openid'], array($row['openid'])))));

                    }
                }
            }
        }
        /*
        print "getOpenID<pre>";
        print_r($res);
        print "<pre/>";
        */
        return $res;
    }

    protected function getPrimaryProfile() {
        if ($this->ARCStore) {

            /*
            $q = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>

	          SELECT ?x ?primaryTopic
                  WHERE {
                          ?x a foaf:PersonalProfileDocument .
                          OPTIONAL { ?x foaf:primaryTopic ?primaryTopic }.
	          }';
	
            if ($rows = $this->ARCStore->query($q, 'rows')) {
		foreach ($rows as $row) {
                    print "primaryTopic " . $row['primaryTopic'] . "<br/>";
                    return $row['primaryTopic'];
		}
            }
            */

            // Remove foaf:PersonaProfileDoucment constraint to get http://sw-app.org/mic.xhtml#i
            $q = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>

	          SELECT ?x ?primaryTopic
                  WHERE {
                          ?x foaf:primaryTopic ?primaryTopic .
	          }';

            if ($rows = $this->ARCStore->query($q, 'rows')) {
                foreach ($rows as $row) {
//                    print "primaryTopic " . $row['primaryTopic'] . "<br/>";
                    return $row['primaryTopic'];
                }
            }
        }
    }

}
?>
