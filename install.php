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
 
ini_set('memory_limit', '256M');
set_time_limit ( 0 );

// schema file to be used as source
$db_schema = 'dbschema.sql';

define('INCLUDE_CHECK',true);
include 'lib/functions.php';
include 'header.php';

function remove_comments(&$output)
{
   $lines = explode("\n", $output);
   $output = "";

   // try to keep mem. use down
   $linecount = count($lines);

   $in_comment = false;
   for($i = 0; $i < $linecount; $i++)
   {
      if( preg_match("/^\/\*/", preg_quote($lines[$i])) )
      {
         $in_comment = true;
      }

      if( !$in_comment )
      {
         $output .= $lines[$i] . "\n";
      }

      if( preg_match("/\*\/$/", preg_quote($lines[$i])) )
      {
         $in_comment = false;
      }
   }

   unset($lines);
   return $output;
}

//
// remove_remarks will strip the sql comment lines out of an uploaded sql file
//
function remove_remarks($sql)
{
   $lines = explode("\n", $sql);

   // try to keep mem. use down
   $sql = "";

   $linecount = count($lines);
   $output = "";

   for ($i = 0; $i < $linecount; $i++)
   {
      if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0))
      {
         if (isset($lines[$i][0]) && $lines[$i][0] != "#")
         {
            $output .= $lines[$i] . "\n";
         }
         else
         {
            $output .= "\n";
         }
         // Trading a bit of speed for lower mem. use here.
         $lines[$i] = "";
      }
   }

   return $output;

}

//
// split_sql_file will split an uploaded sql file into single sql statements.
// Note: expects trim() to have already been run on $sql.
//
function split_sql_file($sql, $delimiter)
{
   // Split up our string into "possible" SQL statements.
   $tokens = explode($delimiter, $sql);

   // try to save mem.
   $sql = "";
   $output = array();

   // we don't actually care about the matches preg gives us.
   $matches = array();

   // this is faster than calling count($oktens) every time thru the loop.
   $token_count = count($tokens);
   for ($i = 0; $i < $token_count; $i++)
   {
      // Don't wanna add an empty string as the last thing in the array.
      if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
      {
         // This is the total number of single quotes in the token.
         $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
         // Counts single quotes that are preceded by an odd number of backslashes,
         // which means they're escaped quotes.
         $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

         $unescaped_quotes = $total_quotes - $escaped_quotes;

         // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
         if (($unescaped_quotes % 2) == 0)
         {
            // It's a complete sql statement.
            $output[] = $tokens[$i];
            // save memory.
            $tokens[$i] = "";
         }
         else
         {
            // incomplete sql statement. keep adding tokens until we have a complete one.
            // $temp will hold what we have so far.
            $temp = $tokens[$i] . $delimiter;
            // save memory..
            $tokens[$i] = "";

            // Do we have a complete statement yet?
            $complete_stmt = false;

            for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
            {
               // This is the total number of single quotes in the token.
               $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
               // Counts single quotes that are preceded by an odd number of backslashes,
               // which means they're escaped quotes.
               $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

               $unescaped_quotes = $total_quotes - $escaped_quotes;

               if (($unescaped_quotes % 2) == 1)
               {
                  // odd number of unescaped quotes. In combination with the previous incomplete
                  // statement(s), we now have a complete statement. (2 odds always make an even)
                  $output[] = $temp . $tokens[$j];

                  // save memory.
                  $tokens[$j] = "";
                  $temp = "";

                  // exit the loop.
                  $complete_stmt = true;
                  // make sure the outer loop continues at the right point.
                  $i = $j;
               }
               else
               {
                  // even number of unescaped quotes. We still don't have a complete statement.
                  // (1 odd and 1 even always make an odd)
                  $temp .= $tokens[$j] . $delimiter;
                  // save memory.
                  $tokens[$j] = "";
               }

            } // for..
         } // else
      }
   }

   return $output;
}

