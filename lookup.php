<?php
/*
 *  Copyright (C) 2012 MyProfile Project
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is furnished 
 *  to do so, subject to the following conditions:

 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.

 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
require_once 'include.php';
$friends_on = 'friends-on';
$title = "Lookup someone";
include 'header.php';

$search = (isset($_REQUEST['search'])) ? $_REQUEST['search'] : 'name, nickname or WebID';

$ret = "<div class=\"content relative shadow clearfix main\">";
$ret .= "<div>";
$ret .= "<form method=\"get\">\n";
$ret .= "<input type=\"search\" name=\"search\" onfocus=\"this.value=(this.value=='name, nickname or WebID') ? '' : this.value;\" onblur=\"this.value=(this.value=='') ? 'name, nickname or WebID' : this.value;\" value=\"".$search."\" />\n";
$ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Search \">\n";
$ret .= "</form></div>\n";

// Display any alerts here
if (isset($confirmation))
    $ret .= $confirmation;

if (isset($_REQUEST['search'])) {
    $ret .= "<div>";
	$ret .= "<h2 class=\"profileHeaders\">Search results for: ";
	if (strlen($_REQUEST['search']) > 50)
    	$ret .= substr(urldecode($_REQUEST['search']), 0, 47) . '...';
    else
        $ret .= urldecode($_REQUEST['search']);
    $ret .= "</h2>\n";

    $ret .= sparql_lookup(trim($_REQUEST['search']), BASE_URI, SPARQL_ENDPOINT);

}
$ret .= "</div>";
echo $ret;
include 'footer.php';
?>        
