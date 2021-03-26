<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use WHMCS\Database\Capsule,
    OpenProvider\WhmcsRegistrar\src\OpenProvider;

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
     * DomainController constructor.
     * @param ApiHelper $apiHelper
     */
    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
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
            $OpenProvider       = new OpenProvider();
            $op_domain_obj      = $OpenProvider->domain($domain->domain);
            $op_domain          = $this->apiHelper->getDomain($op_domain_obj);
            $OpenProvider->toggle_autorenew($domain, $op_domain);
        } catch (\Exception $e) {
                \logModuleCall('OpenProvider', 'Update auto renew', $domain->domain, @$op_domain, $e->getMessage(), [$vars['Password']]);
            return false;
        }
    }
}
