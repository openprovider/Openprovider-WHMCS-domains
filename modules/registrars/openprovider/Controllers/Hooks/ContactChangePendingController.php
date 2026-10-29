<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\APIConfig;
use OpenProvider\WhmcsRegistrar\helpers\ContactChangePending;

/**
 * Class ContactChangePendingController
 * Shows a notice on the domain contacts page while a saved contact change
 * still has to be processed by the registry.
 *
 * @copyright Copyright (c) Openprovider 2026
 */
class ContactChangePendingController
{
    /**
     * ClientAreaFooterOutput hook.
     */
    public function clientArea($vars)
    {
        if (($vars['templatefile'] ?? '') !== 'clientareadomaincontactinfo') {
            return '';
        }

        return $this->render();
    }

    /**
     * AdminAreaFooterOutput hook.
     */
    public function adminArea($vars)
    {
        if (($vars['filename'] ?? '') !== 'clientsdomaincontacts') {
            return '';
        }

        return $this->render();
    }

    private function render(): string
    {
        $domainId = (int) ($_REQUEST['domainid'] ?? 0);
        if ($domainId <= 0) {
            return '';
        }

        // Already refreshed against Openprovider when the page loaded the contacts.
        $pending = ContactChangePending::get($domainId);
        if (empty($pending)) {
            return '';
        }

        $jsonMsg = json_encode(
            $this->buildMessage(array_keys($pending)),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        return <<<HTML
        <script>
        (function () {
            var form = document.querySelector('input[name^="contactdetails["]');
            form = form ? form.closest('form') : null;
            if (!form) {
                return;
            }
            var notice = document.createElement('div');
            notice.className = 'alert alert-info text-center';
            notice.setAttribute('role', 'alert');
            notice.textContent = {$jsonMsg};
            form.parentNode.insertBefore(notice, form);
        })();
        </script>
        HTML;
    }

    /**
     * @param array $handleKeys e.g. ['adminHandle', 'techHandle']
     */
    private function buildMessage(array $handleKeys): string
    {
        $roles = [];
        foreach ($handleKeys as $key) {
            $roles[] = APIConfig::$handlesNames[$key] ?? $key;
        }

        $last  = array_pop($roles);
        $names = $roles ? implode(', ', $roles) . ' and ' . $last : $last;

        if (empty($roles)) {
            return "The {$names} contact change was sent to the registry and is still being processed. "
                . "Until it is finished, the {$names} section below shows the previous details. This usually takes a few minutes.";
        }

        return "The {$names} contact changes were sent to the registry and are still being processed. "
            . "Until they are finished, the {$names} sections below show the previous details. This usually takes a few minutes.";
    }
}
