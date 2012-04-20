<?php

//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_FoafSSLARC.php
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
require_once(dirname(__FILE__)."/Authentication_RDFCache.php");
require_once(dirname(__FILE__)."/Authentication_Helper.php");
require_once(dirname(__FILE__)."/Authentication_FoafSSLAbstract.php");
/**
 * @author Akbar Hossain
 * Implements Foaf+SSL authentication as described by http://esw.w3.org/Foaf%2Bssl
 *
 * The facilities of the ARC library are used.
 */
class Authentication_FoafSSLARC extends Authentication_FoafSSLAbstract {
    private $ARCConfig = NULL;
    // TODO this instance is shared
    public  $ARCStore  = NULL;

    /**
     * Authenticate using Foaf+SSL procedure
     *
     * @param array $ARCConfig
     * @param mixed $ARCStore
     * @param Boolean $createSession
     * @param String $SSLClientCert Client certificate in PEM format
     */
    public function __construct($ARCConfig, $ARCStore = NULL, $createSession= TRUE, $SSLClientCert = NULL) {

        $this->ARCConfig = $ARCConfig;
        $this->ARCStore = $ARCStore;

        parent::__construct($createSession, $SSLClientCert);
    }

    private function createStore() {

        if ( (!isset($this->ARCStore)) && (Authentication_Helper::isValidURL($this->webid)) ) {

            $store = ARC2::getStore($this->ARCConfig);

            if (!$store->isSetUp()) {
                $store->setUp();
            }

            $store->reset();

            /* LOAD will call the Web reader, which will call the
               format detector, which in turn triggers the inclusion of an
               appropriate parser, etc. until the triples end up in the store. */
            $store->query('LOAD <'.$this->webid.'>');

            $this->ARCStore = $store;
        }
    }

