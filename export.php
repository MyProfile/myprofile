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

$ret .= "<p></p>\n";
$ret .= "<h2><strong>Export Profile</strong></h2>\n";

$ret .= "<form name=\"convert\" action=\"\" method=\"post\">\n";
$ret .= "<input type=\"hidden\" name=\"doit\" value=\"1\">\n";
if (isset($_REQUEST['format']))
    $ret .= "<input type=\"hidden\" name=\"format\" value=\"" . $_REQUEST['format'] . "\">\n";
$ret .= "<p>Serialization: <select name=\"format\">\n";

foreach (EasyRdf_Format::getFormats() as $format) {
    if ($format->getSerialiserClass()) {
        $ret .= "<option value=\"" . $format->getName() . "\""; $ret .= $format->getName() == 'rdfxml'?" selected":""; $ret .= ">" . $format->getLabel() . "</option>\n";
    }
}
$ret .= "</select>\n";
$ret .= "<input class=\"btn btn-primary\" type=\"submit\" name=\"export\" value=\" Export \"></p>\n";
$ret .= "</form>\n";


// Display the converted profile
if (isset($_REQUEST['doit'])) {
    $graph = new EasyRdf_Graph($_SESSION['webid']);
    $graph->load();
    $data = $graph->serialise($_REQUEST['format']);
    if (!is_scalar($data)) {
        $data = var_export($data, true);
    } else {
        $data = print_r($data, true);
    }
    $ret .= "<p><textarea name=\"data\" style=\"width: 810px; height: 500px;\">" . htmlspecialchars($data) . "</textarea></p>\n";
}


