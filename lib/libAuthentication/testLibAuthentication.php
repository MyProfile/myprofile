<h1>getAuth();</h1><b>Description:</b><br><br><span style="color:blue">The getAuth(); function checks if the presented SSL certificate has a subjectAltName with a URI pointing to a FOAF file holding a reference to the public key which matches the public key of the supplied client certificate.</span><br><br><b>Code :</b><br><br><span style="color:green">&lt;?php require_once('./libAuthentication.php');<br><br>$auth = getAuth();<br><br>print_r($auth);&nbsp;?&gt;</span><br><br><b>Returns :</b><br><pre>

Array
(
    [certRSAKey] => Array
        (
            [modulus] => ...
	    [exponent] => 10001
        )

    [subjectAltName] => http://foaf.me/romeo#me
    [subjectAltNameRSAKey] => Array
        (
            [modulus] => ...
            [exponent] => 10001
        )

    [isAuthenticated] => [ 0 | 1 ] {Return 1 if the authentication process succeeds}
    [authDiagnostic] => [ No client certificate supplied on an unsecure connection | 
			  No client certificate supplied | 
			  No RSA Key in the supplied client certificate | 
			  Client Certificate RSAkey matches SAN RSAkey | 
			  Client Certificate RSAkey does not match SAN RSAkey ]
)

</pre>
<b>Example Output:</b><br><span style="color:red"><b><pre>Array
(
    [isAuthenticated] => 0
    [authDiagnostic] => No client certificate supplied on an unsecure connection
)
</pre></b></span><br/>
<a href="http://foaf.me/download.html">Download</a>
