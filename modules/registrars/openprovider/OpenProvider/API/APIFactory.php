<?php
namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\src\Configuration;

class APIFactory
{

    public static function initAPIV1()
    {
        return new APIV1();
    }

    public static function initAPIXML()
    {
        return new API();
    }

    public static function getAPI()
    {
        if (Configuration::get('contracts_api')) {
            return self::initAPIV1();
        }
        
        return self::initAPIXML();
    }
}
