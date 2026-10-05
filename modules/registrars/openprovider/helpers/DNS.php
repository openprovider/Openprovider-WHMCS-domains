<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WHMCS\Database\Capsule;

class DNS
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    
    /**
     * ConfigController constructor.
     */
    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    /**
     * Get the DNS URL
     *
     * @param int $domain_id
     * @param bool $skipConfigCheck  Whether to ignore useNewDnsManagerFeature flag
     * @return string|bool
     */
    public function getDnsUrlOrFail($domain_id, bool $skipConfigCheck = false)
    {
        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $domain_id)
            ->first();
        
        if (!$domain) {
            return false;
        }

        // Check if OpenProvider is the provider
        if($domain->registrar != 'openprovider' || $domain->status != 'Active')
            return false;

        // Client-side feature toggle (unless skipped)
        if (!$skipConfigCheck) {

            // Check if we are allowed to make a redirect
            $newDnsStatus = Configuration::getOrDefault('useNewDnsManagerFeature', false);

            if ($newDnsStatus !== true) {
                return false;
            }
        }

        $op_domain_obj = DomainFullNameToDomainObject::convert($domain->domain);

        // Let's get the URL.
        try {
            $domainOp = $this->apiHelper->getDomain($op_domain_obj);
            $zoneProvider = !empty($domainOp['isSectigoDnsEnabled']) ? 'sectigo' : 'openprovider';
            $response = $this->apiHelper->getDnsDomainToken($domain->domain, $zoneProvider);
            return $response['url'] ?? false;
        } catch (\Exception $e) {
            logModuleCall('OpenProvider','getDnsDomainToken',$domain->domain,$e->getMessage(),'', '');
            return false;
        }
    }

}