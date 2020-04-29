<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use WeDevelopCoffee\wPower\Models\Registrar;
use WHMCS\Database\Capsule;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;

/**
 * Class DnsAuthController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DnsAuthController{
    /**
     *
     *
     * @return
     */
    public function replaceDnsMenuItem ($primarySidebar)
    {
        // Filter to the Domain menu
        if(!$domainDetailsManagement = $primarySidebar->getChild('Domain Details Management'))
            return;

        if(!$dnsManagement = $domainDetailsManagement->getChild('Manage DNS Host Records'))
            return;

        if($url = $this->getDnsUrlOrFail($_REQUEST['domainid']))
        {
            // Update the URL.
            $dnsManagement->setUri($url);

            // WHMCS does not natively support target="_blank".
            $id = $dnsManagement->getId();
            $label = $dnsManagement->getLabel() . '</a>
<script >
jQuery( document ).ready(function() {
    jQuery("#' . $id . '").attr("target","_blank");
});
</script>
<a href=\'#\' style=\'display:none;\'>';
            $dnsManagement->setLabel($label);
        }

    }

    /**
     *
     *
     * @return
     */
    public function redirectDnsManagementPage ($params)
    {
        if($url = $this->getDnsUrlOrFail($params['domainid']))
        {
            // Perform redirect.
            header("Location: " . $url);
            exit;
        }
    }

    /**
     * Get the DNS URL
     *
     * @return bool|void
     */
    private function getDnsUrlOrFail($domain_id)
    {
        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $domain_id)
            ->get()[0];

        // Check if OpenProvider is the provider
        if($domain->registrar != 'openprovider' || $domain->status != 'Active')
            return false;

        // Check if we are allowed to make a redirect.
        $newDnsStatus = Registrar::getByKey('openprovider', 'useNewDnsManagerFeature', '');


        if($newDnsStatus != 'on')
            return false;

        // Let's get the URL.
        try {
            $OpenProvider       = new OpenProvider();
            return $OpenProvider->api->getDnsSingleDomainTokenUrl($domain->domain)['url'];
        } catch (\Exception $e) {
            \logModuleCall('OpenProvider', 'Fetching generateSingleDomainTokenRequest', $domain->domain, @$response, $e->getMessage(), [htmlentities($params['Password']), $params['Password']]);
            return false;
        }
    }
}
