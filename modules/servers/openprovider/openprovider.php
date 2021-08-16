<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include_once 'api.php';

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */
function openprovider_MetaData()
{
    return array(
        'DisplayName' => 'Openprovider-premiumDNS',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}

function openprovider_ConfigOptions()
{
    return [
        // a text field type allows for single line text input
        'Login' => [
            'Type' => 'text',
            'Description' => 'Enter Openprovider login',
            'SimpleMode' => true,
        ],
        // a password field type allows for masked text input
        'Password' => [
            'Type' => 'password',
            'Description' => 'Enter Openprovider password',
            'SimpleMode' => true,
        ],
    ];
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function openprovider_CreateAccount(array $params)
{
    try {
        $username = $params['configoption1'];
        $password = $params['configoption2'];

        $api = getApi($username, $password);

        if (is_null($api)) {
            return 'provisioning module cannot configure api. Maybe your credentials are incorrect.';
        }

        $domainRequest = $api->call('searchDomainRequest', [
            'fullName' => $params['domain']
        ]);

        if ($domainRequest->getCode() != 0 || count($domainRequest->getData()['results']) == 0) {
            return 'This domain not found in Openprovider or something went wrong!';
        }

        $domain = $domainRequest->getData()['results'][0];

        $modifyDomainRequest = $api->call('modifyDomainRequest', [
            'id' => $domain['id'],
            'isSectigoDnsEnabled' => true,
        ]);

        if ($modifyDomainRequest->getCode() != 0) {
            return $modifyDomainRequest->getMessage();
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'provisioningmodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}
