<?php
require_once ("include.php");
$sparql = sparql_connect(SPARQL_ENDPOINT);

$input = $_REQUEST["term"];
$data = array();
// query the triple store looking for a match to $input
$query = 'SELECT DISTINCT ?webid, ?name WHERE {
                    ?webid foaf:name ?name .
                    ?webid foaf:nick ?nick .
                    FILTER (regex(?name, "' . $input . '", "i") || regex(?nick, "' . $input . '", "i") || regex(?webid, "' . $input . '", "i"))
                    MINUS { ?webid a foaf:Person .
                           FILTER (regex(?webid, "nodeID", "i")) }
                    } LIMIT 10';
                    
$result = $sparql->query($query);

if(!$result)  
    die(sparql_errno() . ": " . sparql_error());
    
while ($row = sparql_fetch_array($result)) {
    $json = array();
    $json['label'] = $row['name'] . ' (' . $row['webid'] . ')';
    $json['value'] = '<'. strtolower($row['webid']) . '>';
    $data[] = $json;
}

// return data
header("Content-type: application/json");
echo json_encode($data);
?>
