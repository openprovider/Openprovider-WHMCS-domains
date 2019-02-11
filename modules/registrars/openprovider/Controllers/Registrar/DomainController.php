<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Registrar;

use idna_convert;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainTransfer;
use OpenProvider\API\DomainRegistration;
use OpenProvider\WhmcsRegistrar\Library\Handle;
use OpenProvider\WhmcsRegistrar\Library\AdditionalFields;

/**
 * Class DomainController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainController
{
    /**
     * additionalFields class
     *
     * @var object \OpenProvider\WhmcsRegistrar\Library\AdditionalFields
     */
    protected $additionalFields;

    /**
    * Constructor
    * 
    * @return void
    */
    public function __construct (AdditionalFields $additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }

    /**
    * Register a domain
    * 
    * @return string
    */
    public function register ($params)
    {
        $params['sld'] = $params['domainObj']->getSecondLevel();
        $params['tld'] = $params['domainObj']->getTopLevel();

        $values = array();

        try
        {
            $domain             =   new Domain();
            $domain->extension  =   $params['tld'];
            $domain->name       =   $params['sld'];
            
            // Prepare the nameservers
            $nameServers        =   APITools::createNameserversArray($params);
            
            $api = new API($params);
            $handle         = wLaunch(Handle::class);
            $handle->setApi($api);
            
            // Prepare the additional data

            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if(isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            $ownerHandle    = $handle->findOrCreate($params);
            $adminHandle    = $handle->findOrCreate($params, 'admin');
            
            $handles = array();
            $handles['domainid']        = $params['domainid'];
            $handles['ownerHandle']     = $ownerHandle;
            $handles['adminHandle']     = $adminHandle;
            $handles['techHandle']      = $adminHandle;
            $handles['billingHandle']   = $adminHandle;
            $handles['resellerHandle']  = '';
            
            // domain registration
            $domainRegistration                 =   new DomainRegistration();
            $domainRegistration->domain         =   $domain;
            $domainRegistration->period         =   $params['regperiod'];
            $domainRegistration->ownerHandle    =   $handles['ownerHandle'];
            $domainRegistration->adminHandle    =   $handles['adminHandle'];
            $domainRegistration->techHandle     =   $handles['techHandle'];
            $domainRegistration->billingHandle  =   $handles['billingHandle'];
            $domainRegistration->nameServers    =   $nameServers; 
            $domainRegistration->dnsmanagement  =   $params['dnsmanagement'];
            $domainRegistration->isDnssecEnabled =  false;

            if(isset($additionalFields['domainAdditionalData']))
                $domainRegistration->additionalData = json_decode(json_encode($additionalFields['domainAdditionalData']),1);
            
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
            
            $idn = new idna_convert();
            if(
                $params['sld'].'.'.$params['tld'] == $idn->encode($params['sld'].'.'.$params['tld']) 
                && strpos($params['sld'].'.'.$params['tld'], 'xn--') === false
                )
                {
                    unset($domainRegistration->additionalData->idnScript);
                }
                
            $api->registerDomain($domainRegistration);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }

    /**
     * Transfer the domain.
     *
     * @param [type] $params
     * @return void
     */
    public function transfer($params)
    {
        $params['sld'] = $params['domainObj']->getSecondLevel();
        $params['tld'] = $params['domainObj']->getTopLevel();

        $values = array();

        try
        {
            $domain             =   new Domain(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));
            $api = new API($params);

            $nameServers = APITools::createNameserversArray($params);
            
            $handle         = wLaunch(Handle::class);
            $handle->setApi($api);

            // Prepare the additional data
            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if(isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            $ownerHandle    = $handle->findOrCreate($params);
            $adminHandle    = $handle->findOrCreate($params, 'admin');

            $domainTransfer                 =   new DomainTransfer();
            $domainTransfer->domain         =   $domain;
            $domainTransfer->period         =   $params['regperiod'];
            $domainTransfer->nameServers    =   $nameServers;
            $domainTransfer->ownerHandle    =   $ownerHandle;
            $domainTransfer->adminHandle    =   $adminHandle;
            $domainTransfer->techHandle     =   $adminHandle;
            $domainTransfer->billingHandle  =   $adminHandle;
            $domainTransfer->authCode       =   $params['transfersecret'];
            $domainTransfer->dnsmanagement  =   $params['dnsmanagement'];
            $domainTransfer->isDnssecEnabled =  false;

            // Check if premium is enabled. If so, set the received premium cost.
            if($params['premiumEnabled'] == true && $params['premiumCost'] != '')
                $domainTransfer->acceptPremiumFee        =   $params['premiumCost'];

            if($params['idprotection'] == 1)
                $domainTransfer->isPrivateWhoisEnabled = 1;

            if($params['dnsTemplate'] && $params['dnsTemplate'] != 'None')
            {
                $domainTransfer->nsTemplateName =   $params['dnsTemplate'];
            }
            
            if(isset($additionalFields['domainAdditionalData']))
                $domainTransfer->additionalData = json_decode(json_encode($additionalFields['domainAdditionalData']),1);

            $api->transferDomain($domainTransfer);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}