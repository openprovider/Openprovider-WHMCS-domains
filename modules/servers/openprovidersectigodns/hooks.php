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

    $api = getApi($productRow->configoption1, $productRow->configoption2);

    if (is_null($api)) {
        return 'Something was wrong! Check your credentials and try again';
    }

    $domainRequest = $api->call('searchDomainRequest', [
        'fullName' => sprintf('%s.%s', $vars['sld'], $vars['tld'])
    ]);

    if ($domainRequest->getCode() != 0 || count($domainRequest->getData()['results']) == 0) {
        return 'This domain not found in Openprovider or something went wrong!';
    }
});
