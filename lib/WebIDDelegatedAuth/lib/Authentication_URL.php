<?php
/*-------------------------------------------------------------------------------------
 *
 * Filename   : Authentication_URL.php
 * Date       : 11th July 2012
 *
 * Copyright (C) 2012 Melvin Carvalho, Akbar Hossain, László Török
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is furnished 
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Everything should be made as simple as possible, but no simpler."
 * -- Albert Einstein
 */
//-------------------------------------------------------------------------------------

/**
 * Represents a valid Uniform Resource Locator
 *
 * @author László Török
 * @modified Andrei Sambra
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
     * @return Authentication_URL A valid Authentication_URL instance
     * (or NULL on error)
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

        if ( ! $URL_map
          || ! $URL_map['host']
                // some minimalistic sanitization
          || ! preg_match('/[a-zA-Z0-9._-]*[a-zA-Z0-9]$/', $URL_map['host']))
        {
            return false;
        }
        $URL_map = array_map('trim', $URL_map);

        $this->parsedURL = $URL_string;
        $this->scheme = isset($URL_map['scheme']) ? $URL_map['scheme'] : 'http';
        $this->host = $URL_map['host'];
        $this->port = isset($URL_map['port']) ? 
                    (int)$URL_map['port'] : ($this->scheme == 'https') ? 443 : 80;
        $this->path = isset($URL_map['path']) ? $URL_map['path'] : '';
        if (isset($URL_map['query']))
        {
            parse_str($URL_map['query'], $this->query);
        }
        if (!$this->query)
        {
            $this->query = array();
        }

        if ($this->path == '')
        {
            $this->path = '/';
        }

        $this->path .= isset($URL_map['query']) ? "?$URL_map[query]" : '';
        
        isset($URL_map['fragment']) and $this->path .= '#'.$URL_map['fragment'];
        
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
        return base64_decode(str_pad(strtr($data, '-_', '+/'), 
                            strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    /**
     * Returns the original parsed URL without the digital signature
     * @return string
     */
    public function URLWithoutSignature()
    {
        $sig = $this->getQueryParameter('sig');
        
        $encodedsig=urlencode(isset($sig) ? $sig : NULL);
        $encodedsig='&sig='.$encodedsig;
        $startofsig=strpos($this->parsedURL, $encodedsig);
	    $start=substr($this->parsedURL, 0, $startofsig);
        return $start;
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
