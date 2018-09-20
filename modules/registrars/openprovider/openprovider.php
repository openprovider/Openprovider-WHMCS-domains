<?php
/**
 * OpenProvider Registrar module
 * 
 * @copyright Copyright (c) Openprovider 2018
 */

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

require_once( __DIR__ . '/init.php');

use WeDevelopCoffee\wPower\Models\Domain;
use Carbon\carbon;
use WHMCS\Database\Capsule;
use OpenProvider\WhmcsRegistrar\Library\Notification;
use OpenProvider\WhmcsRegistrar\Library\Handle;
use OpenProvider\WhmcsRegistrar\Library\OpenProvider as OP;
use OpenProvider\WhmcsHelpers\Registrar;
use WeDevelopCoffee\wPower\Core\Activate;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use OpenProvider\WhmcsRegistrar\Controllers\Registrar\DomainController;

require_once __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'idna_convert.class.php';

/**
 * Autolaod
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

// Init the schemes
OpenProvider\WhmcsHelpers\Schemes\DomainSyncScheme::up('openprovider');


function openprovider_getConfigArray($params = array())
{
    $configarray = array
    (
        "OpenproviderAPI"   => array
        (
            "FriendlyName"  => "OpenProvider URL",
            "Type"          => "text", 
            "Size"          => "60", 
            "Description"   => "Include https://",
            "Default"       => "https://"
        ),
        "OpenproviderPremium"   => array
        (
            "FriendlyName"  => "Support premium domains",
            "Description"   => "Yes <i>NOTE: Premium pricing must also be activated in WHMCS via Setup -> Products / Services -> Domain pricing</i>. <br><br><strong>WARNING</strong>: to prevent billing problems with premium domains, your WHMCS currency must be the same as the currency you use in Openprovider. Otherwise, you will be billed the premium fee but your client will be billed the non-premium fee due to a <a href=\"https://requests.whmcs.com/topic/major-bug-premium-domains-billed-incorrectly\" target=\"_blank\">bug in WHMCS.</a>",
            "Type"          => "yesno"
        ),
        "Username"          => array
        (
            "FriendlyName"  => "Username",
            "Type"          => "text", 
            "Size"          => "20", 
            "Description"   => "Openprovider login",
        ),
        "Password"          => array
        (
            "FriendlyName"  => "Password",
            "Type"          => "password", 
            "Size"          => "20", 
            "Description"   => "Openprovider password",
        ),
        "updateNextDueDate" => array
        (
            "FriendlyName"  => "Synchronize due-date with offset?",
            "Type"          => "yesno",
            "Description"   => "WHMCS due dates will be synchronized using the due-date offset ",
        ),
        "nextDueDateOffset" => array
        (
            "FriendlyName"  => "Due-date offset",
            "Type"          => "text",
            "Size"          => "2",
            "Description"   => "Number of days to set the WHMCS due date before the Openprovider expiration date",
            "Default"       => "3"
        ),
        "updateInterval"     => array
        (
            "FriendlyName"  => "Update interval",
            "Type"          => "text",
            "Size"          => "2",
            "Description"   => "The minimum number of hours between each domain synchronization",
            "Default"       => "2"
        ),
        "domainProcessingLimit"     => array
        (
            "FriendlyName"  => "Domain process limit",
            "Type"          => "text",
            "Size"          => "4",
            "Description"   => "Maximum number of domains processed each time domain sync runs",
            "Default"       => "200"
        ),
        "sendEmptyActivityEmail" => array
        (
            "FriendlyName"  => "Send empty activity reports?",
            "Type"          => "yesno",
            "Size"          => "20",
            "Description"   => "Receive emails from domain sync even if no domains were updated",
            "Default"       => "no"
        ),
    );
    
    $x = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
    $filename = end($x);
    if(isset($_REQUEST) && $_REQUEST['action'] == 'save' && $filename == 'configregistrars.php')
    {
        $activate = wLaunch(Activate::class);
        $activate->enableFeature('handles');
        $activate->addMigrationPath(__DIR__.'/migrations');
        $activate->migrate();

        foreach($_REQUEST as $key => $val)
        {
            if(isset($configarray[$key]))
            {
                // Prevent that we will overwrite the actual password with the stars.
                if(substr($val, 0, 3) != '***')
                    $params[$key]   =   $val;
            }
        }
    }

    if(isset($params['Password']) && isset($params['Username']) && isset($params['OpenproviderAPI']))
    {
        try
        { 
            $api                =   new \OpenProvider\API\API($params);
            $templates          =   $api->searchTemplateDnsRequest();
            
            if(isset($templates['total']) && $templates['total'] > 0)
            {
                $tpls   =   'None,';
                foreach($templates['results'] as $template)
                {
                    $tpls .= $template['name'].',';
                }
                $tpls = trim($tpls,',');
                
                $configarray['dnsTemplate']  =   array 
                (
                    "FriendlyName"  =>  "DNS Template",
                    "Type"          =>  "dropdown",
                    "Description"   =>  "DNS template will be used when a domain is created or transferred to your account",
                    "Options"       =>  $tpls
                );
            }
        } 
        catch (Exception $ex) 
        {
            //do nothing
        }
    }
    
    return $configarray;
}


/**
 * 
 * @param type $params
 * @return type
 */
