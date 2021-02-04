<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Carbon\Carbon;
use Exception;
use OpenProvider\API\JsonAPI;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;

/**
 * Class RenewDomainController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RenewDomainController extends BaseController
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
     * ConfigController constructor.
     */
    public function __construct(Core $core, JsonAPI $API, Domain $domain)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;
    }

    public function renew($params)
    {
        // Prepare the renewal
        $domain = new \OpenProvider\API\Domain(array(
            'name' => $params['original']['domainObj']->getSecondLevel(),
            'extension' => $params['original']['domainObj']->getTopLevel()
        ));


        $period = $params['regperiod'];

        $api = $this->API;
        $api->setParams($params);

        // If isInGracePeriod is true, renew the domain.
        if(isset($params['isInGracePeriod']) && $params['isInGracePeriod'] == true)
        {
            try
            {
                $api->renewDomainRequest($domain, $period);
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
                $api->restoreDomainRequest($domain);
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }

            return [];
        }

        // We did not have a true isInRedemptionGracePeriod or isInGracePeriod. Fall back on the legacy code
        // for older WHMCS versions.

        try
        {
            $domainSoftQuarantineExpireDate = $api->getDomainSoftQuarantineExpiryDate($domain);

            if(!$domainSoftQuarantineExpireDate) {
                $api->restoreDomainRequest($domain);
            } elseif ((new Carbon($domainSoftQuarantineExpireDate, 'Europe/Amsterdam'))->gt(Carbon::now('Europe/Amsterdam'))) {
                $api->renewDomainRequest($domain, $period);
            } else {
                // This only happens when the isInRedemptionGracePeriod was not true.
                throw new Exception("Domain has expired and additional costs may be applied. Please check the domain in your reseller control panel", 1);
            }

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return [];
    }
}