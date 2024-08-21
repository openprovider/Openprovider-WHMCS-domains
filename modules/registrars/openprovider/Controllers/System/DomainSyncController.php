<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WHMCS\Carbon;
use OpenProvider\WhmcsHelpers\Activity;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain as api_domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Models\Domain;

/**
 * Class TransferSyncController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DomainSyncController extends BaseController
{
    const DOMAIN_STATUSES_ACTIVE = ['ACT'];
    const DOMAIN_STATUSES_INACTIVE = ['REQ', 'PEN', 'SCH'];
    const DOMAIN_STATUSES_CANELLED = ['FAI', 'DEL'];

    /**
     * @var api_domain
     */
    private $api_domain;
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
    public function __construct(Core $core, api_domain $api_domain, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->api_domain = $api_domain;
        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Synchronise the transfer status.
     *
     * @param $params
     * @return array
     */
    public function sync($params)
    {
        $this->domain = $this->domain->find($params['domainid']);
        // Check if the native synchronisation feature
        if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false) {
            return array (
                'expirydate' => $this->domain->expirydate, // Format: YYYY-MM-DD
                'active' => true, // Return true if the domain is active
                'cancelled' => false, // Return true if the domain has expired
                'transferredAway' => false, // Return true if the domain is transferred out
            );
        }

        $this->domain = $this->domain->find($params['domainid']);
        $setting['syncAutoRenewSetting'] = Configuration::getOrDefault('syncAutoRenewSetting', true);
        $setting['syncIdentityProtectionToggle'] = Configuration::getOrDefault('syncIdentityProtectionToggle', true);

        try {
            // get data from op
            $this->api_domain   = DomainFullNameToDomainObject::convert($this->domain->domain);

            $domainOp = $this->apiHelper->getDomain($this->api_domain);

            $expiration_date = (Carbon::createFromFormat('Y-m-d H:i:s', $domainOp['renewalDate'], 'Europe/Amsterdam'))
                ->toDateString();

            if(in_array($domainOp['status'], self::DOMAIN_STATUSES_ACTIVE)) {

                return [
                    'expirydate' => $expiration_date, // Format: YYYY-MM-DD
                    'active' => true, // Return true if the domain is active
                    'cancelled' => false, // Return true if the domain has expired
                    'transferredAway' => false, // Return true if the domain is transferred out
                ];
            } else if (in_array($domainOp['status'], self::DOMAIN_STATUSES_INACTIVE)) {
                return [
                    'expirydate' => $expiration_date, // Format: YYYY-MM-DD
                    'active' => false, // Return true if the domain is active
                    'cancelled' => false, // Return true if the domain has expired
                    'transferredAway' => false, // Return true if the domain is transferred out
                ];
            }
        } catch (\Exception $ex) {
            if($ex->getMessage() == 'This action is prohibitted for current domain status.') {
                // Set the status to expired.
                return [
                    'expirydate' => $this->domain->expirydate, // Format: YYYY-MM-DD
                    'active' => false, // Return true if the domain is active
                    'cancelled' => true, // Return true if the domain has expired
                    'transferredAway' => false, // Return true if the domain is transferred out
                ];
            } else if($ex->getMessage() == 'The domain is not in your account; please transfer it to your account first.') {
                // Set the status to expired.
                return [
                    'expirydate' => $this->domain->expirydate, // Format: YYYY-MM-DD
                    'active' => false, // Return true if the domain is active
                    'cancelled' => false, // Return true if the domain has expired
                    'transferredAway' => true, // Return true if the domain is transferred out
                ];
            }
            
            return [
                'error' =>  $ex->getMessage()
            ];
        }

        return [];
    }

}
