<?php
/**
 * wPower Boostrap
 *
 * @copyright Copyright (c) WeDevelopCoffee 2018
 */

use WeDevelopCoffee\wPower\Module\Setup;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ .'/init.php');

$core = openprovider_registrar_core('admin');

$core->launch()
    ->hooks();

$core->launcher = openprovider_bind_required_classes($core->launcher);

$activate = $core->launcher->get(Setup::class);
$activate->enableFeature('handles');
$activate->addMigrationPath(__DIR__.'/migrations');
$activate->migrate();
