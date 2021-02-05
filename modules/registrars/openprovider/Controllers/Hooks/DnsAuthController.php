<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\helpers\DNS;

/**
 * Class DnsAuthController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DnsAuthController {
    public function redirectDnsManagementPage ($params)
    {
        if($url = DNS::getDnsUrlOrFail($params['domainid']))
        {
            // Perform redirect.
            header("Location: " . $url);
            exit;
        }
    }
}
