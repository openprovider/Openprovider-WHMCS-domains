<?php

include ('../api.php');

$params = [
    'flags'    => $_GET['flags'],
    'alg'      => $_GET['alg'],
    'pubKey'   => $_GET['pubKey'],
    'domainId' => $_GET['domainId'],
    'action'   => $_GET['action'],
];

openprovider_registrar_launch('system')
    ->output($params, 'updateDnsSecRecordApi');

