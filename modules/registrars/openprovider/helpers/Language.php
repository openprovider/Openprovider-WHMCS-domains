<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use WHMCS\Config\Setting;

class Language
{
    public static function loadLang(): array
    {
        $lang = strtolower($_SESSION['Language'] ?? Setting::getValue('Language'));
        $lang = preg_replace('/[^a-z-]/', '', $lang);
        $_ADDONLANG = [];

        $basePath = ROOTDIR . '/modules/registrars/openprovider/lang/';
        $overridePath = $basePath . 'overrides/';
        if (file_exists($basePath . 'english.php'))
        {
            // Always load the base language file.
            require($basePath . 'english.php');
        }
        if ($lang !== 'english')
        {
            // Load the language file for the selected language.
            if (file_exists($basePath . $lang . '.php')) {
                require($basePath . $lang . '.php');
            }
        }
        if (file_exists($overridePath . $lang . '.php'))
        {
            // Load the override file for the selected language.
            require($overridePath . $lang . '.php');
        }

        return $_ADDONLANG;
    }
}