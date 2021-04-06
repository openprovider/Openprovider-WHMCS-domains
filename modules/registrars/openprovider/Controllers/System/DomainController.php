<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use idna_convert;
use OpenProvider\API\API;
use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\Domain;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainTransfer;
use OpenProvider\API\DomainRegistration;
use OpenProvider\WhmcsRegistrar\src\PremiumDomain;
use OpenProvider\WhmcsRegistrar\src\Handle;
use OpenProvider\WhmcsRegistrar\src\AdditionalFields;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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
     * additionalFields class
     *
     * @var object \OpenProvider\WhmcsRegistrar\src\AdditionalFields
     */
    protected $additionalFields;
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var Handle
     */
    private $handle;
    /**
     * @var PremiumDomain
     */
    private $premiumDomain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var ApiInterface
     */
    private $apiClient;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        Core $core,
        API $API,
        Domain $domain,
        PremiumDomain $premiumDomain,
        AdditionalFields $additionalFields,
        Handle $handle,
        ApiHelper $apiHelper,
        ApiInterface $apiClient
    )
    {
        parent::__construct($core);

        $this->API              = $API;
        $this->domain           = $domain;
        $this->additionalFields = $additionalFields;
        $this->handle           = $handle;
        $this->premiumDomain    = $premiumDomain;
        $this->apiHelper        = $apiHelper;
        $this->apiClient        = $apiClient;
        $this->serializer       = new Serializer([new ObjectNormalizer()]);
    }

    /**
     * Register a domain
     *
     * @param $params
     * @return array
     */
    public function register($params)
    {
        $params['sld'] = $params['domainObj']->getSecondLevel();
        $params['tld'] = $params['domainObj']->getTopLevel();

        $values = array();

        try {
            $domain            = $this->domain;
            $domain->extension = $params['tld'];
            $domain->name      = $params['sld'];

            // Prepare the nameservers
            $nameServers = APITools::createNameserversArray($params);

            $handle = $this->handle;
            $handle->setApiHelper($this->apiHelper);
            $this->premiumDomain->setApiClient($this->apiClient);

            // Prepare the additional data
            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if (isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            if (isset($additionalFields['customerAdditionalData']))
                $handle->setCustomerAdditionalData($additionalFields['customerAdditionalData']);

            if (isset($additionalFields['customer']))
                $handle->setCustomerData($additionalFields['customer']);

            $ownerHandle = $handle->findOrCreate($params);
            $adminHandle = $handle->findOrCreate($params, 'admin');

            $handles                   = array();
            $handles['domainid']       = $params['domainid'];
            $handles['ownerHandle']    = $ownerHandle;
            $handles['adminHandle']    = $adminHandle;
            $handles['techHandle']     = $adminHandle;
            $handles['billingHandle']  = $adminHandle;
            $handles['resellerHandle'] = '';

            // domain registration
            $domainRegistration                  = new DomainRegistration();
            $domainRegistration->domain          = $domain;
            $domainRegistration->period          = $params['regperiod'];
            $domainRegistration->ownerHandle     = $handles['ownerHandle'];
            $domainRegistration->adminHandle     = $handles['adminHandle'];
            $domainRegistration->techHandle      = $handles['techHandle'];
            $domainRegistration->billingHandle   = $handles['billingHandle'];
            $domainRegistration->nameServers     = $nameServers;
            $domainRegistration->dnsmanagement   = $params['dnsmanagement'];
            $domainRegistration->isDnssecEnabled = false;

            if (isset($additionalFields['domainAdditionalData'])) {
                $domainRegistration->additionalData = $this->serializer->normalize($additionalFields['domainAdditionalData']);
            }

            // Check if premium is enabled. If so, set the received premium cost.
            if ($params['premiumEnabled'] == true && $params['premiumCost'] != '') {
                $domainRegistration->acceptPremiumFee = $this->premiumDomain->getRegistrarPriceWhenResellerPriceMatches(
                    'create',
                    $params['sld'],
                    $params['tld'],
                    $params['premiumCost']
                );
            }

            if ($params['idprotection'] == 1)
                $domainRegistration->isPrivateWhoisEnabled = true;

            //use dns templates
            if (isset($params['dnsTemplate']) && !empty($params['dnsTemplate'])) {
                $domainRegistration->nsTemplateName = $params['dnsTemplate'];
            }

            if (isset($params['requestTrusteeService']) && !empty($params['requestTrusteeService'])) {
                $trusteeServiceTds = array_map(function ($tld) {
                    if (!empty($tld) && $tld[0] == '.')
                        return mb_strcut($tld, 1);
                    return $tld;
                }, $params['requestTrusteeService']);
                if (in_array($domainRegistration->domain->extension, $trusteeServiceTds))
                    $domainRegistration->useDomicile = true;
            }

            $idn = new idna_convert();
            if (
                $params['sld'] . '.' . $params['tld'] == $idn->encode($params['sld'] . '.' . $params['tld'])
                && strpos($params['sld'] . '.' . $params['tld'], 'xn--') === false
            ) {
                unset($domainRegistration->additionalData->idnScript);
            }

            // Sleep for 2 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(2);

            $this->apiHelper->createDomain($domainRegistration);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }

    /**
     * Transfer the domain.
     *
     * @param array $params
     * @return array
     */
    public function transfer($params)
    {
        $params['sld'] = $params['domainObj']->getSecondLevel();
        $params['tld'] = $params['domainObj']->getTopLevel();

        $values = array();

        try {
            $domain = new Domain();
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $nameServers = APITools::createNameserversArray($params);

            $handle = $this->handle;
            $handle->setApiHelper($this->apiHelper);
            $this->premiumDomain->setApiClient($this->apiClient);

            // Prepare the additional data
            $additionalFields = $this->additionalFields->processAdditionalFields($params, $domain);
            if (isset($additionalFields['extensionCustomerAdditionalData']))
                $handle->setExtensionAdditionalData($additionalFields['extensionCustomerAdditionalData']);

            if (isset($additionalFields['customer']))
                $handle->setCustomerAdditionalData($additionalFields['customer']);

            $ownerHandle = $handle->findOrCreate($params);
            $adminHandle = $handle->findOrCreate($params, 'admin');

            $domainTransfer                  = new DomainTransfer();
            $domainTransfer->domain          = $domain;
            $domainTransfer->period          = $params['regperiod'];
            $domainTransfer->nameServers     = $nameServers;
            $domainTransfer->ownerHandle     = $ownerHandle;
            $domainTransfer->adminHandle     = $adminHandle;
            $domainTransfer->techHandle      = $adminHandle;
            $domainTransfer->billingHandle   = $adminHandle;
            $domainTransfer->authCode        = $params['transfersecret'];
            $domainTransfer->dnsmanagement   = $params['dnsmanagement'];
            $domainTransfer->isDnssecEnabled = false;

            // Check if premium is enabled. If so, set the received premium cost.
            if ($params['premiumEnabled'] == true && $params['premiumCost'] != '')
                $domainTransfer->acceptPremiumFee = $this->premiumDomain->getRegistrarPriceWhenResellerPriceMatches(
                    'transfer',
                    $params['sld'],
                    $params['tld'],
                    $params['premiumCost']
                );

            if ($params['idprotection'] == 1)
                $domainTransfer->isPrivateWhoisEnabled = true;

            if (isset($params['dnsTemplate']) && !empty($params['dnsTemplate'])) {
                $domainTransfer->nsTemplateName = $params['dnsTemplate'];
            }

            if (isset($additionalFields['domainAdditionalData']))
                $domainTransfer->additionalData = $this->serializer->normalize($additionalFields['domainAdditionalData']);

            if (isset($params['requestTrusteeService']) && !empty($params['requestTrusteeService'])) {
                $trusteeServiceTds = array_map(function ($tld) {
                    if (!empty($tld) && $tld[0] == '.')
                        return mb_strcut($tld, 1);
                    return $tld;
                }, $params['requestTrusteeService']);

                if (in_array($domainTransfer->domain->extension, $trusteeServiceTds))
                    $domainTransfer->useDomicile = true;
            }

            // Sleep for 2 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(2);

            $this->apiHelper->transferDomain($domainTransfer);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}
