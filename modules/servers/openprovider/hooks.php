<?php

use WHMCS\Database\Capsule;

include_once 'api.php';

add_hook('ShoppingCartValidateDomain', 1, function($vars) {
    if ($vars['domainoption'] != 'owndomain') {
        return;
    }

    $productId = $_REQUEST['pid'];
    if (!$productId) {
        return 'You have no product id in query parameters: "pid" does not exist!';
    }

    $productRow = Capsule::table('tblproducts')
        ->where('id', $productId)
        ->first();

    $username = $productRow->configoption1;
    $password = $productRow->configoption2;

    $api = getApi($username, $password);

    if (is_null($api)) {
        return 'Something was wrong! Check your credentials and try again';
    }

    $domainFullName = $vars['sld'] . $vars['tld'];

    $domainRequest = $api->call('searchDomainRequest', [
        'fullName' => $domainFullName
    ]);

    if ($domainRequest->getCode() != 0 || count($domainRequest->getData()['results']) == 0) {
        return 'This domain not found in Openprovider or something went wrong!';
    }
});
