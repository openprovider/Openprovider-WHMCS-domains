<?php
use Carbon\carbon;
use WHMCS\Database\Capsule;
use OpenProvider\Notification;
use OpenProvider\OpenProvider as OP;
use OpenProvider\WhmcsHelpers\Registrar;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

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
    // creating the necessary tables
    \OpenProvider\API\APITools::createOpenprovidersTable();
    \OpenProvider\API\APITools::createCustomFields();

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
        "useLocalHanlde"    => array 
        (
            "FriendlyName"  => "Reuse client handles",
            "Type"          => "yesno",
            "Description"   => "When creating a new domain, module will attempt to reuse existing handles",
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

    $values = array();
    
    try
    {
        $domain             =   new \OpenProvider\API\Domain();
        $domain->extension  =   $params['tld'];
        $domain->name       =   $params['sld'];
        $nameServers        =   \OpenProvider\API\APITools::createNameserversArray($params);
        $createNewHandles   =   false;
        $useLocalHandle     =   isset($params['useLocalHanlde']) && $params['useLocalHanlde'];
        
        if ($useLocalHandle)
        {
            // read user's handles
            $handles        =   \OpenProvider\API\APITools::readCustomerHandles($params['userid']);
            
            if ($handles->ownerHandle && $handles->adminHandle && $handles->techHandle && $handles->billingHandle)
            {
                $ownerHandle    =   $handles->ownerHandle;
                $adminHandle    =   $handles->adminHandle;
                $techHandle     =   $handles->techHandle;
                $billingHandle  =   $handles->billingHandle;
            }
            else
            {
                $createNewHandles = true;
            }
        }
        
//        if($params['tld'] == 'es' || $params['tld'] == 'cat')
//        {
            
            $fields         = \OpenProvider\API\APITools::getClientCustomFields($params['customfields']);
            if(!is_object($additionalData))
                $additionalData =   new \OpenProvider\API\CustomerAdditionalData();
            
            
            if($fields['ownerType'] == 'Individual')
            {
                $additionalData->set('socialSecurityNumber', $fields['socialSecurityNumber']);
                $additionalData->set('passportNumber', $fields['passportNumber']);
            } elseif($fields['ownerType'] == 'Company')
            {
                $additionalData->set('companyRegistrationNumber', $fields['companyRegistrationNumber']);
                $additionalData->set('VATNumber', $fields['VATNumber']);
            }
            
//        }
        
        if ($params['tld'] == 'ca') {
            $handles = \OpenProvider\API\APITools::getHandlesForDomainId($params['domainid']);
        }
        
        if (empty($handles) || $params['tld'] != 'ca') {
            if (!$useLocalHandle || $createNewHandles) {
                $ownerCustomer      =   new \OpenProvider\API\Customer($params['original']);
                $ownerCustomer      ->  additionalData = $additionalData;
                
                $ownerHandle        =   \OpenProvider\API\APITools::createCustomerHandle($params, $ownerCustomer);
                
                $adminCustomer      =   new \OpenProvider\API\Customer($params['original']);
                $adminCustomer      ->  additionalData = $additionalData;
                $adminHandle        =   \OpenProvider\API\APITools::createCustomerHandle($params, $adminCustomer);

                $techCustomer       =   new \OpenProvider\API\Customer($params['original']);
                $techCustomer       ->  additionalData = $additionalData;
                $techHandle         =   \OpenProvider\API\APITools::createCustomerHandle($params, $techCustomer);

                $billingCustomer    =   new \OpenProvider\API\Customer($params['original']);
                $billingCustomer    ->  additionalData = $additionalData;
                $billingHandle      =   \OpenProvider\API\APITools::createCustomerHandle($params, $billingCustomer);

                $handles = array();
                $handles['domainid'] = $params['domainid'];
                $handles['ownerHandle'] = $ownerHandle;
                $handles['adminHandle'] = $adminHandle;
                $handles['techHandle'] = $techHandle;
                $handles['billingHandle'] = $billingHandle;
                $handles['resellerHandle'] = '';
                
                if ($params['tld'] == 'ca') {
                    \OpenProvider\API\APITools::saveNewHandles($handles);
                }
                
            }
        }
        
        // domain registration
        $domainRegistration                 =   new \OpenProvider\API\DomainRegistration();
        $domainRegistration->domain         =   $domain;
        $domainRegistration->period         =   $params['regperiod'];
        $domainRegistration->ownerHandle    =   $handles['ownerHandle'];
        $domainRegistration->adminHandle    =   $handles['adminHandle'];
        $domainRegistration->techHandle     =   $handles['techHandle'];
        $domainRegistration->billingHandle  =   $handles['billingHandle'];
        $domainRegistration->nameServers    =   $nameServers;
        $domainRegistration->autorenew      =   'default';
        $domainRegistration->dnsmanagement  =   $params['dnsmanagement'];

        // Check if premium is enabled. If so, set the received premium cost.
        if($params['premiumEnabled'] == true && $params['premiumCost'] != '')
            $domainRegistration->acceptPremiumFee        =   $params['premiumCost'];

        if($params['idprotection'] == 1)
            $domainRegistration->isPrivateWhoisEnabled = 1;

        //use dns templates
        if($params['dnsTemplate'] && $params['dnsTemplate'] != 'None')
        {
            $domainRegistration->nsTemplateName =   $params['dnsTemplate'];
        }

        // New feature.
//        if($params['tld'] == 'nu'){
//            $domainRegistration->additionalData->companyRegistrationNumber = $fields['socialSecurityNumber']; 
//            $domainRegistration->additionalData->passportNumber = $fields['companyRegistrationNumber']; 
//        }
        
        //Additional domain fileds
        if(!empty($params['additionalfields']))
        {
            $additionalData                 =   new \OpenProvider\API\AdditionalData();
            
            foreach($params['additionalfields'] as $name => $value)
            {
                $additionalData->set($name, $value);
            }
            
            $domainRegistration->additionalData =   $additionalData;
        }
        
        $idn = new \idna_convert();
        if(
                $params['sld'].'.'.$params['tld'] == $idn->encode($params['sld'].'.'.$params['tld']) 
                && strpos($params['sld'].'.'.$params['tld'], 'xn--') === false
            )
        {
            unset($domainRegistration->additionalData->idnScript);
        }
        
        sleep(5);
        $api = new \OpenProvider\API\API($params);
        $api->registerDomain($domainRegistration);
        
        // store handles in database
        $storeHandle = new \OpenProvider\API\Handles();
        $storeHandle->importToWHMCS($api, $domain, $params['domainid'], $useLocalHandle);
        
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    return $values;
}


/**
 * Get domain name servers
 * @param type $params
 * @return type
 */
function openprovider_GetNameservers($params) 
{
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
    // Get the domain details
    $domain = Capsule::table('tbldomains')
        ->where('id', $params['domainid'])
        ->get()[0];

    try {
        $OpenProvider       = new OP();
        $op_domain_obj      = $OpenProvider->domain($params['domainname']);
        $op_domain          = $OpenProvider->api->retrieveDomainRequest($op_domain_obj);
        $OpenProvider->toggle_whois_protection($domain, $op_domain);

        return array(
            'success' => 'success',
        );
    } catch (Exception $e) {
        \logModuleCall('OpenProvider', 'Save identity toggle', $params['domainname'], [$OpenProvider->domain, @$op_domain, $OpenProvider], $e->getMessage(), [$params['Password']]);
        
        if($e->getMessage() == 'Wpp contract is not signed')
        {
            $notification = new Notification();
            $notification->WPP_contract_unsigned_one_domain($domain->domain)
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
    $values = array();

    try
    {
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $nameServers = \OpenProvider\API\APITools::createNameserversArray($params);
        
        $createNewHandles = false;
        $useLocalHandle = isset($params['useLocalHanlde']) ? (bool)$params['useLocalHanlde'] : false;
        
        if ($useLocalHandle)
        {
            // read user's handles
            $userId = $params['userid'];
            
            $handles = \OpenProvider\API\APITools::readCustomerHandles($userId);
            
            if ($handles->ownerHandle && $handles->adminHandle && $handles->techHandle && $handles->billingHandle)
            {
                $ownerHandle    =   $handles->ownerHandle;
                $adminHandle    =   $handles->adminHandle;
                $techHandle     =   $handles->techHandle;
                $billingHandle  =   $handles->billingHandle;
            }
            else
            {
                $createNewHandles = true;
            }
        }
        
        if (!$useLocalHandle || $createNewHandles)
        {
            $ownerCustomer = new \OpenProvider\API\Customer($params);
            $ownerHandle = \OpenProvider\API\APITools::createCustomerHandle($params, $ownerCustomer);
            
            $adminCustomer = new \OpenProvider\API\Customer($params);
            $adminHandle = \OpenProvider\API\APITools::createCustomerHandle($params, $adminCustomer);
            
            $techCustomer = new \OpenProvider\API\Customer($params);
            $techHandle = \OpenProvider\API\APITools::createCustomerHandle($params, $techCustomer);
            
            $billingCustomer = new \OpenProvider\API\Customer($params);
            $billingHandle = \OpenProvider\API\APITools::createCustomerHandle($params, $billingCustomer);
        }

        $domainTransfer                 =   new \OpenProvider\API\DomainTransfer();
        $domainTransfer->domain         =   $domain;
        $domainTransfer->period         =   $params['regperiod'];
        $domainTransfer->nameServers    =   $nameServers;
        $domainTransfer->ownerHandle    =   $ownerHandle;
        $domainTransfer->adminHandle    =   $adminHandle;
        $domainTransfer->techHandle     =   $techHandle;
        $domainTransfer->billingHandle  =   $billingHandle;
        $domainTransfer->authCode       =   $params['transfersecret'];
        $domainTransfer->dnsmanagement  =   $params['dnsmanagement'];

        // Check if premium is enabled. If so, set the received premium cost.
        if($params['premiumEnabled'] == true && $params['premiumCost'] != '')
            $domainTransfer->acceptPremiumFee        =   $params['premiumCost'];

        if($params['idprotection'] == 1)
            $domainRegistration->isPrivateWhoisEnabled = 1;

        if($params['dnsTemplate'] && $params['dnsTemplate'] != 'None')
        {
            $domainRegistration->nsTemplateName =   $params['dnsTemplate'];
        }
        
        $idn = new \idna_convert();
        if($params['sld'].'.'.$params['tld'] == $idn->encode($params['sld'].'.'.$params['tld']))
        {
            unset($domainTransfer->additionalData->idnScript);
        }
        
        $api = new \OpenProvider\API\API($params);
        $api->transferDomain($domainTransfer);
    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    return $values;
}

//
function openprovider_RenewDomain($params)
{
    try
    {
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $period = $params['regperiod'];

        $api = new \OpenProvider\API\API($params);

        if($api->getSoftRenewalExpiryDate($domain) == false)

        {
            $api->renewDomain($domain, $period);
        }

        elseif((new Carbon($api->getSoftRenewalExpiryDate($domain), 'CEST'))->gt(Carbon::now()))
        {
            $api->restoreDomain($domain, $period);
        }
        throw new Exception("Domain not in soft quarantine period. Needs to be manually restored", 1);

    }
    catch (\Exception $e)
    {
        $values["error"] = $e->getMessage();
    }
    return $values;
}


/**
 * Get domain contact details
 * @param type $params
 * @return type
 */
function openprovider_GetContactDetails($params)
{
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
    try
    {
        $api                =   new \OpenProvider\API\API($params);
        $handles            =   array_flip(\OpenProvider\API\APIConfig::$handlesNames);
        $domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $params['getFromContactDetails'] = true;
        $customers  =   array();
                
        foreach($params['contactdetails'] as $contactName => $contactValues)
        {
            $customers[$handles[$contactName]]    =   new \OpenProvider\API\Customer($params, $contactName);
        }

        $api->SaveContactDetails($domain, $customers, $params['domainid']);
        
        // store handles in database
        $storeHandle = new \OpenProvider\API\Handles();
        $useLocalHandle = isset($params['useLocalHanlde']) ? (bool)$params['useLocalHanlde'] : false;
        $storeHandle->updateInWHMCS($api, $domain, $params['domainid'], $useLocalHandle);
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
    $premiumEnabled = (bool) $params['premiumEnabled'];

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
        \logModuleCall('openprovider', 'whois', $domains, $e->getMessage(), null, [$params['Password']]);
        return $results;
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