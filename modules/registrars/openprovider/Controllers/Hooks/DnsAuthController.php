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

            // Fallback if referer is not set or is not same-origin
            $defaultPreviousUrl = 'clientarea.php?action=domains';
            $previousUrl = $defaultPreviousUrl;
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
                $refererParts = parse_url($referer);

                if ($refererParts !== false) {
                    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                    // Remove port if present
                    $currentHost = explode(':', $currentHost)[0];

                    // Allow relative URLs or same-host absolute URLs
                    if (
                        !isset($refererParts['host']) ||
                        (isset($refererParts['host']) &&
                            strcasecmp($refererParts['host'], $currentHost) === 0)
                    ) {
                        $previousUrl = $referer;
                    }
                }
            }

            // Use json_encode to safely embed URLs in JS strings
            $urlJs        = json_encode($url, JSON_UNESCAPED_SLASHES);
            $previousUrlJs = json_encode(html_entity_decode($previousUrl), JSON_UNESCAPED_SLASHES);

            $shouldOpenDnsInNewWindow = Configuration::getOrDefault('useNewDnsManagerFeatureInNewWindow', true);

            if ($shouldOpenDnsInNewWindow) {
                // JavaScript confirm dialog
                echo '<script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            var newWindow = window.open(' . $urlJs . ', "_blank"); // Open OP DNS management page in a new tab
                            if (newWindow) {
                                window.location.href = ' . $previousUrlJs . '; // Redirect to previous page
                                newWindow.focus(); // Focus on the new tab
                            } else {
                                alert("Popup blocked. Allow popups if you want to open this in a new tab.");
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