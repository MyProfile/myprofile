<?php

//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_Session.php
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
/**
 * Persist authentication information in the session storage
 *
 * @author Akbar Hossain
 */
class Authentication_Session {

    public  $webid            =  NULL;
    public  $isAuthenticated  = 0;
    public  $agent            = NULL;

    private $authnSession     = NULL;

    const IS_AUTHENTICATED = 'Authentication_isAuthenticated';
    const AGENT = 'Authentication_agent';
    const WEBID = 'Authentication_webid';

    /**
     * Created FOAF+SSL authenticated session
     * @param int $isAuthenticated
     * @param mixed $agent
     * @param string $webid
     */
    public function __construct($isAuthenticated = 0, $agent = NULL, $webid = NULL) {
        $this->authnSession = session_name();

        if (isset($this->authnSession)) {
            // session was started in the header file (where this file is included in)
            //if (session_start()) {
                $this->isAuthenticated = (isset($_SESSION[self::IS_AUTHENTICATED]))?$_SESSION[self::IS_AUTHENTICATED]:$isAuthenticated;
                $this->webid           = (isset($_SESSION[self::WEBID]))?$_SESSION[self::WEBID]:$webid;
                $this->agent           = (isset($_SESSION[self::AGENT]))?$_SESSION[self::AGENT]:$agent;
            //}
        }
    }

    /**
     * Set an authenticated webid
     * @param mixed $webid
     * @param mixed $agent
     */
    public function setAuthenticatedWebid($webid, $agent = NULL) {
        if (!is_null($webid)) {
            $_SESSION[self::IS_AUTHENTICATED] = 1;
            $_SESSION[self::WEBID]            = $webid;
            $_SESSION[self::AGENT]            = $agent;

            $this->isAuthenticated = 1;
            $this->webid           = $webid;
            $this->agent           = $agent;
        }
    }
    /**
     * Unset authenticated webid for current session
     */
    public function unsetAuthenticatedWebid() {
        $_SESSION[self::IS_AUTHENTICATED] = 0;
        $_SESSION[self::AGENT]            = NULL;
        $_SESSION[self::WEBID]            = NULL;

        $this->isAuthenticated = 0;
        $this->webid           = NULL;
        $this->agent           = NULL;
    }
}

?>
