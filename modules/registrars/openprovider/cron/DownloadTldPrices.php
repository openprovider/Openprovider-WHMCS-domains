<?php

/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
include('BaseCron.php');
$core = openprovider_registrar_core('system');
$launch = $core->launch();
$core->launcher = openprovider_bind_required_classes($core->launcher);

openprovider_registrar_launch_decorator('DownloadTldPricesCron');
