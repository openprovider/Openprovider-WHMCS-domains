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

    /**
     * @var DNS
     */
    private $dnsHelper;
    /**
     * ConfigController constructor.
     */
    public function __construct(DNS $dnsHelper)
    {
        $this->dnsHelper = $dnsHelper;
    }

    public function redirectDnsManagementPage ($params)
    {
        if($url = $this->dnsHelper->getDnsUrlOrFail($params['domainid']))
        {
            $urlOne = $_SERVER['HTTP_REFERER'];
            $url_decoded = html_entity_decode($urlOne);

        
            // JavaScript confirm dialog
            echo '<script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        var userConfirmed = confirm("Do you want to open in New Tab?");
                        if (userConfirmed) {
                            var newWindow = window.open("' . $url . '", "_blank"); // Open OP DNS management page in a new tab
                            if (newWindow) {
                                window.location.href = "' . $url_decoded . '"; // Redirect to previous page
                                newWindow.focus(); // Focus on the new tab
                            } else {
                                alert("New tab opening blocked! Please allow it for this site.");
                                window.location.href = "' . $url . '"; // Redirect to OP DNS management page
                            }
                        } else {
                            window.location.href = "' . $url . '"; // Redirect to OP DNS management page
                        }
                    });
                  </script>';
            exit;
        }
    }
}
