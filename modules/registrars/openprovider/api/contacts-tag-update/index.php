<?php

include ('../api.php');

$params = [
    'userid' => $_GET['userid'],
];

openprovider_registrar_launch('system')
    ->output($params, 'updateContactsTagApi');
