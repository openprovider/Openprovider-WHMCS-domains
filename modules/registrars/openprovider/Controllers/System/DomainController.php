<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use idna_convert;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainTransfer;
use OpenProvider\API\DomainRegistration;
use OpenProvider\WhmcsRegistrar\src\PremiumDomain;
use OpenProvider\WhmcsRegistrar\src\Handle;
use OpenProvider\WhmcsRegistrar\src\AdditionalFields;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class DomainController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * additionalFields class
     *
     * @var object \OpenProvider\WhmcsRegistrar\src\AdditionalFields
     */
    protected $additionalFields;
    /**
     * @var Handle
     */
    private $handle;
    /**
     * @var PremiumDomain
     */
    private $premiumDomain;

    /**
    * Constructor
    * 
    * @return void
    */
    public function __construct(Core $core, API $API, Domain $domain, PremiumDomain $premiumDomain, AdditionalFields $additionalFields, Handle $handle)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;
        $this->additionalFields = $additionalFields;
        $this->handle = $handle;
        $this->premiumDomain = $premiumDomain;
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
            $domain             =   $this->domain;
            $domain->extension  =   $params['tld'];
            $domain->name       =   $params['sld'];
            
            // Prepare the nameservers
            $nameServers        =   APITools::createNameserversArray($params);

            $api                =   $this->API;
            $api->setParams($params);
            $handle         = $this->handle;
            $handle->setApi($api);
            
            // Prepare the additional data
            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if(isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            if(isset($additionalFields['customerAdditionalData']))
                $handle->setCustomerAdditionalData($additionalFields['customerAdditionalData']);

            if(isset($additionalFields['customer']))
                $handle->setCustomerData($additionalFields['customer']);

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
            $domainRegistration->isDnssecEnabled =  0;

            if(isset($additionalFields['domainAdditionalData']))
                $domainRegistration->additionalData = json_decode(json_encode($additionalFields['domainAdditionalData']),1);
            
            // Check if premium is enabled. If so, set the received premium cost.
            if($params['premiumEnabled'] == true && $params['premiumCost'] != '')
                $domainRegistration->acceptPremiumFee = $this->premiumDomain->getRegistrarPriceWhenResellerPriceMatches('create', $params['sld'], $params['tld'], $params['premiumCost']);

            
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

            // Sleep for 2 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(2);

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
            $domain             =   new Domain();
            $domain->load(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));
            $api = new API();
            $api->setParams($params);

            $nameServers = APITools::createNameserversArray($params);
            
            $handle         = $this->handle;
            $handle->setApi($api);

            // Prepare the additional data
            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if(isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            if(isset($additionalFields['customer']))
                $handle->setCustomerAdditionalData($additionalFields['customer']);

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
            $domainTransfer->isDnssecEnabled =  0;

            // Check if premium is enabled. If so, set the received premium cost.
            if($params['premiumEnabled'] == true && $params['premiumCost'] != '')
                $domainRegistration->acceptPremiumFee = $this->premiumDomain->getRegistrarPriceWhenResellerPriceMatches('transfer', $params['sld'], $params['tld'], $params['premiumCost']);

            if($params['idprotection'] == 1)
                $domainTransfer->isPrivateWhoisEnabled = 1;

            if($params['dnsTemplate'] && $params['dnsTemplate'] != 'None')
            {
                $domainTransfer->nsTemplateName =   $params['dnsTemplate'];
            }
            
            if(isset($additionalFields['domainAdditionalData']))
                $domainTransfer->additionalData = json_decode(json_encode($additionalFields['domainAdditionalData']),1);

            // Sleep for 2 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(2);
            $api->transferDomain($domainTransfer);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}