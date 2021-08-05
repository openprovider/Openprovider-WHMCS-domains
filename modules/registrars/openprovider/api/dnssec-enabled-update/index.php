<?php

include ('../api.php');

$params = [
    'isDnssecEnabled' => $_GET['isDnssecEnabled'],
    'domainId'        => $_GET['domainId'],
];

openprovider_registrar_launch_decorator('updateDnsSecEnabledApi', $params);
