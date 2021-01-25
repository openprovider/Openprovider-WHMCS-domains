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
    private static $configFilePath = '/../configuration/advanced-module-configurations.php';
    private static $config = [];

    /**
     * init config
     */
    public static function init()
    {
        $configFileAbsolutePath = realpath(__DIR__ . self::$configFilePath);
        if (count(self::$config) == 0 && file_exists($configFileAbsolutePath))
            self::$config = include $configFileAbsolutePath;
    }

    /**
     * Return a value.
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        self::init();
        return self::$config[$key];
    }

    /**
     * Return configuration params Array
     *
     * @return array
     */
    public static function getParams()
    {
        self::init();
        return self::$config;
    }

    public static function getOrDefault($key, $defaultValue = false)
    {
        self::init();
        $value = self::$config[$key];
        if (!$value) {
            return $defaultValue;
        }

        return $value;
    }

    public static function getApiUrl($apiMethod)
    {
        return self::_getServerUrl() . "/modules/registrars/openprovider/api/{$apiMethod}";
    }

    public static function getJsModuleUrl($jsModuleName)
    {
        return self::_getServerUrl() . "/modules/registrars/openprovider/includes/templates/js/modules/{$jsModuleName}.js";
    }

    public static function getCssModuleUrl($cssModuleName)
    {
        return self::_getServerUrl() . "/modules/registrars/openprovider/includes/templates/css/modules/{$cssModuleName}.css";
    }

    private static function _getServerUrl()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER[HTTP_HOST]}"
            . "/whmcs";
    }
}