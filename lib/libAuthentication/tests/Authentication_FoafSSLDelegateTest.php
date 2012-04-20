<?php
//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_HelperTest.php
// Date       : 26th Mar 2010
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

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../lib/Authentication_FoafSSLDelegate.php';
/**
 * @author László Török
 */
class Authentication_FoafSSLDelegateTest extends PHPUnit_Framework_TestCase
{
    private $test_idp_URL = 'http://testidp.org/';
    private $test_idp_private_key = '
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBANDiE2+Xi/WnO+s120NiiJhNyIButVu6zxqlVzz0wy2j4kQVUC4Z
RZD80IY+4wIiX2YxKBZKGnd2TtPkcJ/ljkUCAwEAAQJAL151ZeMKHEU2c1qdRKS9
sTxCcc2pVwoAGVzRccNX16tfmCf8FjxuM3WmLdsPxYoHrwb1LFNxiNk1MXrxjH3R
6QIhAPB7edmcjH4bhMaJBztcbNE1VRCEi/bisAwiPPMq9/2nAiEA3lyc5+f6DEIJ
h1y6BWkdVULDSM+jpi1XiV/DevxuijMCIQCAEPGqHsF+4v7Jj+3HAgh9PU6otj2n
Y79nJtCYmvhoHwIgNDePaS4inApN7omp7WdXyhPZhBmulnGDYvEoGJN66d0CIHra
I2SvDkQ5CmrzkW5qPaE2oO7BSqAhRZxiYpZFb5CI
-----END RSA PRIVATE KEY-----
';
    private $testidp_cert = '
-----BEGIN CERTIFICATE-----
MIIB+zCCAaWgAwIBAgIJALRu4UYakrHfMA0GCSqGSIb3DQEBBQUAMDUxCzAJBgNV
BAYTAkFVMRMwEQYDVQQIEwpTb21lLVN0YXRlMREwDwYDVQQKEwhUZXN0IElEUDAe
Fw0xMDA0MDYyMDMxMDhaFw0xMTA0MDYyMDMxMDhaMDUxCzAJBgNVBAYTAkFVMRMw
EQYDVQQIEwpTb21lLVN0YXRlMREwDwYDVQQKEwhUZXN0IElEUDBcMA0GCSqGSIb3
DQEBAQUAA0sAMEgCQQDooaDm/YzdQLGGz0QbZJ599l0FaPVBpF/xv4SkLCz59V5S
tVo2RwyUZ75klywVKp37pUGpG6OwhHdCWx+qSOY/AgMBAAGjgZcwgZQwHQYDVR0O
BBYEFB84tFBN9GbuJT8Od9sqAP0b+ziiMGUGA1UdIwReMFyAFB84tFBN9GbuJT8O
d9sqAP0b+ziioTmkNzA1MQswCQYDVQQGEwJBVTETMBEGA1UECBMKU29tZS1TdGF0
ZTERMA8GA1UEChMIVGVzdCBJRFCCCQC0buFGGpKx3zAMBgNVHRMEBTADAQH/MA0G
CSqGSIb3DQEBBQUAA0EA1eixIxHxZR6aYlkDEsxsd26QrnYW8B4iplkzCSCFInxl
G/YzrI9CJ5hGnjPPzPwQ8u9zREp71KNwVsrn3h+SVg==
-----END CERTIFICATE-----
';
    /** Signed by foafssl-org */
    private $validIdentityResponse =
     'http://foaf.selfip.org/demoprocesslogin.php?
      webid=http%3A%2F%2Ffoaf.me%2Ftl73%23me&
      ts=2010-04-06T10%3A39%3A32-0700&
      sig=khcCt3kMDJ%2FJ9a86aaFmu9DA5PbArxC%2FzhGStW%2BCM9XLVjkDZ4a8zhiM%2Fy33Od
      Fg6OD1pdAowcL57EaDzRO63oc6UF1Km4bGc4%2Fd42N38RXnO4TmcQudeDjta7E46QxWT9%2F7
      LVI0XvuZPqWjZL%2Futw%2FKprFMbsfwMZZvcOOGpUY%3D';

    /**
     * @test
     */
    public function Auth_fails_if_IDP_returns_confirmation_too_late()
    {
        $allowedTimeWindow = 0;
        $auth = new Authentication_FoafSSLDelegate( false, 
                Authentication_SignedURL::parse($this->validIdentityResponse),
                NULL, NULL, NULL, 
                Authentication_FoafSSLDelegate::SIG_ALG_RSA_SHA1,
                $allowedTimeWindow);
        $this->assertEquals(
                Authentication_FoafSSLDelegate::STATUS_IDP_RESPONSE_TIMEOUT_ERR,
                            $auth->authnDiagnostic);
    }
    /**
     * @test
     */
    public function Auth_succesful_if_signed_url_can_be_verified()
    {
        

        $signedUrl = $this->signedUrl();

        $certRepo = new Authentication_X509CertRepo(array(
            $this->test_idp_URL => $this->testidp_cert
        ));
        $referer = new Authentication_URL($this->test_idp_URL);

        $auth = new Authentication_FoafSSLDelegate(
                false, $signedUrl, $referer, $certRepo);

        $this->assertEquals(
                Authentication_FoafSSLDelegate::STATUS_DELEGATED_LOGIN_OK,
                $auth->authnDiagnostic);

        $this->assertEquals(1, $auth->isAuthenticated);
       
    }

    private function signedUrl()
    {
        $now = new DateTime();
        $idpResponseURL =
            'http://foaf.selfip.org/demoprocesslogin.php?
             webid=http://foaf.me/tl73#me&
             ts='.$now->format(DateTime::ISO8601);
        openssl_sign($idpResponseURL, $signature, $this->test_idp_private_key);
        $idpSignedResponseURL= $idpResponseURL.'&sig='.base64_encode($signature);
        echo $idpSignedResponseURL.'\n';
        return Authentication_SignedURL::parse($idpSignedResponseURL);
    }
}

?>
