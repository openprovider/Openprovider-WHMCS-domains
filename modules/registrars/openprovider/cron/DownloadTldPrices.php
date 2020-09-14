<?php
use OpenProvider\WhmcsRegistrar\src\DomainSync;
use OpenProvider\WhmcsHelpers\Activity;

/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

include('BaseCron.php');

openprovider_registrar_launch('system')
    ->output($params, 'DownloadTldPricesCron');
