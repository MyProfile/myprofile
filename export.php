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

// Offer to download the profile data
if ((isset($_REQUEST['download'])) && (isset($_REQUEST['uri']))) {
    $graph = new EasyRdf_Graph($_REQUEST['uri']);
    $graph->load();
    $data = $graph->serialise($_REQUEST['format']);
    if (!is_scalar($data)) {
        $data = var_export($data, true);
    } else {
        $data = print_r($data, true);
    }
    
    $format = $_REQUEST['format'];

    $content_type = '';
    $extension = '';
	if ($format == 'rdfxml') {
		$content_type = 'application/rdf+xml';
		$extension = 'rdf';
	} else if ($format == 'turtle') {
		$content_type = 'application/x-turtle';
		$extension = 'ttl';
	} else if ($format == 'ntriples') {
		$content_type = 'application/ntriples';
		$extension = 'nt';
	} else if ($format == 'n3') {
	    $content_type = 'application/n3';
	    $extension = 'n3';
	} else if ($format == 'json') {
	    $content_type = 'application/json';
	    $extension = 'json';
	} else {
		$content_type = 'text/plain';
		$extension = 'txt';
	}

    header('Content-disposition: attachment; filename="profile.' . $extension . '"');
    header('Content-type: ' . $content_type);
    echo $data;
    exit();
}

$ret = '';

$format_options = array();
foreach (EasyRdf_Format::getFormats() as $format) {
    if ($format->getSerialiserClass()) {
        $format_options[$format->getLabel()] = $format->getName();
    }
}

if (isset($_REQUEST['uri']))
    $uri = $_REQUEST['uri'];
else if (isset($_SESSION['webid']))
    $uri = $_SESSION['webid'];
else
    $uri = '';

$ret .= "<div class=\"container\">\n";
$ret .= "<p><font style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">Convert/Export Profile</font></p><br/>\n";
$ret .= "<div class=\"clear\"></div>\n";
$ret .= "</div>\n";

$ret .= "      <form name=\"convert\" action=\"\" method=\"GET\">\n";
$ret .= "       <input type=\"hidden\" name=\"doit\" value=\"1\">\n";
if (isset($_REQUEST['format']))
    $ret .= "       <input type=\"hidden\" name=\"format\" value=\"" . $_REQUEST['format'] . "\">\n";
$ret .= "       <p>WebID URI: <input type=\"text\" name=\"uri\" size=\"50\" placeholder=\"http://fcns.eu/people/andrei/card#me\" value=\"" . $uri . "\" /></p><br/>\n";
$ret .= "       <p>Serialization: <select name=\"format\">\n";

foreach (EasyRdf_Format::getFormats() as $format) {
    if ($format->getSerialiserClass()) {
        $ret .= "<option value=\"" . $format->getName() . "\""; $ret .= $format->getName() == 'rdfxml'?" selected":""; $ret .= ">" . $format->getLabel() . "</option>\n";
    }
}
$ret .= "      </select>\n";
$ret .= "      <input class=\"btn btn-primary\" type=\"submit\" name=\"convert\" value=\" Convert \"></p>\n";
$ret .= "       </form>\n";
$ret .= "     <div class=\"clear\"></div>\n";


// Display the converted profile
if ((isset($_REQUEST['uri'])) && (isset($_REQUEST['doit']))) {
    $graph = new EasyRdf_Graph($_REQUEST['uri']);
    $graph->load();
    $data = $graph->serialise($_REQUEST['format']);
    if (!is_scalar($data)) {
        $data = var_export($data, true);
    } else {
        $data = print_r($data, true);
    }
    $ret .= "     <div class=\"container\">\n";
    $ret .= "       <p><textarea name=\"data\" style=\"width: 810px; height: 500px;\">" . htmlspecialchars($data) . "</textarea></p>\n";
    $ret .= "      <form name=\"convert\" action=\"\" method=\"GET\">\n";
    $ret .= "       <input type=\"hidden\" name=\"uri\" value=\"" . $_REQUEST['uri'] . "\">\n";
    $ret .= "       <input type=\"hidden\" name=\"format\" value=\"" . $_REQUEST['format'] . "\">\n";
    $ret .= "       <input class=\"btn btn-primary\" type=\"submit\" name=\"download\" value=\" Download \">";
    $ret .= "       </form>\n";
    $ret .= "      <div class=\"clear\"></div>\n";
    $ret .= "     </div>\n";
}

include 'header.php';
echo $ret;
include 'footer.php';
?>

