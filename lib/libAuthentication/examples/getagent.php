<?php

chdir (dirname(__FILE__) . "/..");

require_once("config.php");
require_once("lib/Authentication_AgentARC.php");

$webid = (isset($_GET['webid']))?$_GET['webid']:'http://www.w3.org/People/Berners-Lee/card';

//$auth = new Authentication_AgentARC($GLOBALS['config'], $webid);
$auth = new Authentication_AgentARC($GLOBALS['config']);

$auth->setAgent($webid);

print "<pre>";
print var_dump($auth);
print "</pre>";

$agent = $auth->getAgent();

print "Agent<pre>";
print_r($agent);
print "</pre>";

?>