function openprovider_RegisterDomain($params)
{
    $domainController = wLaunch(DomainController::class);
    return $domainController->register($params);
}

/**
 * Get domain name servers
 * @param type $params
 * @return type
 */
function openprovider_GetNameservers($params) 
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        $nameservers        =   $api->getNameservers($domain);
        $return             =   array();
        $i                  =   1;
        
        foreach($nameservers as $ns)
        {
            $return['ns'.$i]    =   $ns;
            $i++;
        }
        
        return $return;
    }
    catch (\Exception $e)
    {
        return array
        (
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Change domain name servers
 * @param type $params
 * @return string
 */
function openprovider_SaveNameservers($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        $nameServers        =   \OpenProvider\API\APITools::createNameserversArray($params);
        
        $api->saveNameservers($domain, $nameServers);
    }
    catch (\Exception $e)
    {
        return array(
            'error' => $e->getMessage(),
        );
    }
    
    return 'success';
}

/**
 * Get registrar lock
 * @param type $params
 * @return type
 */
function openprovider_GetRegistrarLock($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $lockStatus         =   $api->getRegistrarLock($domain);
    }
    catch (\Exception $e)
    {
        //Nothing...
    }

    return $lockStatus ? 'locked' : 'unlocked';;
}


/**
 * Save registrar lock
 * @param type $params
 * @return type
 */
function openprovider_SaveRegistrarLock($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    $values = array();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        $lockStatus         =   $params["lockenabled"] == "locked" ? 1 : 0;

        $api->saveRegistrarLock($domain, $lockStatus);
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    return $values;
}

/**
 * Get domain DNS
 * @param type $params
 * @return array
 */
function openprovider_GetDNS($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    $dnsRecordsArr = array();
    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        $dnsInfo            =   $api->getDNS($domain);

        if (is_null($dnsInfo))
        {
            return array();
        }

        $supportedDnsTypes  =   \OpenProvider\API\APIConfig::$supportedDnsTypes;
        $domainName         =   $domain->getFullName();
        foreach ($dnsInfo['records'] as $dnsRecord)
        {
            if (!in_array($dnsRecord['type'], $supportedDnsTypes))
            {
                continue;
            }

            $hostname = $dnsRecord['name'];
            if ($hostname == $domainName)
            {
                $hostname = '';
            }
            else
            {
                $pos = stripos($hostname, '.' . $domainName);
                if ($pos !== false)
                {
                    $hostname = substr($hostname, 0, $pos);
                }
            }
            $prio = is_numeric($dnsRecord['prio']) ? $dnsRecord['prio'] : '';
            $dnsRecordsArr[] = array(
                'hostname' => $hostname,
                'type' => $dnsRecord['type'],
                'address' => $dnsRecord['value'],
                'priority' => $prio
            );
        }
    }
    catch (\Exception $e)
    {
    }
    
    return $dnsRecordsArr;
}

/**
 * Save domain DNS records
 * @param type $params
 * @return string
 */
