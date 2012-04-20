1. Introduction
===============

_libAuthentication_ is a PHP implementation of the FOAF+SSL protocol.
Further details of FOAF+SSL can be obtained at <http://esw.w3.org/topic/foaf+ssl>

If you would like to learn how to get going quickly without diving to much into
technical details, then read section 2. and 3.

The core classes of _libAuthentication_ are tackled in section 4. and 5.

--------------------------------------------------------------------------------

2. How to set up Foaf+SSL authentication in a few lines of code
================================================================================

There are a few flavours of Foaf+SSL authentication. The following very simple
example shows how to setup a Foaf+SSL authentication relying on an identity 
provider such as foaf-ssl.org.

Prerequisites:

  *   Publicly available internet site
  *   Apache 2.2 and PHP 5.2.x or higher

Checkout and create a script that will be the entry point for your application:

    git clone git://github.com/melvincarvalho/libAuthentication.git

    cat > index.php
    <?php

    require_once('libAuthentication/lib/Authentication.php');
    $auth = new Authentication_FoafSSLDelegate();

    if (!$auth->isAuthenticated()) 
    { 
      echo '<a href="https://foafssl.org/srv/idp?authreqissuer=http://localhost/index.php">Click here to Login</a>';
    } 
    else 
    { 
      echo 'Your have succesfully logged in.<pre>';
      print_r($auth);
    } 

Make sure the _"authreqissuer"_ points to YOUR site and...  
... YOU ARE DONE!

You just set up you first Foaf+SSL powered site. Behind the scenes,
_libAuthentication_ has a copy of foaf-ssl.org's certificate which is used
in the authentication process.

--------------------------------------------------------------------------------

3. A more complex setup
================================================================================

If you run your own database that contains Foaf profiles, you can use the
Authentication class that handles all supported authentication methods automatically.

_Note:_
config.php must be configured with the details of the db you have created for ARC2.
Further details of ARC2 are at <http://arc.semsol.org/>

_Note:_
The current RDF library has a dependency on mysql, so it will only work if you
have mysql installed.

You can find the code in examples/authenticationlogin.php

Calling `new Authentication()` tests if the user has presented a SSL Client Certificate
that matches to public key as expressed in a FOAF file. Authentication can work with
SSL Client Certificates presented to your server or with the FOAF+SSL login
delegation server. To use the delegated FOAF+SSL version ask the user to click on 
something like the following: 

    https://foafssl.org/srv/idp?authreqissuer=http://foaf.me/index.php

In this case <http://foaf.me/index.php> executes `new Authentication($config)`. 
Alternatively configured your server to request Client Certificates on the https 
page the user tries to access. The page needs to execute
`new Authentication($config)`.

Authentication stores the result of the user login process in a session so 
subsequent calls to Authentication's constructor do not preform any further 
remote checks on FOAF files. To re-perform the FOAF fetch you need to execute 

    $auth->logout()

**WARNING**

