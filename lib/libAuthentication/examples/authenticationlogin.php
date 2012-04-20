<?php

chdir (dirname(__FILE__) . "/..");

require_once("config.php");
require_once("lib/Authentication.php");

$auth = new Authentication($GLOBALS['config']);

if ($auth->isAuthenticated())
    print "Hello : $auth->webid<br/>";
else
    print "Sorry you are not logged in<br/>";

print "<pre>";
print var_dump($auth);
print "</pre>";

$auth->logout();

?>
