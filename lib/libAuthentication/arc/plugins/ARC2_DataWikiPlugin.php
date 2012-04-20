<?php

/* ARC2 static class inclusion */ 
include_once('/home/foaf/www/arc/ARC2.php');


/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Data Wiki Plugin
author:   Benjamin Nowack
version:  2008-01-14
*/

ARC2::inc('Class');

class ARC2_DataWikiPlugin extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_DataWikiPlugin($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->headers = array('http' => 'HTTP/1.1 200 OK');
  }

  /*  */

  function handleRequest($webid, $page) {
	if (preg_match('/^post$/i', $_SERVER['REQUEST_METHOD'])) {
		$ret = $this->handleUpdateRequest($webid, $page);
    }

	return $ret;
  }

  function setHeader($k, $v) {
    $this->headers[$k] = $v;
  }
  
  function sendHeaders() {
    foreach ($this->headers as $k => $v) {
      header($v);
    }
  }
  
  function getResult() {
	return $this->result;
  }
  
  function go($webid, $page) {
    $ret = $this->handleRequest($webid, $page);
    $this->sendHeaders();
	echo $this->getResult();
	return $ret;
  }

  /*  */

  function handleUpdateRequest($webid, $uri) {
	$this->result = '';
	$this->setHeader('http', 'HTTP/1.1 403 Forbidden');
	if ($q = @file_get_contents('php://input')) {
//	$this->sparulLog($uri, $webid, $q);
	$triples = array();
	$parser = ARC2::getRDFParser($this->a);
	$parser->parse($uri);
	$triples = $parser->getTriples();
	$index = ARC2::getSimpleIndex($triples, 0);
	/* split combined INSERT/DELETE query */
	if (preg_match('/^\s*(DELETE.*)\s*(INSERT.*)$/is', $q, $m)) {
		$qs = array($m[1], $m[2]);
	}
	else {
		$qs = array($q);
	}
	$tmpfname = tempnam("/home/foaf/www/datawiki/rdf", "rdf_");
	foreach ($qs as $q) {
		$index = $this->getUpdatedIndex($index, $q, $uri);
		if (!$this->getErrors()) {
			$this->setHeader('http', 'HTTP/1.1 200 OK');
			if ($index) {
				/* todo: create dirs, if necessary */
				$fp = fopen($tmpfname, 'w');
				$doc = $parser->toRDFXML($index);
				fwrite($fp, $doc);
				fclose($fp);
			}
			else {
				unlink($tmpfname);
			}
		}
	}
		return $tmpfname;
	}
  }
  
  function getUpdatedIndex($old_index, $q, $file) {
    if (!preg_match('/^\s*(INSERT|DELETE)\s*(INTO|FROM)?\s*(.*)$/is', $q, $m)) {
      return 0;
    }
    $qt = strtolower($m[1]);
    /* inject a target graph, if necessary */
    if (!$m[2]) {
      $q = strtoupper($qt) . (($qt == 'insert') ? ' INTO ' : ' FROM') . ' <' . $file . '> ' . $m[3];
    }
    /* parse the query */
//    $this->writeLog($q);
    ARC2::inc('SPARQLPlusParser');
    $p = & new ARC2_SPARQLPlusParser($this->a, $this);
    $p->parse($q);
    $infos = $p->getQueryInfos();
    /* errors? */
    if ($errors = $this->getErrors()) {
      $this->setHeader('http', 'HTTP/1.1 400 Bad Request');
      $this->setHeader('content-type', 'Content-type: text/plain; charset=utf-8');
      $this->result = join("\n", $errors);
      return 0;
    }
    $q_index = ARC2::getSimpleIndex($infos['query']['construct_triples'], 0);
    if ($qt == 'insert') {
      return ARC2::getMergedIndex($old_index, $q_index);
    }
    elseif ($qt == 'delete') {
      return ARC2::getCleanedIndex($old_index, $q_index);
    }
  }
  
  /*  */

  function writeLog($v) {
 //   return 1;
    $fp = fopen('/home/foaf/www/datawiki/log.txt', 'a');
    $now = time();
    fwrite($fp, date('Y-m-d H:i:s', $now) . ' : ' . $v . '' . "\r\n");
    fclose($fp);
  }

/*
  function sparulLog($page, $webid, $sparul) {
	//   return 1;
    $fp = fopen('/home/foaf/www/datawiki/sparul2.log', 'a');
    $now = time();
    fwrite($fp, date('Y-m-d H:i:s', $now) . ' : webid : ' . $webid . ' : uri : ' . $page . ' : sparul : ' . $sparul . '' . "\r\n");
    fclose($fp);
  }
*/

  /*  */

}
?>