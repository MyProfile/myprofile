<?php
require_once ("include.php");
$sparql = sparql_connect(SPARQL_ENDPOINT);

$input = $_REQUEST["term"];
$data = array();
// query the triple store looking for a match to $input
$query = 'SELECT DISTINCT ?webid, ?name, ?img, ?depiction WHERE {
                    ?webid foaf:name ?name .
                    ?webid foaf:nick ?nick .
                    OPTIONAL { ?webid foaf:img ?img } .
                    OPTIONAL { ?webid foaf:depiction ?depiction } .
                    FILTER (regex(?name, "' . $input . '", "i") || regex(?nick, "' . $input . '", "i") || regex(?webid, "' . $input . '", "i"))
                    MINUS { ?webid a foaf:Person .
                           FILTER (regex(?webid, "nodeID", "i")) }
                    } LIMIT 10';
                    
$result = $sparql->query($query);

if(!$result)  
    die(sparql_errno() . ": " . sparql_error());
    
while ($row = sparql_fetch_array($result)) {
    if (strlen($row['img']) > 0)
        $picture = $row['img'];
    else if (strlen($row['depiction']) > 0)
        $picture = $row['depiction'];
    else
        $picture = 'img/nouser.png';

    $json = array();
    $json['webid'] = $row['webid'];
    $json['name'] = $row['name'];

    $json['img'] = $picture;
/*    
    $json['label'] = '<table><tr>';
    $json['label'] .= '<td><img width="30" src="' . $picture . '"/></td>';
    $json['label'] .= '<td><strong>' . $row['name'] . '</strong><br/>' . $row['webid'] . '</td>';
    $json['label'] .= '</tr></table>';
*/
    $json['label'] = $row['name'] . ' (' . $row['webid'] . ')';    
    $json['value'] = strtolower($row['webid']);
    $data[] = $json;
}

// return data
header("Content-type: application/json");
echo json_encode($data);

//echo $data;
?>
