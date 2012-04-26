<?php

//-----------------------------------------------------------------------------------------------------------------------------------
//
// Filename   : Authentication_AgentAbstract.php
// Date       : 14th Feb 2010
//
// See Also   : https://foaf.me/testLibAuthentication.php
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

require_once(dirname(__FILE__)."/Authentication_Helper.php");
/**
 * @author Akbar Hossain
 * @abstract
 * Abstact Foaf parser
 * It takes URI of an user/agent and looks up properties (e.g. public key)
 * of the corresponding Foaf profile.
 */
abstract class Authentication_AgentAbstract {

    /**
     * Contains the error message of the last operation.
     * @var string
     */
    public $errors   = NULL;
    public $agentURI = NULL;
    public $agentId  = NULL;
    private $agent    = NULL;

    public function __construct($agentURI = NULL) {

        $this->setAgent($agentURI);
    }
    /**
     * Returns the parsed agent instance.
     * @return mixed
     */
    public function getAgent() {
        return $this->agent;
    }
    /**
     * Set URI of the agent (that is, the URI of the agent's Foaf profile)
     * @param string $agentURI
     * @return Boolean True if success, False on Error
     */
    public function setAgent($agentURI) {

        if (isset($agentURI)) {
            $this->agentURI = $agentURI;
            $this->errors = NULL;

            if (Authentication_Helper::isValidURL($agentURI)) {
                $this->loadAgent();
                $this->loadErrors();
                if (!isset($this->errors)) {
                    // TODO !!!! Undefined method !!!
                    $this->agentId = $this->getAgentId();
                    $this->agent = $this->getAgentProperties();
                }
            }
            else {
                $this->errors = "Invalid foaf file supplied";
                return(FALSE);
            }
        }
        else
        {
            $this->errors = "No foaf file supplied";
            return FALSE;
        }

        return TRUE;
    }

    protected abstract function loadAgent();

    protected abstract function loadErrors();

    protected abstract function getAgentProperties();

    protected abstract function getAgentId();

}

?>
