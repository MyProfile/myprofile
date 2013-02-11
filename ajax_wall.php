<?php
require("include.php");

$w = new Wall($_REQUEST['owner']);
echo $w->load($_REQUEST['count'], $_REQUEST['offset'], $_REQUEST['activity']);

