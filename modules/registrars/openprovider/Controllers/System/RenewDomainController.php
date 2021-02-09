<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Carbon\Carbon;
use Exception;
use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
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
     * @var OpenProvider
     */
    private $openProvider;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
        $this->domain = $domain;
    }

    public function renew($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $period = $params['regperiod'];

        // If isInGracePeriod is true, renew the domain.
        if(isset($params['isInGracePeriod']) && $params['isInGracePeriod'] == true)
        {
            try
            {
                $api->renewDomainRequest($this->domain, $period);
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
                $api->restoreDomainRequest($this->domain);
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }

            return [];
        }

        // We did not have a true isInRedemptionGracePeriod or isInGracePeriod. Fall back on the legacy code
        // for older WHMCS versions.

        try
        {
            $domainSoftQuarantineExpireDate = $api->getDomainSoftQuarantineExpiryDate($this->domain);

            if(!$domainSoftQuarantineExpireDate) {
                $api->restoreDomainRequest($this->domain);
            } elseif ((new Carbon($domainSoftQuarantineExpireDate, 'Europe/Amsterdam'))->gt(Carbon::now('Europe/Amsterdam'))) {
                $api->renewDomainRequest($this->domain, $period);
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