Authentication will try to create a session variable therefore it is very
important to execute `new Authentication()` before any html is sent to the client.
Further details [here](http://uk3.php.net/manual/en/book.session.php)

--------------------------------------------------------------------------------

4. Brief overview of _libAuthentication_'s core classes
================================================================================

_libAuthentication_ provides the following core classes:

*   Authentication_AgentARC
    Parse a Foaf file identified by a Webid (e.g. <http://foaf.me/tl73> )

*   Authentication
    Authenticate user by trying all supported authentication methods in a fixed 
    and reasonable sequence

*   Authentication_FoafSSLDelegate
    Authenticate via the delegated Foaf+SSL method using a 3rd party FoafSSL 
    identity provider (foafssl.org by default)    

*   Authentication_FoafSSLARC
    Authenticate via "native" Foaf+SSL  by relying on a database that stores Foaf 
    files. (currently the ARC RDF store is used as storage backend)

*   Authentication_Session
    Create a session cookie after successful authentication to speed up 
    subsequent authentication attempts

A detailed description of the core classes an their usage follows.

--------------------------------------------------------------------------------

5. Detailed description of _libAuthentication_'s core classes
================================================================================

class Authentication\_AgentARC (implements Authentication\_AgentAbstract)
--------------------------------------------------------------------------------

This is basically a parser for a Foaf file identified by a URI, like
<http://foaf.me/tl73>.

It relies on [ARC RDF Store](http://arc.semsol.org/download).

Assuming you pass it a valid URI and appropriate store configuration, it loads 
the corresponding foaf document.

    $config =  array ('db_name'  => 'your_db_name',        // db name  
                      'db_user'    => 'your_db_username',  // db username  
                      'db_pwd'    => 'your_password',     // db password  
                'store_name'    => 'arc_tests',           // tmp table name  
    $webid = 'http://foaf.me/tl73';  
    $foafDoc = new Authentication_AgentARC($config, $webid);  
  
`$foafDoc->agentURI` contains the URI passed in.  

On Success:  

`$foafDoc->agentId` contains the value of the _primaryTopic_ property if defined, 
otherwise it falls back to `$this->agentURI`.

You can get the parsed foaf file properties as an associative map.

    $foafProperties = $foafDoc->getAgent()

These properties are currently (only those appear that are found in the foaf file):
name, mbox, homepage, nick, weblog, img, RSAKey (with mod and exp) and more.
The semantics of these properties are defined in <http://xmlns.com/foaf/spec/>.

On Error: 
 
You can retrieve error message by inspecting the content of 

    $foafDoc->errors 

(an array).

_Note_: 
If the ARC store doesn't exists (first run), it will be created and setup 
automatically. However, the parsing will obviously fail, since the store is empty.

class Authentication
--------------------------------------------------------------------------------
This class provides easy access to all supported authentication mechanisms. 
On instantiation, it performs the following operations:

1.  Checks if an authentication session cookie is present
2.  If 1. fails, it tries to authenticate via delegated Foaf+SSL (see _Authentication\_FoafSSLDelegate_)
3.  If 2. fails, it tries to authenticate via native Foaf+SSL (see _Authentication\_FoafSSLARC_)
4.  If authentication is successful, it loads the corresponding foaf file

        $auth = new Authentication($config) // $config is optional,  
                                            // only necessary if ARC is used

On Success:

-   `$auth->isAuthenticated()` returns true
-   `$auth->webid` contains the authenticated webid
-   `$auth->getAgent()` returns the parsed foaf profile that is tied to the 
    authenticated webid (as in _Authentication\_AgentARC_)

On Error:  

If an error occurs, an explanation can be retrieved by inspecting
`$auth->$authnDiagnostic`.
If you want to terminate the authenticated session, it is a good idea to call
`$auth->logout`.

class Authentication_Session
--------------------------------------------------------------------------------

This class usually won't be instantiated directly. If a given authentication 
method succeeds, it can optionally persist that information by instantiating 
_Authentication\_Session_. It stores the authenticated webid and the parsed foaf
file in `$_SESSION`. This results in a significant speed up in successive 
authentication attempts. If you want to create it manually, you can do that as follows:

    $authSession = new Authentication_Session( 1, $agent, $webid)

where 1 indicates the fact of successful authentication, 
`$agent` is an associative array representation of the Foaf file that is associated 
with `$webid`, which is a URI string.

class Authentication_FoafSSLDelegate
--------------------------------------------------------------------------------

Using the delegated Foaf+SSL method is probably the easiest way to get you start 
quickly leveraging this powerful authentication method. It is also the easiest 
to set up. Refer to Section 2. for an example and make sure you set up the example 
using a public domain name or a public IP address. I you want find out more details 
how the identity provider works, see <https://foafssl.org/srv/idp>.

You need to instantiate _Authentication\_FoafSSLDelegate_ at a common entry point 
to your site (e.g. index.php):

    $auth = new Authentication_FoafSSLDelegate();

Most of the input is automatically retrieved from the global php context variables 
(`$_REQUEST`, `$_SERVER` etc.), so using the default constructor parameters is fine. 

On Success:

-   `$auth->isAuthenticated()` returns true
-   `$auth->webid` contains the authenticated webid

If not explicitly disabled, on successful authentication an instance of 
_Authentication\_Session_ will also be created, to speed up further authentication 
attempts. If that something you don't want to happend, you need to call the constructor 
as follows:

    $auth = new Authentication_FoafSSLDelegate( false );

On Error:  

If an error occurs, an explanation can be retrieved by inspecting `$auth->$authnDiagnostic`.
If you want to terminate the authenticated session, it is a good idea to call `$auth->logout`.

class Authentication\_FoafSSLARC (implements Authentication\_FoafSSLAbstract)
--------------------------------------------------------------------------------

This implements the "native" Foaf+SSL authentication. The current implementation 
relies on the ARC RDF store. The public key tied to the webid is extracted via 
Sparql query, as are other properties of the corresponding foaf file.
To perform the authentication, you only need

    $auth = new Authentication_FoafSSLARC($config)

where $config is the same configuration you used for _Authentication\_AgentARC_.
The rest of the constructor parameters are better left at their default value.

On Success:

-   `$auth->isAuthenticated()`	returns true
-   `$auth->webid`			    contains the authenticated webid
-   `$auth->certModulus`		modulus of the associated public key      
-   `$auth->certExponent`		exponent of the associated public key
-   `$auth->certSubjectAltName`	subjectAltName from the x509 extension section

_FoafSSLARC_ (_FoafSSLAbstract_) also instantiates a _Authentication\_Session_. 
You can disable that by passing "false" as 3rd parameter.

    $auth = new Authentication_FoafSSLARC($config, NULL, false);

On Error: 

If an error occurs, an explanation can be retrieved by inspecting `$auth->$authnDiagnostic`.
If you want to terminate the authenticated session, it is a good idea to call `$auth->logout`.

Reference
================================================================================

For detailed information on _libAuthentication_ classes please refer to the API
documentation.