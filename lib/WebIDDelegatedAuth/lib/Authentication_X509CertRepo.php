<?php
/*-------------------------------------------------------------------------------------
 *
 * Filename   : Authentication_X509CertRepo.php
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


/**
 * An X509Certificate repository
 *
 */
class Authentication_X509CertRepo
{
    const DEFAULT_IDP = 'foafssl.org';
    
    private $IDPCertificates = array ( self::DEFAULT_IDP =>
"-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhFboiwS5HzsQAAerGOj8
Zk6qvEf2QVarlm+c1fxd6f3OoQ9ezib1LjXitw+z2xcLG8lzaTmKOU0jw7KZp6WL
W6gqhAWj2BQ1Lkl9R7aAUpA3ypk52gik8u/5JiWpTt1EV99DP5XNzzQ/QVjkvBlj
rY+1ZeM+XtKzGfbK7eWh583xn3AE6maprXfLAo3BjUWJOQe0VHGYgrBVOcRQrSQ6
34/f+jk22tmYZRzdTT/ZCadeLd7NryIeJbEu0W105JYvKodawSM3/zjt4fXFIPyB
z8vHHmHRd2syDWqUy46YVQfqCfUBdXkHbvVQBtAfvRGUhYbFQm926an6z9uRE5LC
aQIDAQAB
-----END PUBLIC KEY-----
",
                                       'auth.my-profile.eu' =>
"-----BEGIN CERTIFICATE-----
MIIHKzCCBhOgAwIBAgIDBerZMA0GCSqGSIb3DQEBBQUAMIGMMQswCQYDVQQGEwJJ
TDEWMBQGA1UEChMNU3RhcnRDb20gTHRkLjErMCkGA1UECxMiU2VjdXJlIERpZ2l0
YWwgQ2VydGlmaWNhdGUgU2lnbmluZzE4MDYGA1UEAxMvU3RhcnRDb20gQ2xhc3Mg
MSBQcmltYXJ5IEludGVybWVkaWF0ZSBTZXJ2ZXIgQ0EwHhcNMTIwNDA0MTA0NTEw
WhcNMTMwNDA0MTg1MjI3WjBuMRkwFwYDVQQNExBoWTdENnQ3M1A5Y1B2ckF6MQsw
CQYDVQQGEwJGUjEbMBkGA1UEAxMSYXV0aC5teS1wcm9maWxlLmV1MScwJQYJKoZI
hvcNAQkBFhhwb3N0bWFzdGVyQG15LXByb2ZpbGUuZXUwggEiMA0GCSqGSIb3DQEB
AQUAA4IBDwAwggEKAoIBAQC9Ix5SIxwgZjGvx63VXYhFU2+A94FXEO7qr1Ri1ZdZ
WUjItBUNvK6JzdFA1oAPYtGMDs/Uev99Ibj4FfUT3R2GYI2WWv1nGZk6zXFN51Z3
2JAXh1XgX1IW47mhVfzR2yy/i31yPn0oOEhyA3R3dYPs3K6HTd1Eng2rtzbYieVK
zamTkVQmyMG2WFmJBbJ5QoCRkGHR5ZnkJ/4jhZF41GyTTW71dcwOb3ITi9GDsSHv
D5jfUTZy5PXN/91H48SdrVVj6KEziD4h7FnPHpgzpsKJt1wehc83EWR89IEeY/dC
62sNz0s1sMg1BNhoqKesdCSUhjEURGyqGUaF7Ge+0baJAgMBAAGjggOxMIIDrTAJ
BgNVHRMEAjAAMAsGA1UdDwQEAwIDqDATBgNVHSUEDDAKBggrBgEFBQcDATAdBgNV
HQ4EFgQUMM0hTiEKfr0/Lp85d7KgQlBfNgcwHwYDVR0jBBgwFoAU60I00Jiwq5/0
G2sI98xkLu8OLEUwLAYDVR0RBCUwI4ISYXV0aC5teS1wcm9maWxlLmV1gg1teS1w
cm9maWxlLmV1MIICIQYDVR0gBIICGDCCAhQwggIQBgsrBgEEAYG1NwECAjCCAf8w
LgYIKwYBBQUHAgEWImh0dHA6Ly93d3cuc3RhcnRzc2wuY29tL3BvbGljeS5wZGYw
NAYIKwYBBQUHAgEWKGh0dHA6Ly93d3cuc3RhcnRzc2wuY29tL2ludGVybWVkaWF0
ZS5wZGYwgfcGCCsGAQUFBwICMIHqMCcWIFN0YXJ0Q29tIENlcnRpZmljYXRpb24g
QXV0aG9yaXR5MAMCAQEagb5UaGlzIGNlcnRpZmljYXRlIHdhcyBpc3N1ZWQgYWNj
b3JkaW5nIHRvIHRoZSBDbGFzcyAxIFZhbGlkYXRpb24gcmVxdWlyZW1lbnRzIG9m
IHRoZSBTdGFydENvbSBDQSBwb2xpY3ksIHJlbGlhbmNlIG9ubHkgZm9yIHRoZSBp
bnRlbmRlZCBwdXJwb3NlIGluIGNvbXBsaWFuY2Ugb2YgdGhlIHJlbHlpbmcgcGFy
dHkgb2JsaWdhdGlvbnMuMIGcBggrBgEFBQcCAjCBjzAnFiBTdGFydENvbSBDZXJ0
aWZpY2F0aW9uIEF1dGhvcml0eTADAgECGmRMaWFiaWxpdHkgYW5kIHdhcnJhbnRp
ZXMgYXJlIGxpbWl0ZWQhIFNlZSBzZWN0aW9uICJMZWdhbCBhbmQgTGltaXRhdGlv
bnMiIG9mIHRoZSBTdGFydENvbSBDQSBwb2xpY3kuMDUGA1UdHwQuMCwwKqAooCaG
JGh0dHA6Ly9jcmwuc3RhcnRzc2wuY29tL2NydDEtY3JsLmNybDCBjgYIKwYBBQUH
AQEEgYEwfzA5BggrBgEFBQcwAYYtaHR0cDovL29jc3Auc3RhcnRzc2wuY29tL3N1
Yi9jbGFzczEvc2VydmVyL2NhMEIGCCsGAQUFBzAChjZodHRwOi8vYWlhLnN0YXJ0
c3NsLmNvbS9jZXJ0cy9zdWIuY2xhc3MxLnNlcnZlci5jYS5jcnQwIwYDVR0SBBww
GoYYaHR0cDovL3d3dy5zdGFydHNzbC5jb20vMA0GCSqGSIb3DQEBBQUAA4IBAQBp
JFAAxZ2gzThBLAGITaUqXBLMgauQQkFjK6AwmPXu3XxDpxAsXTM6ce0DpwOjDWXQ
CCvF8pydSUKBIwuGN8BcQaC5qnyHamc62YO5Q+VkHbRcLyCB/zqjsOO2+G75AZf9
Z9PIzHUFTxIO2rWu76K6IT8vIpjiIwfF5r5irPOzjbWTFTCQwbhBCF7XdMPlma6d
UFGtn+/N7Hg5F/TPHdI7z/oJIkTP79h73+H9Nv6OD7DKIMWZBfvwR9vNIxvaLOMW
0uxmn9nSfUiAHli5nhvI6gAk1JJf31sOkWmd66KIQzC4pR+GRjPzdmbZpXCjqbjq
rsXEfOCMHw9T3c5vV5qy
-----END CERTIFICATE-----
"
);
    public function  __construct(array $IDPCertificates = array())
    {
        $this->IDPCertificates =
                array_merge($this->IDPCertificates, $IDPCertificates);
    }

    /**
     * Get the Identity Provider's certificate
     * @param string $IPDDomainName Identity Provider's domain name
     *        (e.g. foafssl.org)
     * @return object requiested x509 certificate content
     *         (or the default IDP's certificate, if the requested is not found)
     */
    public function getIdpCertificate($IDPDomainName)
    {
       return isset($this->IDPCertificates[$IDPDomainName]) ?
               $this->IDPCertificates[$IDPDomainName]
              : $this->IDPCertificates[self::DEFAULT_IDP];
    }
}
?>