    /* Returns an array of the modulus and exponent in the supplied RDF */
    protected function getFoafRSAKey() {

        $modulus   = NULL;
        $exponent  = NULL;
        $res       = NULL;
        $primaryId = $this->webid;

        $q = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>

              SELECT ?x ?primaryTopic
              WHERE {
                      ?x foaf:primaryTopic ?primaryTopic .
	            }';

        if ($rows = $this->ARCStore->query($q, 'rows')) {
            foreach ($rows as $row) {
//                    print "primaryTopic " . $row['primaryTopic'] . "<br/>";
                $primaryId = $row['primaryTopic'];
            }
        }
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

                if ($row['person']==$primaryId) {

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

        return ( $res );
    }
    
    protected function getAgentRSAKey() {

        if ($this->webid) {

            $this->createStore();

            $store = $this->ARCStore;

            if (isset($store) && ($errs = $store->getErrors())) {
                return NULL;
            }

            if (isset($store) && ($agentRSAKey = $this->getFoafRSAKey()))
                return($agentRSAKey);

            return NULL;

        }

    }

    function getBindings( $sparql_results ) {
        return $sparql_results->results->bindings;
    }

    /**
     * url safety for differing domains, wrap all urls in u() to make the correct uri
     * for current site
     *
     * @param $uri
     * @return unknown_type
     */
    function u( $uri ) {
        return str_replace( RDF_BASE , 'http://' . $_SERVER['HTTP_HOST'] . '/' , $uri );
    }

    /**
     * this one trims down a uri to be the domain only (w/ prefix and trailing slash
     *
     * @param $uri
     * @return unknown_type
     */
    function t( $uri ) {
        return implode('/' , array_slice( explode( '/' , $uri , 4 ) , 0 , 3) ) . '/';
    }

    /**
     * this one trims down a uri to be the domain only
     *
     * @param $uri
     * @return unknown_type
     */
    function d( $uri ) {
        return implode('/' , array_slice( explode( '/' , $uri , 4 ) , 2 , 1) );
    }

    /**
     * proxy method to get the value of an object / node
     *
     * @param $obj
     * @return unknown_type
     */
    function v( $obj ) {
        return Tripler::getValue( $obj );
    }

    function dr( $obj ) {
        return date( DATE_RFC822 , v($obj) );
    }

    function dt( $obj ) {
        return date( 'Y-m-d H:i:s' , v($obj) );
    }

    function pl( $page ) {
        $qs = $_SERVER['QUERY_STRING'];
        if( strlen($qs) ) {
            if( ( $colon = strpos($qs , ';') ) > 0 ) {
                $qs = substr( $qs , 0 , $colon );
            }
        }
        return sprintf( '?%s;%s' , $qs , $page );
    }

    function uriSafe( $string ) {
        return urlencode( preg_replace('/__+/', '_' , preg_replace( array( '/\h\h+/' , '/\v\v+/' , '/ /' ), '_' , $string ) ) );
    }

    function cleanWords( $string ) {
        return trim( preg_replace( array( '/\W/' , '/\h\h+/' ) , ' ' , $string ) );
    }


    function getIdentityFromNode( $node , $index ) {
        $exponent = 0;
        $modulus = '';
        if( $node['http://www.w3.org/ns/auth/rsa#modulus'][0]['datatype'] == 'http://www.w3.org/ns/auth/cert#hex' ) {
            $modulus = $node['http://www.w3.org/ns/auth/rsa#modulus'][0]['value'];
        } elseif( isset($index[ $node['http://www.w3.org/ns/auth/rsa#modulus'][0]['value'] ]) ) {
            $modulus = $index[ $node['http://www.w3.org/ns/auth/rsa#modulus'][0]['value'] ]['http://www.w3.org/ns/auth/cert#hex'][0]['value'];
        }
        $modulus =  strtoupper(preg_replace( '/[^0-9a-f]/im' , '' , $modulus ));
        $modulus = str_split( $modulus , 2 );
        while( $modulus[0] == '00' ) {
            array_shift($modulus);
        }
        $modulus = implode( '' , $modulus );
        if( $node['http://www.w3.org/ns/auth/rsa#public_exponent'][0]['datatype'] == 'http://www.w3.org/ns/auth/cert#int' ) {
            $exponent = $node['http://www.w3.org/ns/auth/rsa#public_exponent'][0]['value'];
        } else {
            $temp = $index[ $node['http://www.w3.org/ns/auth/rsa#public_exponent'][0]['value'] ];
            if( isset($temp['http://www.w3.org/ns/auth/cert#int']) ) {
                $exponent = $temp['http://www.w3.org/ns/auth/cert#int'][0]['value'];
            } else {
                $exponent = $temp['http://www.w3.org/ns/auth/cert#decimal'][0]['value'];
            }
        }
        return (object)array(
                        'webid' => $node['http://www.w3.org/ns/auth/cert#identity'][0]['value'],
                        'modulus' => $modulus,
                        'exponent' => $exponent
        );
    }

    function hasTypeFromIndexNode( $node , $type ) {
        if( isset($node['http://www.w3.org/1999/02/22-rdf-syntax-ns#type']) ) {
            foreach( $node['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'] as $offset => $valueSet ) {
                if( v((object)$valueSet) == $type ) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    function getIdentitiesFromFOAF( $foaf ) {
        $foafIdentities = array();
        foreach( $foaf->index as $ref => $node ) {
            if( hasTypeFromIndexNode($node , 'http://www.w3.org/ns/auth/rsa#RSAPublicKey' ) ) {
                $foafIdentities[] = getIdentityFromNode( $node , $foaf->index );
            }
        }
        return $foafIdentities;
    }

    function getIdentityFromCert( $cert ) {
        if( strlen($cert) < 50 ) {
            throw new Exception( 'No SSL Certificate' );
        }
        $x509certDetails = openssl_x509_parse( $cert );
        preg_match( '/URI\:([^,$]+)/iu' , $x509certDetails['extensions']['subjectAltName'] , $webID );
        $webID = array_pop($webID);
        $key = openssl_pkey_get_details( openssl_pkey_get_public( $cert ) );
        $return = `echo "{$key['key']}" | openssl rsa -pubin -inform PEM -text`;
        preg_match( '/Exponent\: ([0-9]+)/im', $return , $exponent );
        preg_match_all( '/([0-9a-f]{2})(\:|\n)/m', $return , $modulus );
        $modulus = $modulus[1];
        if( !(count($modulus) % 2) ) {
            array_pop($modulus);
        }
        while( $modulus[0] == '00' ) {
            array_shift($modulus);
        }
        return (object)array(
                        'webid' => $webID,
                        'modulus' => strtoupper( implode( '' , $modulus ) ),
                        'exponent' => $exponent[1]
        );
    }

    function authenticate( $identity ) {
        $foaf = RDFCache::getRDF( $identity->webid );
        $foafIdentities = getIdentitiesFromFOAF( $foaf );
        foreach( $foafIdentities as $offset => $foafIdentity ) {
            if( $foafIdentity == $identity ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    function login() {
        try {
            $identity = getIdentityFromCert( $_SERVER['REMOTE_USER'] );
            if( authenticate($identity) ) {
                return $identity;
            }
        } catch ( Exception $e ) {
            echo $e;
            throw $e;
        }
    }



}

?>
