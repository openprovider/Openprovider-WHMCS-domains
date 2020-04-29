<?php
/**
 * wPower Boostrap
 *
 * @copyright Copyright (c) WeDevelopCoffee 2018
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ .'/init.php');

openprovider_registrar_launch()
    ->hooks();

$core = openprovider_registrar_core('admin');
$core->launch();



$activate = $core->launcher->get(\WeDevelopCoffee\wPower\Module\Setup::class);
$activate->enableFeature('handles');
$activate->addMigrationPath(__DIR__.'/migrations');
$activate->migrate();
