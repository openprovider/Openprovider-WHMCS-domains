<?php
namespace OpenProvider\WhmcsRegistrar\src;

/**
 * Hardcoded configuration
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Configuration
{
    protected static $api_url = 'https://api.openprovider.eu/';
    protected static $api_url_cte = 'https://api.cte.openprovider.eu/';

    /**
     * Return a value.
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::$$key;
    }


}