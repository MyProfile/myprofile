<?php

/*-------------------------------------------------------------------------------------
 *
 * Filename   : Authentication_Delegated.php
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
require_once(dirname(__FILE__)."/Authentication_URL.php");
require_once(dirname(__FILE__)."/Authentication_X509CertRepo.php");
require_once(dirname(__FILE__)."/Authentication_Session.php");
/**
 * Implements WebID Delegated Authentication using an Identity Provider
 *
 * @author Akbar Hossain
 * @modified Andrei Sambra
 */
class Authentication_Delegated {
    /**
     * After succesful authentication contains the webid
     * (e.g. http://foaf.me/tl73#me)
     * @var string
     */
    public  $webid             = NULL;
    public  $isAuthenticated   = 0;
    /**
     * Always contains the diagnostic message for the last authentication attempt
     * @var string
     */
    public  $authnDiagnostic   = NULL;
    /** @var Authentication_SignedURL */
    private $requestURI        = NULL;
    /** @var Authentication_URL */
    private $referer           = NULL;
    private $ts                = NULL;
    private $allowedTimeWindow = 0;
    private $elapsedTime       = 0;

    const STATUS_AUTH_VIA_SESSION =
    "Authenticated via a session";
    
    const STATUS_DELEGATED_LOGIN_OK =
    "Delegated WebID Login response has been authenticated";
    
    const STATUS_SIGNATURE_VERIFICATION_ERR =
    "Signature on response could not be verified";
    
    const STATUS_UNSUPPORTED_SIGNATURE_ALG_ERR =
    "Unsupported signature algorithm";
    
    const STATUS_IDP_RESPONSE_TIMEOUT_ERR =
    "Response from delegate IdP was outside of the allowed time window";

    const STATUS_OPENSSL_VERIFICATION_ERR =
    "Openssl verification error";

    const STATUS_IDP_CERTIFICATE_MISSING =
    "Signing IdP's certificate not found";

    const SIG_ALG_RSA_SHA1 = 'rsa-sha1';
    /**
     * Perform delegated WebID authentication relying on an Identity Provider
     * @param Authentication_SignedURL $request (if not specified infered from _GET)
     * @param Authentication_X509CertRepo $certRepository (if not default is used)
     * @param bool $createSession
     * @param string $sigAlg
     * @param int $allowedTimeWindow
     */
    public function __construct($createSession = TRUE,
                                Authentication_SignedURL $request = NULL,
                                Authentication_URL $referer = NULL,
                                Authentication_X509CertRepo $certRepository = NULL,
                                $sigAlg = self::SIG_ALG_RSA_SHA1,
                                $allowedTimeWindow = 300)
    {
        if ($createSession)
        {
            $session = new Authentication_Session();
            if ($session->isAuthenticated)
            {
                $this->webid = $session->webid;
                $this->isAuthenticated = $session->isAuthenticated;
                $this->authnDiagnostic = self::STATUS_AUTH_VIA_SESSION;
                return;
            }
        }

        if ( ! $certRepository)
            $certRepository = new Authentication_X509CertRepo();

        if ( ! $request) {
            $request = Authentication_SignedURL::parse(
                    ((isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) ? "https" : "http")
                    . "://".$_SERVER["SERVER_NAME"]
                    . ($_SERVER["SERVER_PORT"] != ((isset($_SERVER["HTTPS"])
                         && ($_SERVER["HTTPS"] == "on")) ? 443 : 80) ? ":"
                            .$_SERVER["SERVER_PORT"] : "")
                    . $_SERVER["REQUEST_URI"]
                    );
        }

        $error = null;
        $sig = null;
        $ts = null;

        isset($_GET["error"]) and $error = $_GET["error"];
        
        isset($_GET["sig"]) and $sig = $_GET["sig"];
        
        isset($_GET["ts"]) and $ts = $_GET["ts"];

        $error = $request->getQueryParameter('error', $error);
        $sig = $request->getQueryParameter('sig', $sig);
        $ts = $request->getQueryParameter('ts', $ts);

        $this->requestURI = $request;
        if (NULL != $referer)
        {
            $this->referer = $referer;
        }
        else if (isset($_GET["referer"]))
        {
            $this->referer = Authentication_URL::parse($_GET["referer"]);
        }
        else
        {
            $this->referer = new Authentication_URL();
        }
        $this->ts = $ts;

        $webid = null;
        isset($_GET["webid"]) and $webid = $_GET["webid"];
        
        $this->webid             = $request->getQueryParameter('webid', $webid);
        $this->allowedTimeWindow = $allowedTimeWindow;
        $this->elapsedTime       = time() - strtotime($ts);

        /*
         * Loads the trusted certificate of the IdP: its public key is used to
         * verify the integrity of the signed assertion.
         */
        $idpCertificate = $certRepository->getIdpCertificate($this->referer->host);
        if ( ! $idpCertificate)
        {
           $this->isAuthenticated = 0;
           $this->authnDiagnostic = self::STATUS_IDP_CERTIFICATE_MISSING;

        }
        else if (($this->elapsedTime < $this->allowedTimeWindow) && ( ! isset($error)))
        {

            $signedInfo = $this->requestURI->urlWithoutSignature();
            // Extracts the signature
            $signature = $this->requestURI->digitalSignature();
            // TODO this may be removed in the future
            if ( ! $signature)
                $signature = $sig;

            // Only rsa-sha1 is supported at the moment.
            if ($sigAlg == self::SIG_ALG_RSA_SHA1)
            {
                    $pubKeyId = openssl_get_publickey($idpCertificate);

                    // Verifies the signature
                    $verified = openssl_verify($signedInfo, $signature, $pubKeyId);
                    if ($verified == 1)
                    {
                        // The verification was successful.
                        $this->isAuthenticated = 1;
                        $this->authnDiagnostic = self::STATUS_DELEGATED_LOGIN_OK;
                    }
                    else if ($verified == 0)
                    {
                        // The signature didn't match.
                        $this->isAuthenticated = 0;
                        $this->authnDiagnostic = self::STATUS_SIGNATURE_VERIFICATION_ERR;
                    } 
                    else
                    {
                        // Error during the verification.
                        $this->isAuthenticated = 0;
                        $this->authnDiagnostic = self::STATUS_OPENSSL_VERIFICATION_ERR;
                    }

                    openssl_free_key($pubKeyId);

            }
            else 
            {
                // Unsupported signature algorithm.
                $this->isAuthenticated = 0;
                $this->authnDiagnostic = self::STATUS_UNSUPPORTED_SIGNATURE_ALG_ERR;
            }
        }
        else
        {
            $this->isAuthenticated = 0;
            if (isset($error))
                $this->authnDiagnostic = $error;
            else
                $this->authnDiagnostic = self::STATUS_IDP_RESPONSE_TIMEOUT_ERR;
        }

        if ($createSession)
        {
            if ($this->isAuthenticated)
                $session->setAuthenticatedWebid($this->webid);
            else
                $session->unsetAuthenticatedWebid();
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
