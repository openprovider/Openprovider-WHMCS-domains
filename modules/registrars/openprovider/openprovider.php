<?php

/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

use OpenProvider\WhmcsRegistrar\src\Configuration;
use WHMCS\Exception\Module\InvalidConfiguration;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/init.php');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'idna_convert.class.php';

/**
 * Autoload
 * @param type $class_name
 */

spl_autoload_register(function ($className) {
    $className  =   implode(DIRECTORY_SEPARATOR, explode('\\', $className));

    if (file_exists((__DIR__) . DIRECTORY_SEPARATOR . $className . '.php')) {
        require_once (__DIR__) . DIRECTORY_SEPARATOR . $className . '.php';
    }
});

/**
 * Get the configuration.
 *
 * @param array $params
 * @return mixed
 */
function openprovider_getConfigArray($params = array())
{
    return openprovider_registrar_launch_decorator('config', $params);
}


/**
 * Register the domain.
 *
 * @param type $params
 * @return type
 */
function openprovider_RegisterDomain($params)
{
    return openprovider_registrar_launch_decorator('registerDomain', $params);
}

/**
 * Add extra buttons to admin registrar commands (Sync).
 *
 * @param type $params
 * @return type
 */
function openprovider_AdminCustomButtonArray()
{
    $buttonarray = array(
        "Auto-renew Sync" => "AutoRenewSync",
        "Sync" => "Sync",
        'Manage DNS Zone' => 'AdminManageDnsZone',
    );

    return $buttonarray;
}

/**
 * Transfer the domain.
 *
 * @param type $params
 * @return type
 */
function openprovider_TransferDomain($params)
{
    return openprovider_registrar_launch_decorator('transferDomain', $params);
}

/**
 * Get domain information (WHMCS > v7.6).
 *
 * @param type $params
 * @return type
 */
function openprovider_GetDomainInformation($params)
{
    return openprovider_registrar_launch_decorator('GetDomainInformation', $params);
}

/**
 * Get domain name servers.
 *
 * @param type $params
 * @return type
 */
function openprovider_GetNameservers($params)
{
    return openprovider_registrar_launch_decorator('getNameservers', $params);
}

/**
 * Change domain name servers.
 *
 * @param type $params
 * @return string
 */
function openprovider_SaveNameservers($params)
{
    return openprovider_registrar_launch_decorator('saveNameservers', $params);
}

/**
 * Get registrar lock.
 *
 * @param type $params
 * @return type
 */
function openprovider_GetRegistrarLock($params)
{
    return openprovider_registrar_launch_decorator('getRegistrarLock', $params);
}


/**
 * Save registrar lock.
 *
 * @param type $params
 * @return type
 */
function openprovider_SaveRegistrarLock($params)
{
    return openprovider_registrar_launch_decorator('saveRegistrarLock', $params);
}

/**
 * Get domain DNS.
 *
 * @param type $params
 * @return array
 */
function openprovider_GetDNS($params)
{
    return openprovider_registrar_launch_decorator('getDns', $params);
}

/**
 * Save domain DNS records.
 *
 * @param type $params
 * @return string
 */
function openprovider_SaveDNS($params)
{
    return openprovider_registrar_launch_decorator('saveDns', $params);
}

/**
 * Process the toggle
 *
 * @param type $params
 * @return array
 */
function openprovider_IDProtectToggle($params)
{
    return openprovider_registrar_launch_decorator('idProtect', $params);
}

/**
 * Request the deletion of the domain at the registrar.
 *
 * @param $params
 * @return mixed
 */
function openprovider_RequestDelete($params)
{
    return openprovider_registrar_launch_decorator('requestDelete', $params);
}

/**
 * Renew the domain.
 *
 * @param $params
 * @return array
 */
function openprovider_RenewDomain($params)
{
    return openprovider_registrar_launch_decorator('renewDomain', $params);
}


/**
 * Get domain contact details.
 * @param type $params
 * @return type
 */
function openprovider_GetContactDetails($params)
{
    return openprovider_registrar_launch_decorator('getContactDetails', $params);
}

/**
 * Save the contact details.
 * @param $params
 * @return mixed
 */
function openprovider_SaveContactDetails($params)
{
    return openprovider_registrar_launch_decorator('saveContactDetails', $params);
}

/**
 * Get domain epp code.
 * @param type $params
 * @return type
 */
function openprovider_GetEPPCode($params)
{
    return openprovider_registrar_launch_decorator('getEppCode', $params);
}


