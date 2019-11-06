<?php
// Require any libraries needed for the module to function.
use WeDevelopCoffee\wPower\Core\Core;

require_once __DIR__ . '/vendor/autoload.php';;

/**
 * Configure and launch the system
 */
function openprovider_addon_launch($level = 'hooks')
{
    $core = openprovider_addon_core($level);
    return $core->launch();
}

/**
 * Configure and launch the system
 */
function openprovider_addon_core($level = 'hooks')
{
    $core = new Core();

    $core->setModuleName('openprovider');
    $core->setModuleType('addon');
    $core->setNamespace('\OpenProvider\WhmcsDomainAddon');
    $core->setLevel($level);
    return $core;
}

