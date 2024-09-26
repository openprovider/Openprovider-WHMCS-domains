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
            //header("Location: " . $url);

            $urlOne = $_SERVER['HTTP_REFERER'];
            $url_decoded = html_entity_decode($urlOne);

            // Perform open in new tab.
            echo '<script type="text/javascript">
                    window.open("' . $url . '");
                    window.location.href = "' . $url_decoded . '";
                  </script>';
            exit;
        }
    }
}
