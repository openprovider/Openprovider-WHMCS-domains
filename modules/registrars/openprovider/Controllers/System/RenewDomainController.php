<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Carbon\Carbon;
use Exception;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class RenewDomainController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RenewDomainController extends BaseController
{
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
        $this->domain = $domain;
    }

    public function renew($params)
    {
        // Prepare the renewal
        $this->domain->load(array(
            'name' => $params['original']['domainObj']->getSecondLevel(),
            'extension' => $params['original']['domainObj']->getTopLevel()
        ));
        $domain = $this->domain;

        $period = $params['regperiod'];
        $domainOp = $this->apiHelper->getDomain($domain);

        if (empty($domainOp)) {
            throw new \Exception("Domain not found in openprovider.", 1);
        }

        // If isInGracePeriod is true, renew the domain.
        if(isset($params['isInGracePeriod']) && $params['isInGracePeriod'] == true)
        {
            try
            {
                $this->apiHelper->renewDomain($domainOp['id'], $period);
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
                $this->apiHelper->restoreDomain($domainOp['id']);
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }

            return [];
        }

        // We did not have a true isInRedemptionGracePeriod or isInGracePeriod. Fall back on the legacy code
        // for older WHMCS versions.

        try
        {
            if(!$domainOp['softQuarantineExpiryDate']) {
                $this->apiHelper->renewDomain($domainOp['id'], $period);
            } elseif ((new Carbon($domainOp['softQuarantineExpiryDate'], 'Europe/Amsterdam'))->gt(Carbon::now('Europe/Amsterdam'))) {
                $this->apiHelper->restoreDomain($domainOp['id']);
            } else {
                // This only happens when the isInRedemptionGracePeriod was not true.
                throw new Exception("Domacin has expired and additional costs may be applied. Please check the domain in your reseller control panel", 1);
            }

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return [];
    }
}
