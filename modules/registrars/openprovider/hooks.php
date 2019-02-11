<?php
/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

require_once( __DIR__ . '/init.php');


use WeDevelopCoffee\wPower\Controllers\HooksDispatcher;
$dispatcher = wLaunch(HooksDispatcher::class);
$dispatcher->launch();

