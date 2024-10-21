<?php

include ('../api.php');

$params = [
    'domainList'         => $_GET['domainList'],
    'clientId'           => $_GET['clientId'],
    'paymentMethod'      => $_GET['paymentMethod'],
    'registrar'          => $_GET['registrar'],
];

openprovider_registrar_launch_decorator('importDomainApi', $params);
