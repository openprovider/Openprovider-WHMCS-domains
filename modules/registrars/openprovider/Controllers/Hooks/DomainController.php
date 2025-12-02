<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Models\Domain;

/**
 * Class DomainController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * DomainController constructor.
     * @param ApiHelper $apiHelper
     */
    public function __construct(ApiHelper $apiHelper, Domain $domain)
    {
        $this->apiHelper = $apiHelper;
        $this->domain = $domain;
    }

    /**
     * @param $vars
     * @return false|void
     */
    public function saveDomainEdit ($vars)
    {
        if(!isset($_POST['domain']) && !isset($_POST['autorenew']))
            return;

        // Get the domain details
        $domain = $this->domain->find($vars['domainid']);

        // Check if OpenProvider is the provider
        if($domain->registrar != 'openprovider' || $domain->status != 'Active')
            return false;

        try {
            $op_domain_obj      = DomainFullNameToDomainObject::convert($domain->domain);
            $op_domain          = $this->apiHelper->getDomain($op_domain_obj);
            $this->apiHelper->toggleAutorenewDomain($domain, $op_domain);
        } catch (\Exception $e) {
                \logModuleCall('OpenProvider', 'Update auto renew', $domain->domain, @$op_domain, $e->getMessage(), [$vars['Password']]);
            return false;
        }
    }

    /**
     * Hook handler for AfterRegistrarRegistration
     */
    public function updateExpiryDateAfterRegistration(array $vars): void
    {
        $this->updateExpiryDate($vars, 'AfterRegistrarRegistration');
    }

    /**
     * Hook handler for AfterRegistrarTransfer
     */
    public function updateExpiryDateAfterTransfer(array $vars): void
    {
        $this->updateExpiryDate($vars, 'AfterRegistrarTransfer');
    }

    /**
     * Shared logic to fetch and update expiry date from Openprovider API.
     *
     * @param array $vars
     * @param string $context
     * @return void
     */
    private function updateExpiryDate(array $vars, string $context): void
    {
        try {
            if (($vars['params']['registrar'] ?? '') !== 'openprovider') {
                return;
            }

            $domainId   = $vars['params']['domainid'] ?? null;
            $domainName = ($vars['params']['sld'] ?? '') . '.' . ($vars['params']['tld'] ?? '');

            if (!$domainId || !$domainName) {
                return;
            }

            // Fetch actual expiry from Openprovider API
            $domainObj = DomainFullNameToDomainObject::convert($domainName);
            $opDomain  = $this->apiHelper->getDomain($domainObj);

            if (!empty($opDomain['renewalDate'])) {
                $expiryDate = date('Y-m-d', strtotime($opDomain['renewalDate']));

                localAPI('UpdateClientDomain', [
                    'domainid'   => $domainId,
                    'expirydate' => $expiryDate,
                ]);
            }
        } catch (\Exception $e) {
            \logModuleCall(
                'OpenProvider',
                $context . ' Hook',
                ['domain' => $vars['params']['sld'] . '.' . $vars['params']['tld']],
                $e->getMessage(),
                [],
                []
            );
        }
    }
}