function openprovider_SaveDNS($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    $dnsRecordsArr = array();
    $values = array();
    foreach ($params['dnsrecords'] as $tmpDnsRecord)
    {
        if (!$tmpDnsRecord['hostname'] && !$tmpDnsRecord['address'])
        {
            continue;
        }
        
        $dnsRecord          =   new \OpenProvider\API\DNSrecord();
        $dnsRecord->type    =   $tmpDnsRecord['type'];
        $dnsRecord->name    =   $tmpDnsRecord['hostname'];
        $dnsRecord->value   =   $tmpDnsRecord['address'];
        $dnsRecord->ttl     =   \OpenProvider\API\APIConfig::$dnsRecordTtl;

        if ('MX' == $dnsRecord->type) // priority - required for MX records; ignored for all other record types
        {
            if (is_numeric($tmpDnsRecord['priority']))
            {
                $dnsRecord->prio    =   $tmpDnsRecord['priority'];
            }
            else
            {
                $dnsRecord->prio    =   \OpenProvider\API\APIConfig::$dnsRecordPriority;
            }
        }
        
        if (!$dnsRecord->value)
        {
            continue;
        }
        
        if (in_array($dnsRecord, $dnsRecordsArr))
        {
            continue;
        }

        $dnsRecordsArr[] = $dnsRecord;
    }

    $domain = new \OpenProvider\API\Domain();
    $domain->name = $params['sld'];
    $domain->extension = $params['tld'];

    try
    {
        $api = new \OpenProvider\API\API($params);
        if (count($dnsRecordsArr))
        {
            $api->saveDNS($domain, $dnsRecordsArr);
        }
        else
        {
            $api->deleteDNS($domain);
        }
        
        return "success";
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    
    return $values;
}

/**
 * Process the toggle
 *
 * @param type $params
 * @return array
 */
function openprovider_IDProtectToggle($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();
    $params['domainname'] = $params['sld'] . '.' . $params['tld'];

    // Get the domain details
    $domain = Capsule::table('tbldomains')
        ->where('id', $params['domainid'])
        ->get()[0];

    try {
        $OpenProvider       = new OP();
        $op_domain_obj      = new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $op_domain          = $OpenProvider->api->retrieveDomainRequest($op_domain_obj);
        $OpenProvider->toggle_whois_protection($domain, $op_domain_obj, $op_domain);

        return array(
            'success' => 'success',
        );
    } catch (Exception $e) {
        \logModuleCall('OpenProvider', 'Save identity toggle',$params['domainname'], [$OpenProvider->domain, @$op_domain, $OpenProvider], $e->getMessage(), [$params['Password']]);
        
        if($e->getMessage() == 'Wpp contract is not signed')
        {
            $notification = new Notification();
            $notification->WPP_contract_unsigned_one_domain($params['domainname'])
                ->send_to_admins();

        }

        return array(
            'error' => $e->getMessage(),
        );
    }
}

//
function openprovider_RequestDelete($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();
    $values = array();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        
        $api->requestDelete($domain);
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    
    return $values;
}

/**
 * 
 * @param type $params
 * @return type
 */
function openprovider_TransferDomain($params)
{
    $domainController = wLaunch(DomainController::class);
    return $domainController->transfer($params);
}

//
function openprovider_RenewDomain($params)
{
    // Prepare the renewal
    $domain = new \OpenProvider\API\Domain(array(
        'name' => $params['original']['domainObj']->getSecondLevel(),
        'extension' => $params['original']['domainObj']->getTopLevel()
    ));


    $period = $params['regperiod'];

    $api = new \OpenProvider\API\API($params);

    // If isInGracePeriod is true, renew the domain.
    if(isset($params['isInGracePeriod']) && $params['isInGracePeriod'] == true)
    {
        try
        {
            $api->restoreDomain($domain, $period);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return [];
    }

    // If isInRedemptionGracePeriod is true, restore the domain.
    if(isset($params['isInRedemptionGracePeriod']) && $params['isInRedemptionGracePeriod'] == true)
    {
        try
        {
            $api->restoreDomain($domain, $period);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return [];
    }

    // We did not have a true isInRedemptionGracePeriod or isInGracePeriod. Fall back on the legacy code
    // for older WHMCS versions.

    try
    {
        if(!$api->getSoftRenewalExpiryDate($domain)) {
            $api->renewDomain($domain, $period);
        } elseif ((new Carbon($api->getSoftRenewalExpiryDate($domain), 'CEST'))->gt(Carbon::now())) {
            $api->restoreDomain($domain, $period);
        } else {
            // This only happens when the isInRedemptionGracePeriod was not true.
            throw new Exception("Domain has expired and additional costs may be applied. Please check the domain in your reseller control panel", 1);
        }

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }

    return [];
}


/**
 * Get domain contact details
 * @param type $params
 * @return type
 */
function openprovider_GetContactDetails($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $api                =   new \OpenProvider\API\API($params);
        $values             =   $api->getContactDetails($domain);
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    
    return $values;
}

//
function openprovider_SaveContactDetails($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $handles            =   array_flip(\OpenProvider\API\APIConfig::$handlesNames);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        
        $handle = wLaunch(Handle::class);
        $handle->setApi($api);
        
        $customers['ownerHandle']   = $handle->updateOrCreate($params, 'registrant');
        $customers['adminHandle']   = $handle->updateOrCreate($params, 'admin');
        $customers['techHandle']    = $handle->updateOrCreate($params, 'tech');
        
        if(isset($params['contactdetails']['Billing']))
            $customers['billingHandle'] = $handle->updateOrCreate($params, 'billing');

        $finalCustomers = [];

        // clean out the empty results
        array_walk($customers, function($handle, $key) use (&$customers, &$finalCustomers){
            if($handle != '')
                $finalCustomers[$key] = $handle;
        });
        
        if(!empty($finalCustomers))
            $api->modifyDomainCustomers($domain, $finalCustomers);

        return ['success' => true];
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    return $values;
}

/**
 * Get domain epp code
 * @param type $params
 * @return type
 */
function openprovider_GetEPPCode($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    $values = array();

    try
    {
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $api = new \OpenProvider\API\API($params);
        $eppCode = $api->getEPPCode($domain);
        
        if(!$eppCode)
        {
            throw new Exception('EPP code is not set');
        }
        $values["eppcode"] = $eppCode ? $eppCode : '';
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    
    return $values;
}


/**
 * Add name server in domain
 * @param type $params
 * @return array|string
 */
function openprovider_RegisterNameserver($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();
    
            // get data from op
    $api                = new \OpenProvider\API\API($params);
    $domain             =   new \OpenProvider\API\Domain(array(
        'name'          =>  $params['sld'],
        'extension'     =>  $params['tld']
    ));
           
    try
    {
        
        $nameServer         =   new \OpenProvider\API\DomainNameServer();
        $nameServer->name   =   $params['nameserver'];
        $nameServer->ip     =   $params['ipaddress'];
        
        if (($nameServer->name == '.' . $params['sld'] . '.' . $params['tld']) || !$nameServer->ip)
        {
            throw new Exception('You must enter all required fields');
        }

        $api = new \OpenProvider\API\API($params);
        $api->nameserverRequest('create', $nameServer);
        
        return 'success';
    }
    catch (\Exception $e)
    {
        return array
        (
            'error' => $e->getMessage(),
        );
    }
}


/**
 * Modify existing name servers
 * @param array $params
 * @return array|string
 */
function openprovider_ModifyNameserver($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    $newIp      =   $params['newipaddress'];
    $currentIp  =   $params['currentipaddress'];
    
    // check if not empty
    if (($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) || !$newIp || !$currentIp)
    {
        return array(
            'error' => 'You must enter all required fields',
        );
    }
    
    // check if the addresses are different
    if ($newIp == $currentIp)
    {
        return array
        (
            'error' => 'The Current IP Address is the same as the New IP Address',
        );
    }
    
    try
    {
        $nameServer = new \OpenProvider\API\DomainNameServer();
        $nameServer->name = $params['nameserver'];
        $nameServer->ip = $newIp;

        $api = new \OpenProvider\API\API($params);
        $api->nameserverRequest('modify', $nameServer, $currentIp);
    }
    catch (\Exception $e)
    {
        return array
        (
            'error' => $e->getMessage(),
        );
    }
    
    return 'success';
}

/**
 * Delete name server from domain
 * @param type $params
 * @return array|string
 */
function openprovider_DeleteNameserver($params)
{
    $params['sld'] = $params['original']['domainObj']->getSecondLevel();
    $params['tld'] = $params['original']['domainObj']->getTopLevel();

    try
    {
        $nameServer             =   new \OpenProvider\API\DomainNameServer();
        $nameServer->name       =   $params['nameserver'];
        $nameServer->ip         =   $params['ipaddress'];
        
        // check if not empty
        if ($nameServer->name == '.' . $params['sld'] . '.' . $params['tld'])
        {
            return array
            (
                'error'     =>  'You must enter all required fields',
            );
        }

        $api = new \OpenProvider\API\API($params);
        $api->nameserverRequest('delete', $nameServer);
        
        return 'success';
    }
    catch (\Exception $e)
    {
        return array
        (
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Synchronize domain status and expiry date
 * @param type $params
 * @return array
 */
function openprovider_TransferSync($params)
{
    if(isset($param['domainObj']))
    {
        $params['sld'] = $params['domainObj']->getSecondLevel();
        $params['tld'] = $params['domainObj']->getTopLevel();
    }

    try
    {
        // get data from op
        $api                = new \OpenProvider\API\API($params);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));
        
        $opInfo             =   $api->retrieveDomainRequest($domain);
        
        if($opInfo['status'] == 'ACT')
        {
            return array
            (
                'completed'     =>  true,
                'expirydate'    =>  date('Y-m-d', strtotime($opInfo['renewalDate'])),
            );
        }
        
        return array();
    }
    catch (\Exception $ex)
    {
        return array
        (
            'error' =>  $ex->getMessage()
        );
    }

    return [];
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * Domain syncing is intended to ensure domain status and expiry date
 * changes made directly at the domain registrar are synced to WHMCS.
 * It is called periodically for a domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function openprovider_Sync($params)
{
    // This function is here to prevent any errors from WHMCS. 
    // Synchronisation is done in a separate cron job (see manual).
    return[];
}


/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @see http://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain availability check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function openprovider_CheckAvailability($params)
{
    // Safety feature: Make premium opt-in with warning only. See https://requests.whmcs.com/topic/major-bug-premium-domains-billed-incorrectly.
    if(isset($params['OpenproviderPremium']) && $params['OpenproviderPremium'] == 'on')
    {
        $premiumEnabled = (bool) $params['premiumEnabled'];
    }
    else
        $premiumEnabled = false;

    $results = new ResultsList();
    if(empty($params['tldsToInclude']))
        return $results;
    
    $api = new \OpenProvider\API\API($params);
    foreach($params['tldsToInclude'] as $tld)
    {
        $domain             = new \OpenProvider\API\Domain();
        $domain->extension  = substr($tld, 1);
        $domain->name       = $params['isIdnDomain'] ? $params['punyCodeSearchTerm'] : $params['searchTerm'];
        $domains[]          = $domain;
    }

    try {
        $status =  $api->checkDomainArray($domains);
    } catch (Exception $e) {
        if($e->getcode() == 307)
        {
            // OP response: "Your domain request contains an invalid extension!""
            // Meaning: the id is not supported.

            foreach($params['tldsToInclude'] as $tld)
            {
                $domain_tld  = substr($tld, 1);
                $domain_sld  = $params['isIdnDomain'] ? $params['punyCodeSearchTerm'] : $params['searchTerm'];
                $searchResult = new SearchResult($domain_sld, $domain_tld);
                $searchResult->setStatus(SearchResult::STATUS_TLD_NOT_SUPPORTED);
                $results->append($searchResult);
            }

            return $results;
        }
        else
        {
            \logModuleCall('openprovider', 'whois', $domains, $e->getMessage(), null, [$params['Password']]);
            return $results;
        }
    }

    foreach($status as $domain_status)
    {
        $domain_sld = explode('.', $domain_status['domain'])[0];
        $domain_tld = substr(str_replace($domain_sld, '', $domain_status['domain']), 1);

        $searchResult = new SearchResult($domain_sld, $domain_tld);

        if(isset($domain_status['premium']) && $domain_status['status'] == 'free')
        {
            if($premiumEnabled == false)
                $status = SearchResult::STATUS_RESERVED;
            else
            {
                $status = SearchResult::STATUS_NOT_REGISTERED;
                $searchResult->setPremiumDomain(true);

                $args['domain']['name']         = $domain_sld;
                $args['domain']['extension']    = $domain_tld;
                $args['operation']    = 'create';
                $create_pricing = $api->sendRequest('retrievePriceDomainRequest', $args);


                $args['operation']    = 'transfer';
                $transfer_pricing = $api->sendRequest('retrievePriceDomainRequest', $args);

                // Retrieve the pricing
                $searchResult->setPremiumCostPricing(
                    array(
                        'register'  => $create_pricing['price']['reseller']['price'],
                        'renew'     =>  $transfer_pricing['price']['reseller']['price'],
                        'CurrencyCode' => $create_pricing['price']['reseller']['currency'],
                    )
                );

            }
        }
        elseif($domain_status['status'] == 'free')
            $status = SearchResult::STATUS_NOT_REGISTERED;
        else
            $status = SearchResult::STATUS_REGISTERED;

        $searchResult->setStatus($status);
        $results->append($searchResult);

    }
        
    return $results;
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