$ret = '';
// Create database tables and write configuration to disk
if (isset($_REQUEST['submit'])) {

    /* Database config */
    $db_database	= trim($_REQUEST['database']); 
    $db_host		= trim($_REQUEST['host']);
    $db_user		= trim($_REQUEST['user']);
    $db_pass		= trim($_REQUEST['pass']);
    
    /* SMTP server config */
    $smtp_authentication = $_REQUEST['smtp_auth']; 
    $smtp_server		= trim($_REQUEST['smtp_server']);
    $smtp_username		= trim($_REQUEST['smtp_user']);
    $smtp_passpasswod	= trim($_REQUEST['smtp_pass']);

    // Establish db connection
    if (!mysql_connect($db_host,$db_user,$db_pass))
        $ret .= error('Unable to connect to database!'); 
    mysql_select_db($db_database);
    mysql_query("SET names UTF8");

    // proceed only if we have an empty database
    if (mysql_num_rows(mysql_query("SHOW TABLES FROM " . $db_database)) == 0) {
        // write configuration to config.php
        $cf = fopen('config.php', 'w') or die('Cannot create the config.php file!');

        $content = "<?php\n";
        $content .= "// ------------- AUTOGENERATED FILE ---------------- //\n";
        $content .= "if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');\n";
        $content .= "\n";
        $content .= "// ------------- USER STUFF ---------------- //\n";
        $content .= "/* Password for CA private key (used to generate client certs) */\n";
        $content .= '$CApass = \'' . trim($_REQUEST['capass']) . '\';' . "\n";
        $content .= "/* OpenSSL config file location */\n";
        $content .= '$SSLconf = \'' . trim($_REQUEST['openssl']) . '\';' . "\n";
        $content .= "\n";
        $content .= "/* IDP address */\n";
        $content .= '$idp = \'' . trim($_REQUEST['idp']) . '\';' . "\n";
        $content .= "\n";
        $content .= "/* SPARQL endpoint */\n";
        $content .= '$endpoint = \'' . trim($_REQUEST['endpoint']) . '\';' . "\n";
        $content .= "\n";
        $content .= "/* Database config */\n";
        $content .= '$db_database   = \'' . $db_database . '\';' . "\n";
        $content .= '$db_host       = \'' . $db_host . '\';' . "\n";
        $content .= '$db_user       = \'' . $db_user . '\';' . "\n";
        $content .= '$db_pass       = \'' . $db_pass . '\';' . "\n";
        $content .= "\n";
        $content .= "/* SMTP config */\n";
        $content .= '$smtp_authentication   = \'' . $smtp_authentication . '\';' . "\n";
        $content .= '$smtp_server       = \'' . $smtp_server . '\';' . "\n";
        $content .= '$smtp_username     = \'' . $smtp_user . '\';' . "\n";
        $content .= '$smtp_passwrod     = \'' . $smtp_pass . '\';' . "\n";
        $content .= "\n";
        $content .= "// Establish db connection\n";
        $content .= 'mysql_connect($db_host,$db_user,$db_pass) or die(\'Unable to establish a DB connection\');' . "\n";
        $content .= 'mysql_select_db($db_database);' . "\n";
        $content .= "mysql_query(\"SET names UTF8\");\n";
        $content .= "?>";

        fwrite($cf, $content);
        fclose($cf);
        $cf_status = "<p><font color=\"green\"><strong>Success!</strong></font> Configuration file has been saved to disk.</p>\n";
        
        // create cache dir if it doesn't exist
        if (!is_dir('cache/')) {
            if (!mkdir('cache/', 0775))
                die('Failed to create cache/ dir...');
            $cache_status = "<p><font color=\"green\"><strong>Success!</strong></font> Cache dir has been created.</p>\n";
        } else {
            $cache_status = "<p><strong>Skipped.</strong> Cache dir already exists.</p>\n";
        }
        
        // create logs dir if it doesn't exist
        if (!is_dir('logs/')) {
            if (!mkdir('logs/', 0775))
                die('Failed to create logs/ dir...');
            $logs_status = "<p><font color=\"green\"><strong>Success!</strong></font> Logs dir has been created.</p>\n";
        } else {
            $logs_status = "<p><strong>Skipped.</strong> Logs dir already exists.</p>\n";
        }
        
        // create dir where we store profiles if it doesn't exist
        if (!is_dir('people/')) {
            if (!mkdir('people/', 0775))
                die('Failed to create people/ dir...');
            $people_status = "<p><font color=\"green\"><strong>Success!</strong></font> Profile root dir has been created.</p>\n";
        } else {
            $people_status = "<p><strong>Skipped.</strong> Profile root dir already exists.</p>\n";
        }

        // create database tables
        $sql_query = @fread(@fopen($db_schema, 'r'), @filesize($db_schema)) or die('problem ');
        $sql_query = remove_remarks($sql_query);
        $sql_query = split_sql_file($sql_query, ';');

        foreach($sql_query as $sql){
            mysql_query($sql) or die('error in query');
        }
        $sql_status = "<p><font color=\"green\"><strong>Success!</strong></font> " . mysql_num_rows(mysql_query("SHOW TABLES FROM " . $db_database)) . " database tables have been created.</p>\n";
        
        // display success
        $ret .= "<p><font align=\"left\" style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">MyProfile Installation</font></p>\n";
        $ret .= success('Your installation is complete!');
        $ret .= "<br/><div>\n";
        $ret .= "<form action=\"index.php\" method=\"POST\">\n";
        $ret .= $sql_status;
        $ret .= $cf_status;
        $ret .= $cache_status;
        $ret .= $logs_status;
        $ret .= $people_status;
        $ret .= "<br/><p><input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Take me to the main page! \"></p>\n";
        $ret .= "</form></div>\n";
        
    } else {
        $ret .= error('Cannot perform installation on existing database. Please clear database!');
    }
} else {
    // display form        
    $ret .= "<div>\n";
    $ret .= "<p><font align=\"left\" style=\"font-size: 2em; text-shadow: 0 1px 1px #cccccc;\">MyProfile Installation</font></p>\n";
    
    $ret .= "<form action=\"\" method=\"POST\">\n";
    $ret .= "<p>Once the installation is complete, you can safely remove this file (<strong>install.php</strong>) \n";
    $ret .= "as well as the database schema file (<strong>dbschema.sql</strong>).</p><br/>\n";
    
    $ret .= "<table>\n";
    $ret .= "<tr><td colspan=\"2\"><p><strong>SSL configuration</strong></p><br/></td></tr>\n";
    $ret .= "<tr><td>CA key password: </td><td><input type=\"password\" name=\"capass\" value=\"\"> <small><font color=\"grey\">[needed for generating certificates]</font></small></td></tr>\n";
    $ret .= "<tr><td>OpenSSL config file: </td><td><input type=\"text\" name=\"openssl\" size=\"50\" placeholder=\"/etc/ssl/private/CA.key\" value=\"\"></td></tr>\n";

    $ret .= "<tr><td colspan=\"2\"><br/><p><strong>Delegated authentication</strong></p><br/></td></tr>\n";
    $ret .= "<tr><td>IdP address: </td><td><input type=\"text\" name=\"idp\" size=\"50\" value=\"https://auth.my-profile.eu/auth/index.php?authreqissuer=\"></td></tr>\n";
    $ret .= "<tr><td colspan=\"2\"><font color=\"grey\">Important note regarding using a different IdP: if you want to use a different IdP, you will have to edit the file <i>lib/libAuthentication/lib/Authentication_X509CertRepo.php</i> and add the IdP's certificate in (PEM form) to the array of IdPs.</font></td></tr>\n";

    $ret .= "<tr><td colspan=\"2\"><br/><p><strong>SPARQL endpoint</strong></p><br/></td></tr>\n";
    $ret .= "<tr><td>Endpoint address: </td><td><input type=\"text\" name=\"endpoint\" size=\"50\" value=\"\"></td></tr>\n";
    
    $ret .= "<tr><td colspan=\"2\"><br/><p><strong>Database configuration</strong></p><br/></td></tr>\n";
    $ret .= "<tr><td>Database host: </td><td><input type=\"text\" name=\"host\" value=\"localhost\"></td></tr>\n";
    $ret .= "<tr><td>Database name: </td><td><input type=\"text\" name=\"database\" value=\"\"></td></tr>\n";
    $ret .= "<tr><td>Database user: </td><td><input type=\"text\" name=\"user\" value=\"\"></td></tr>\n";
    $ret .= "<tr><td>Database pass: </td><td><input type=\"password\" name=\"pass\" value=\"\"></td></tr>\n";

    $ret .= "<tr><td colspan=\"2\"><br/><p><strong>Email server</strong></p><br/></td></tr>\n";
    $ret .= "<tr><td>Email server: </td><td><input type=\"text\" name=\"smtp_server\" value=\"\"></td></tr>\n";
    $ret .= "<tr><td colspan=\"2\">Does the server require authentication? <input type=\"checkbox\" name=\"smtp_auth\" value=\"\"></td></tr>\n";
    $ret .= "<tr><td>Email user: </td><td><input type=\"text\" name=\"smtp_user\" value=\"\"></td></tr>\n";
    $ret .= "<tr><td>Email pass: </td><td><input type=\"password\" name=\"smtp_pass\" value=\"\"></td></tr>\n";

    $ret .= "<tr><td colspan=\"2\"><p><input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\" Proceed to install \"></p></td></tr>\n";
    $ret .= "</table>\n";
    $ret .= "</form></div>\n";
}

echo $ret;
include 'footer.php';

?>
