<?php

define('CLIENTAREA', true);

require __DIR__ . '/init.php';
require_once __DIR__ . '/modules/registrars/openprovider/init.php';
require_once __DIR__ . '/modules/registrars/openprovider/openprovider.php';

openprovider_registrar_launch_decorator('showDnssecPage', []);
