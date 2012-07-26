<?php

/*-------------------------------------------------------------------------------------
 *
 * Filename   : Authentication.php
 * Date       : 11th July 2012
 *
 * Copyright (C) 2012 Melvin Carvalho, Akbar Hossain, László Török
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is furnished 
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Everything should be made as simple as possible, but no simpler."
 * -- Albert Einstein
 */
//-------------------------------------------------------------------------------------

require_once(dirname(__FILE__)."/Authentication_Delegated.php");

/**
 * Top-level authentication class that integrates multiple authentication
 * procedures. (session or delegated WebID)
 *
 * @modified Andrei Sambra
 */
class Authentication {
    /**
     * After succesful authentication contains the webid
     * @var string
     */
    public  $webid             = NULL;
    public  $isAuthenticated   = 0;
    public  $authnDiagnostic   = NULL;
    private $session = NULL;

    const STATUS_AUTH_VIA_SESSION = "Authenticated via a session";

    public function __construct($ARCConfig, $sig = NULL)
    {
        // Authenticate via session and return
        $this->session = new Authentication_Session();
        if ($this->session->isAuthenticated) {
            $this->webid           = $this->session->webid;
            $this->isAuthenticated = $this->session->isAuthenticated;
            $this->authnDiagnostic = self::STATUS_AUTH_VIA_SESSION;
            return;
        }

        // Authenticate via delegated login
        $sig = isset($sig)?$sig:$_GET["sig"];
        if (isset($sig))
        {
            $authDelegate = new Authentication_Delegated(FALSE);

            $this->webid           = $authDelegate->webid;
            $this->isAuthenticated = $authDelegate->isAuthenticated;
            $this->authnDiagnostic = $authDelegate->authnDiagnostic;
        }

        if ($this->isAuthenticated)
        {
            $this->session->setAuthenticatedWebid($this->webid);
        }
        else
        {
            $this->session->unsetAuthenticatedWebid();
            $this->webid = NULL;
        }
    }

    /**
     * Is the current user authenticated?
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Leave the authenticated session
     */
    public function logout()
    {
        $this->isAuthenticated = 0;
        $this->session->unsetAuthenticatedWebid();
    }
}

?>
