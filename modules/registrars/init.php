<?php
// Require any libraries needed for the module to function.
use WeDevelopCoffee\wPower\Core\Core;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

/**
 * Configure and launch the system
 */
function openprovider_registrar_launch($level = 'hooks')
{
    $core = openprovider_registrar_core($level);
    return $core->launch();
}

/**
 * Configure and launch the system
 */
function openprovider_registrar_core($level = 'hooks')
{
    $core = new Core();

    $core->setModuleName('openprovider');
    $core->setModuleType('registrar');
    $core->setNamespace('\OpenProvider\WhmcsRegistrar');
    $core->setLevel($level);
    return $core;
}

