<?php

include ('../api.php');

$params = [
    'userid' => $_GET['userid'],
];

openprovider_registrar_launch_decorator('updateContactsTagApi', $params);
