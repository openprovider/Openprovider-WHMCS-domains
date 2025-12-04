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
    public function redirectDnsManagementPage($params)
    {
        if ($url = DNS::getDnsUrlOrFail($params['domainid'])) {

            // Fallback if referer is not set
            $previousUrl = $_SERVER['HTTP_REFERER'] ?? 'clientarea.php?action=domains';

            // Use json_encode to safely embed URLs in JS strings
            $urlJs        = json_encode($url);
            $previousUrlJs = json_encode(html_entity_decode($previousUrl));

            echo '<script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    // Try to open in a new tab, just like target="_blank"
                    var newWindow = window.open(' . $urlJs . ', "_blank");

                    if (newWindow && !newWindow.closed) {
                        // New tab opened successfully: go back to previous page in this tab
                        newWindow.focus();
                        window.location.href = ' . $previousUrlJs . ';
                    } else {
                        // Popup blocked: just redirect this tab instead
                        window.location.href = ' . $urlJs . ';
                    }
                });
            </script>';
            exit;
        }
    }
}