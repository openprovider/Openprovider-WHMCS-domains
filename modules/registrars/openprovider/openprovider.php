<?php
/**
 * OpenProvider Registrar module
 * 
 * @copyright Copyright (c) Openprovider 2018
 */

use WeDevelopCoffee\wPower\Models\Domain;
use WHMCS\Domains\DomainLookup\ResultsList;

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

require_once( __DIR__ . '/init.php');

require_once __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'idna_convert.class.php';

/**
 * Autoload
 * @param type $class_name
 */

spl_autoload_register(function ($className) 
{
    $className  =   implode(DIRECTORY_SEPARATOR, explode('\\', $className));
    
    if(file_exists((__DIR__).DIRECTORY_SEPARATOR.$className.'.php'))
    {
        require_once (__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
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
    return openprovider_registrar_launch('system')
        ->output($params, 'config');
}


/**
 * Register the domain.
 *
 * @param type $params
 * @return type
 */
function openprovider_RegisterDomain($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'registerDomain');
}

/**
 * Transfer the domain.
 *
 * @param type $params
 * @return type
 */
function openprovider_TransferDomain($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'transferDomain');
}

/**
 * Get domain name servers.
 *
 * @param type $params
 * @return type
 */
function openprovider_GetNameservers($params) 
{
    return openprovider_registrar_launch('system')
        ->output($params, 'getNameservers');
}

/**
 * Change domain name servers.
 *
 * @param type $params
 * @return string
 */
function openprovider_SaveNameservers($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'saveNameservers');
}

/**
 * Get registrar lock.
 *
 * @param type $params
 * @return type
 */
function openprovider_GetRegistrarLock($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'getRegistrarLock');
}


/**
 * Save registrar lock.
 *
 * @param type $params
 * @return type
 */
function openprovider_SaveRegistrarLock($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'saveRegistrarLock');
}

/**
 * Get domain DNS.
 *
 * @param type $params
 * @return array
 */
function openprovider_GetDNS($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'getDns');
}

/**
 * Save domain DNS records.
 *
 * @param type $params
 * @return string
 */
function openprovider_SaveDNS($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'saveDns');
}

/**
 * Process the toggle
 *
 * @param type $params
 * @return array
 */
function openprovider_IDProtectToggle($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'idProtect');
}

/**
 * Request the deletion of the domain at the registrar.
 *
 * @param $params
 * @return mixed
 */
function openprovider_RequestDelete($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'requestDelete');
}

/**
 * Renew the domain.
 *
 * @param $params
 * @return array
 */
function openprovider_RenewDomain($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'renewDomain');
}


/**
 * Get domain contact details.
 * @param type $params
 * @return type
 */
function openprovider_GetContactDetails($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'getContactDetails');
}

/**
 * Save the contact details.
 * @param $params
 * @return mixed
 */
function openprovider_SaveContactDetails($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'saveContactDetails');
}

/**
 * Get domain epp code.
 * @param type $params
 * @return type
 */
function openprovider_GetEPPCode($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'getEppCode');
}


/**
 * Add name server in domain.
 * @param type $params
 * @return array|string
 */
function openprovider_RegisterNameserver($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'registerNameserver');
}


/**
 * Modify existing name servers.
 * @param array $params
 * @return array|string
 */
function openprovider_ModifyNameserver($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'modifyNameserver');
}

/**
 * Delete name server from domain.
 * @param type $params
 * @return array|string
 */
function openprovider_DeleteNameserver($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'deleteNameserver');
}

/**
 * Synchronize domain status and expiry date.
 * @param type $params
 * @return array
 */
function openprovider_TransferSync($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'transferSync');
}

/**
 * Mock a domain synchronisation.
 *
 * @param $params
 * @return array
 */
function openprovider_Sync($params)
{
    $domain = Domain::find($params['domainid']);
    return array(
        'expirydate' => $domain->expirydate, // Format: YYYY-MM-DD
        'active' => true, // Return true if the domain is active
        'expired' => false, // Return true if the domain has expired
        'transferredAway' => false, // Return true if the domain is transferred out
    );
}


/**
 * Check the domains availability.
 *
 * @param $params
 * @return mixed
 */
function openprovider_CheckAvailability($params)
{
    return openprovider_registrar_launch('system')
        ->output($params, 'checkAvailability');
}

/**
 * get Domain suggestions
 *
 * This is not available in OpenProvider yet.
 */
function openprovider_GetDomainSuggestions($params)
{
    $results = new ResultsList();

    return $results;
}