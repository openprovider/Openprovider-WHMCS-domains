<?php
/**
 * Domain abuse monitor module
 *
 * @copyright Copyright (c) WeDevelopCoffee 2020
 */

require_once('BaseCron.php');

openprovider_addon_launch('system')
    ->output($params, 'Synchronise');
