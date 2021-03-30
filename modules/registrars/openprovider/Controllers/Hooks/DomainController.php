<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiInterface;
use WHMCS\Database\Capsule,
    OpenProvider\OpenProvider;

/**
 * Class DomainController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainController
{
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * DomainController constructor.
     * @param ApiInterface $apiClient
     */
    public function __construct(ApiInterface $apiClient)
    {
        $this->apiClient = $apiClient;
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
        $domain = Capsule::table('tbldomains')
                    ->where('id', $vars['domainid'])
                    ->get()[0];

        // Check if OpenProvider is the provider
        if($domain->registrar != 'openprovider' || $domain->status != 'Active')
            return false;

        try {
            $OpenProvider       = new OpenProvider(null, $this->apiClient);
            $op_domain_obj      = $OpenProvider->domain($domain->domain);
            $op_domain          = $this->apiClient->call('searchDomainRequest', [
                'domainNamePattern' => $op_domain_obj->name,
                'extension' => $op_domain_obj->extension,
            ])->getData()['results'][0];
            $OpenProvider->toggle_autorenew($domain, $op_domain);
        } catch (\Exception $e) {
                \logModuleCall('OpenProvider', 'Update auto renew', $domain->domain, @$op_domain, $e->getMessage(), [$vars['Password']]);
            return false;
        }
    }
}
