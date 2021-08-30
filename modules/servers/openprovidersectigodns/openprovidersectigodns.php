<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include_once 'api.php';
include_once 'functions.php';

const CREATE_DNS_ZONE_TYPE = 'master';

const SUCCESS_MESSAGE = 'success';

const MODULE_NAME = 'Openprovider-premiumDNS';
const API_VERSION = '1.1'; // Use API Version 1.1
const REQUIRES_SERVER = true; // Set true if module requires a server to work
const DEFAULT_NON_SSL_PORT = '1111'; // Default Non-SSL Connection Port
const DEFAULT_SSL_PORT = '1112'; // Default SSL Connection Port
const SERVICE_SINGLE_SIGN_ON_LABEL = 'Login to Panel as User';
const ADMIN_SINGLE_SIGN_ON_LABEL = 'Login to Panel as Admin';

const CONFIG_OPTION_LOGIN_NAME = 'Login';
const CONFIG_OPTION_LOGIN_DESCRIPTION = 'Enter Openprovider login';

const CONFIG_OPTION_PASSWORD_NAME = 'Password';
const CONFIG_OPTION_PASSWORD_DESCRIPTION = 'Enter Openprovider password';

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
function openprovidersectigodns_MetaData()
{
    return array(
        'DisplayName' => MODULE_NAME,
        'APIVersion' => API_VERSION,
        'RequiresServer' => REQUIRES_SERVER,
        'DefaultNonSSLPort' => DEFAULT_NON_SSL_PORT,
        'DefaultSSLPort' => DEFAULT_SSL_PORT,
        'ServiceSingleSignOnLabel' => SERVICE_SINGLE_SIGN_ON_LABEL,
        'AdminSingleSignOnLabel' => ADMIN_SINGLE_SIGN_ON_LABEL,
    );
}

function openprovidersectigodns_ConfigOptions()
{
    return [
        // a text field type allows for single line text input
        CONFIG_OPTION_LOGIN_NAME => [
            'Type' => 'text',
            'Description' => CONFIG_OPTION_LOGIN_DESCRIPTION,
            'SimpleMode' => true,
        ],
        // a password field type allows for masked text input
        CONFIG_OPTION_PASSWORD_NAME => [
            'Type' => 'password',
            'Description' => CONFIG_OPTION_PASSWORD_DESCRIPTION,
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
function openprovidersectigodns_CreateAccount(array $params)
{
    $api = getApi($params['configoption1'], $params['configoption2']);

    if (is_null($api)) {
        return 'provisioning module cannot configure api. Maybe your credentials are incorrect.';
    }

    // get dns zone if exist
    $dnsZoneResponse = $api->call('retrieveZoneDnsRequest', [
        'name' => $params['domain'],
    ]);

    // if zone exists
    if ($dnsZoneResponse->getCode() == 0) {
        $modifyZoneResponse = $api->call('modifyZoneDnsRequest', [
            'name' => $params['domain'],
            'premiumDNS' => true,
        ]);

        if ($modifyZoneResponse->getCode() != 0) {
            return $modifyZoneResponse->getMessage();
        }

        return SUCCESS_MESSAGE;
    }

    // if zone does not exist
    try {
        $domainArray = getDomainArrayFromDomain($params['domain']);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    $createDnsZoneResponse = $api->call('createZoneDnsRequest', [
        'domain' => $domainArray,
        'records' => [],
        'type' => CREATE_DNS_ZONE_TYPE,
        'premiumDNS' => true,
    ]);

    if ($createDnsZoneResponse->getCode() != 0) {
        return $createDnsZoneResponse->getMessage();
    }

    return SUCCESS_MESSAGE;
}