/**
 * Add name server in domain.
 * @param type $params
 * @return array|string
 */
function openprovider_RegisterNameserver($params)
{
    return openprovider_registrar_launch_decorator('registerNameserver', $params);
}


/**
 * Modify existing name servers.
 * @param array $params
 * @return array|string
 */
function openprovider_ModifyNameserver($params)
{
    return openprovider_registrar_launch_decorator('modifyNameserver', $params);
}

/**
 * Delete name server from domain.
 * @param type $params
 * @return array|string
 */
function openprovider_DeleteNameserver($params)
{
    return openprovider_registrar_launch_decorator('deleteNameserver', $params);
}

/**
 * Synchronize domain status and expiry date.
 * @param type $params
 * @return array
 */
function openprovider_TransferSync($params)
{
    return openprovider_registrar_launch_decorator('transferSync', $params);
}

/**
 * Mock a domain AutoRenew synchronisation.
 *
 * @param $params
 * @return array
 */
function openprovider_AutoRenewSync($params)
{
    return openprovider_registrar_launch_decorator('domainSyncAutoRenew', $params);
}

/**
 * Domain synchronisation.
 *
 * @param $params
 * @return array
 */
function openprovider_Sync($paramsArray)
{
    return openprovider_registrar_launch_decorator('domainSync', $paramsArray);
}

/**
 * Get the TLD pricing.
 *
 * @param array $params
 * @return mixed
 */
function openprovider_GetTldPricing(array $params)
{
    return openprovider_registrar_launch_decorator('getTldPricing', $params);
}

/**
 * Check the domains availability.
 *
 * @param $params
 * @return mixed
 */
function openprovider_CheckAvailability($params)
{
    return openprovider_registrar_launch_decorator('checkAvailability', $params);
}

/**
 * get Domain suggestions
 *
 * @param $params
 * @return mixed
 */
function openprovider_GetDomainSuggestions($params)
{
    return openprovider_registrar_launch_decorator('getDomainSuggestions', $params);
}

/**
 * Domain Suggestion Options
 *
 * @param $params
 * @return mixed
 */
function openprovider_DomainSuggestionOptions($params)
{
    return openprovider_registrar_launch_decorator('getDomainSuggestionOptions', $params);
}

/**
 * Resend IRTP Verification Email
 *
 * @param array $params
 * @return mixed
 */
function openprovider_ResendIRTPVerificationEmail(array $params)
{
    // Perform API call to initiate resending of the IRTP Verification Email
    return openprovider_registrar_launch_decorator('resendIRTPVerificationEmail', $params);
}

/**
 * Admin Manage Dns Zone
 *
 * @param array $params
 * @return mixed
 */
function openprovider_AdminManageDnsZone($params)
{
    return openprovider_registrar_launch_decorator('adminManageDnsZone', $params);
}

function openprovider_config_validate($params)
{
    $username = $params['Username'];
    $password = $params['Password'];
    $testMode = $params['test_mode'];
    $resourcePath = '/v1beta/auth/login'; //Resource path to login API

    $baseUrl = Configuration::get('api_url');
    $env = 'Production';

    if ($testMode == 'on') {
        $env = 'Sandbox';
        $baseUrl = Configuration::get('restapi_url_sandbox');
    }

    $url = "{$baseUrl}{$resourcePath}";

    $data = array(
        "username" => $username,
        "password" => $password,
        "ip" => "0.0.0.0",
    );

    $encodedData = json_encode($data);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
    $result = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($result);
    if (!$response->data->token || !$response->data->reseller_id) {
        throw new InvalidConfiguration("Credentials are Invalid for $env Environment");
    }
}

/**
 * Decorator for merge configuration params with static params from
 * \OpenProvider\WhmcsRegistrar\src\Configuration class
 *
 * @param string $route
 * @param array $params
 * @param string $level
 * @return mixed
 */
function openprovider_registrar_launch_decorator(string $route, $params = [], $level = 'system')
{
    $modifiedParams = array_merge($params, Configuration::getParams());
    $modifiedParams['original'] = Configuration::getParams();
    if (isset($params['original'])) {
        $modifiedParams['original'] = array_merge($params['original'], Configuration::getParams());
    }

    $core = openprovider_registrar_core($level);
    $launch = $core->launch();

    $core->launcher = openprovider_bind_required_classes($core->launcher);

    return $launch->output($modifiedParams, $route);
}
