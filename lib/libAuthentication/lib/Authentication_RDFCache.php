<?php
/**
 * This file was developed by Nathan <nathan@webr3.org>
 *
 * Created:     Nath - 19 Apr 2010 21:39:29
 * Modified:    SVN: $Id: Cache.php 82 2010-04-20 23:28:29Z root $
 * PHP Version: 5.1.6+
 *
 * @package   @project.name@
 * @author    Nathan <nathan@webr3.org>
 * @version   SVN: $Revision: 82 $
 */

class RDFCache
{
	private static $__cache = array();
	 
	public static function getRDF( $uri , $rdf = null )
	{
		$uri = preg_replace( '/#.*/i' , '' , $uri ); #dereference
		if( !isset(self::$__cache[$uri]) && $rdf == NULL ) {
			$rdf = file_get_contents($uri);
		}
		if( !isset(self::$__cache[$uri]) && $rdf != NULL ) {
			$parser = ARC2::getRDFParser();
			$parser->parse( $uri , $rdf );
			$index = $parser->getSimpleIndex(0);
			$triples = $parser->getTriples();
			self::$__cache[$uri] = (object)array(
				'index' => $index,
				'triples' => $triples,
				'rdf' => $rdf
			);		
		}
		return self::$__cache[$uri];
	}
	
	
}
