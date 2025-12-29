<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\helpers\DNS;
use OpenProvider\WhmcsRegistrar\src\Configuration;

/**
 * Class DnsAuthController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DnsAuthController {
    public function redirectDnsManagementPage($params)
    {
        if ($url = DNS::getDnsUrlOrFail($params['domainid'])) {

            // Fallback if referer is not set
            $previousUrl = $_SERVER['HTTP_REFERER'] ?? 'clientarea.php?action=domains';

            // Use json_encode to safely embed URLs in JS strings
            $urlJs        = json_encode($url);
            $previousUrlJs = json_encode(html_entity_decode($previousUrl));

            $newDnsInNewWindow = Configuration::getOrDefault('useNewDnsManagerFeatureInNewWindow', true);

            if ($newDnsInNewWindow) {
                // JavaScript confirm dialog
                echo '<script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            var newWindow = window.open(' . $urlJs . ', "_blank"); // Open OP DNS management page in a new tab
                            if (newWindow) {
                                window.location.href = ' . $previousUrlJs . '; // Redirect to previous page
                                newWindow.focus(); // Focus on the new tab
                            } else {
                                alert("Pop-up blocked. Allow pop-ups if you want to open this in a new tab.");
                                window.location.href = ' . $urlJs . '; // Redirect to OP DNS management page
                            }
                        });
                    </script>';
                exit;
            } else {
                echo '<script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            window.location.href = ' . $urlJs . '; // Redirect to OP DNS management page
                        });
                    </script>';
                exit;
            }
        }
    }
}