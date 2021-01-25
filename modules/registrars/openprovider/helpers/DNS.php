<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use WHMCS\Database\Capsule;

class DNS
{
    /**
     * Get the DNS URL
     *
     * @return bool|void
     */
    public static function getDnsUrlOrFail($domain_id)
    {
        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $domain_id)
            ->first();

        // Check if OpenProvider is the provider
        if($domain->registrar != 'openprovider' || $domain->status != 'Active')
            return false;

        // Check if we are allowed to make a redirect.
        $newDnsStatus = Configuration::getOrDefault('useNewDnsManagerFeature', false);


        if($newDnsStatus != true)
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