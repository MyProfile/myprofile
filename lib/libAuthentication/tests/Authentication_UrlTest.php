<?php
//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_UrlTest.php
// Date       : 25th Feb 2010
//
// Copyright 2008-2010 foaf.me
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
//
// "Everything should be made as simple as possible, but no simpler."
// -- Albert Einstein
//
//-----------------------------------------------------------------------------------------------------------------------------------

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../lib/Authentication_URL.php';
/**
 * @author László Török
 */
class Authentication_URLTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function valid_urls_are_accepted()
    {
        $url_string = 'http://foaf.me/myid#me';
        $url = Authentication_URL::parse($url_string);
        
        $this->assertEquals('http',$url->scheme);
        $this->assertEquals('foaf.me',$url->host);
        $this->assertEquals('/myid#me',$url->path);
        
        $url_serizalized = sprintf('%s',$url);
        $this->assertEquals('http://foaf.me:80/myid#me', $url_serizalized);
        $this->assertEquals($url_string, $url->parsedURL);

    }

    /**
     * @test
     */
    public function returns_NULL_for_invalid_urls()
    {
        $this->assertEquals(NULL, Authentication_URL::parse('http:///myid#me') );
        $this->assertEquals(NULL, Authentication_URL::parse('http://./myid#me') );
    }

    /**
     * @test
     */
    public function query_parameters_are_available_as_key_value_pairs()
    {
        $url_string = 'http://foaf-ssl.org/?authreqissuer=http://foaf.me/simpleLogin.php';
        $url = Authentication_URL::parse($url_string);

        $this->assertEquals('http://foaf.me/simpleLogin.php',$url->getQueryParameter('authreqissuer'));
        $this->assertEquals('someDefaultValue',$url->getQueryParameter('unknownKey', 'someDefaultValue'),
                'a default value can be specified if key not found');
    }
}

?>
