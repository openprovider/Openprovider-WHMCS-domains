<?php

namespace OpenProvider\API;

/**
 * Class Request
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Request
{
    protected $cmd;
    protected $args;
    protected $username;
    protected $password;
    protected $client;

    public function __construct()
    {
        $this->client = \OpenProvider\API\APIConfig::$moduleVersion;
    }

    public function getRaw()
    {
        $dom = new \DOMDocument('1.0', \OpenProvider\API\APIConfig::$encoding);

        $credentialsElement = $dom->createElement('credentials');
        $usernameElement = $dom->createElement('username');
        $usernameElement->appendChild(
                $dom->createTextNode(mb_convert_encoding($this->username, \OpenProvider\API\APIConfig::$encoding))
        );
        $credentialsElement->appendChild($usernameElement);

        $passwordElement = $dom->createElement('password');
        $passwordElement->appendChild(
                $dom->createTextNode(mb_convert_encoding($this->password, \OpenProvider\API\APIConfig::$encoding))
        );
        $credentialsElement->appendChild($passwordElement);

        $clientElement = $dom->createElement('client');
        $clientElement->appendChild(
                $dom->createTextNode(mb_convert_encoding($this->client, \OpenProvider\API\APIConfig::$encoding))
        );
        $credentialsElement->appendChild($clientElement);

        $initiator = \OpenProvider\API\APIConfig::getInitiator();
        $clientElement = $dom->createElement('initiator');
        $clientElement->appendChild(
            $dom->createTextNode(mb_convert_encoding($initiator, \OpenProvider\API\APIConfig::$encoding))
        );
        $credentialsElement->appendChild($clientElement);

        $rootElement = $dom->createElement('openXML');
        $rootElement->appendChild($credentialsElement);

        $rootNode = $dom->appendChild($rootElement);
        $cmdNode = $rootNode->appendChild(
                $dom->createElement($this->getCommand())
        );

        \OpenProvider\API\APITools::convertPhpObjToDom($this->args, $cmdNode, $dom);

        return $dom->saveXML();
    }
    
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    public function setCommand($cmd)
    {
        $this->cmd = $cmd;
        return $this;
    }

    public function getCommand()
    {
        return $this->cmd;
    }

    public function setAuth($args)
    {
        $this->username = isset($args["username"]) ? $args["username"] : null;
        $this->password = isset($args["password"]) ? $args["password"] : null;

        return $this;
    }
}
