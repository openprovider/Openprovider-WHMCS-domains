<?php
/**
 * OpenProvider domain addon.
 * 
 * @copyright Copyright (c) WeDevelopCoffee 2018
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__.'/init.php');


use WeDevelopCoffee\wPower\Controllers\HooksDispatcher;
$dispatcher = wLaunch(HooksDispatcher::class);
$dispatcher->dispatch();