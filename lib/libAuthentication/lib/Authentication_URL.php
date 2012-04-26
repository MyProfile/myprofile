<?php
//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_URL.php
// Date       : 26th Feb 2010
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

/**
 * Represents a valid Uniform Resource Locator
 *
 * @author László Török
 */
class Authentication_URL
{
    public $scheme;
    public $host;
    public $port;
    public $path;
    public $parsedURL;
    private $query = array();

    /**
     *
     * @param string $URL_string String to parse
     * @return Authentication_URL A valid Authentication_URL instance (or NULL on error)
     */
    public static function parse($URL_string)
    {
        $URL = new Authentication_URL();
        $isOk = $URL->parseInternal($URL_string);
        return $isOk ? $URL : NULL;
    }
    /**
     * Returns query string parameter value by key
     * @param string $key
     * @param mixed $default
     * @return mixed The required "value" (or $default if not found)
     */
    public function getQueryParameter($key,$default = NULL)
    {
        return isset($this->query[$key]) ? $this->query[$key] : $default;
    }
    /**
     * Normalized URL serialization scheme://domain:port/path
     * @return <type> Returns the parsed URL in a normalized form
     */
    public function __toString()
    {
        return $this->scheme.'://'.$this->host.':'.$this->port.$this->path;
    }

    protected function parseInternal($URL_string)
    {
        $URL_map = @parse_URL($URL_string);

        if ( !$URL_map
          || !$URL_map['host']
                // some minimalistic sanitization
          || !preg_match('/[a-zA-Z0-9._-]*[a-zA-Z0-9]$/', $URL_map['host']) )
        {
            return false;
        }
        $URL_map = array_map('trim', $URL_map);

        $this->parsedURL = $URL_string;
        $this->scheme = isset($URL_map['scheme']) ? $URL_map['scheme'] : 'http' ;
        $this->host = $URL_map['host'];
        $this->port = isset($URL_map['port']) ? (int)$URL_map['port'] : 80;
        $this->path = isset($URL_map['path']) ? $URL_map['path'] : '';
        if (isset($URL_map['query'])) {
            parse_str($URL_map['query'], $this->query);
        }
        if (!$this->query) {
            $this->query = array();
        }

        if ($this->path == '') {
            $this->path = '/';
        }

        $this->path .= isset ( $URL_map['query'] ) ? "?$URL_map[query]" : '';
        if (isset($URL_map['fragment']))
            $this->path .= '#'.$URL_map['fragment'];
        
        return true;
    }
}
/**
 * Represents a special "signed" URL used in authentication scenarios
 */
class Authentication_SignedURL extends Authentication_URL
{
    /**
     * Returns the digital signature string extracted from the signed URL
     * @return string
     */
    public function digitalSignature()
    {
		$data = $this->getQueryParameter('sig');
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    /**
     * Returns the original parsed URL without the digital signature
     * @return string
     */
    public function URLWithoutSignature()
    {
        $sig = $this->getQueryParameter('sig');
        // parsedUrl until &sig=[digital signature]
        
        $url_arr = explode("&sig=", $this->parsedURL);
        return $url_arr[0];
        // old and bad method
        //return substr($this->parsedURL, 0, -5-strlen(urlencode(isset($sig) ? $sig : NULL)));
    }
    /**
     * Parses the given URL string into a Authentication_SignedURL
     * @param string $URL_string
     * @return Authentication_SignedURL
     */
    public static function parse($URL_string)
    {
        $URL = new Authentication_SignedURL();
        $isOk = $URL->parseInternal($URL_string);
        return $isOk ? $URL : NULL;
    }
}

?>
