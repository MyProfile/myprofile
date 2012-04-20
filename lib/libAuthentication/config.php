<?php

//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : config.php                                                                                                  
// Date       : 15th October 2009
// Version    : 0.1
//
// Copyright 2008-2009 foaf.me
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

$config = array(

        /* db */
        'db_name'        => 'foaf',                     // db name
        'db_user'        => 'foaf',                          // db username
        'db_pwd'         => 'FmLxzTcy9thQRB67',                              // db password

        /* store */
        'store_name'     => 'arc_tests',                     // tmp table name

        /* modes */
        'multi_user'     => true,                            // not yet impl
        'auto_generate'  => true,                            // not yet impl
        'federation_uri' => '',                              // not yet impl
        'certficate_uri' => 'https://foaf.me/keygen.php'     // not yet impl

);

?>
