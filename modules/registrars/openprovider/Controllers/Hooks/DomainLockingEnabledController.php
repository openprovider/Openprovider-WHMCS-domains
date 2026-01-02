<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Models\Domain;
use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;
use OpenProvider\WhmcsRegistrar\helpers\Cache;

/**
 * Class DomainController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2024
 */
class DomainLockingEnabledController
{

    const REGISTRAR_OPENPROVIDER = 'openprovider';

    /**
     * DomainController constructor.
     * @param ApiHelper $apiHelper
     */
    public function __construct(private ApiHelper $apiHelper, private Domain $domain) {}

    public function handleDomainLockingClientArea(array $vars): string
    {
        $id = null;

        if (isset($vars['domain'])) {
            $id = $vars['domain']->id ?? null;
        }

        if (!$id) {
            return "";
        }
        // Get the domain details
        $domain = $this->domain->find($id);

        // Check if OpenProvider is the provider
        if (!$domain || $domain->registrar !== self::REGISTRAR_OPENPROVIDER) {
            return "";
        }

        if ($this->isDomainLockingEnabled($domain)) {
            return "";
        }

        return '
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var tabReglockListItem = document.querySelector(\'a[href="#tabReglock"]\');
                    if (tabReglockListItem) {
                        tabReglockListItem.parentElement.style.display = \'none\';
                    }
                });
                document.addEventListener("DOMContentLoaded", function() {
                    var alerts = document.querySelectorAll(\'.alert.alert-danger\');
                    alerts.forEach(function(alert) {
                        var strongTag = alert.querySelector(\'strong\');
                        if (strongTag && strongTag.textContent.trim() === \'Domain Currently Unlocked!\') {
                            alert.remove();
                        }
                    });
                });
            </script>';
    }

    public function handleDomainLockingClientSidebar(\WHMCS\View\Menu\Item $primarySidebar): ?string
    {
        $action = $_REQUEST['action'] ?? '';
        if ($action !== 'domaindetails') {
            return "";
        }
        $id = $_REQUEST['id'] ?? ($_REQUEST['domainid'] ?? null);
        if (!$id) {
            return "";
        }
        // Get the domain details
        $domain = $this->domain->find($id);
        // Check if OpenProvider is the provider
        if (!$domain || $domain->registrar !== self::REGISTRAR_OPENPROVIDER) {
            return "";
        }

        if ($this->isDomainLockingEnabled($domain)) {
            return "";
        }

        if (!is_null($primarySidebar->getChild('Domain Details Management'))) {
            $primarySidebar->getChild('Domain Details Management')->removeChild('Registrar Lock Status');
        }

        return null;
    }

    public function handleDomainLockingAdminArea(): string
    {
        if (basename($_SERVER['PHP_SELF'] ?? (($_SERVER['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_FILENAME']) ?? '')) !== "clientsdomains.php") {
            return "";
        }
        $id = $_REQUEST['id'] ?? ($_REQUEST['domainid'] ?? null);
        if (!$id) {
            return "";
        }
        // Get the domain details
        $domain = $this->domain->find($id);
        // Check if OpenProvider is the provider
        if (!$domain || $domain->registrar !== self::REGISTRAR_OPENPROVIDER) {
            return "";
        }

        if ($this->isDomainLockingEnabled($domain)) {
            return "";
        }

        return '<script>
            $(function (){
                $(\'td.fieldarea input[type="checkbox"][name="lockstatus"]\').closest(\'tr\').remove();
            });
        </script>';
    }

    private function isDomainLockingEnabled(Domain $domain): bool
    {
        try {
            $op_domain_obj = DomainFullNameToDomainObject::convert($domain->domain);
            $lockable_state = Cache::get($op_domain_obj->extension);

            if (!isset($lockable_state)) {
                $op_domain = $this->apiHelper->getDomain($op_domain_obj);
                if (($op_domain['isLockable'] ?? false)) {
                    Cache::set($op_domain['domain']['extension'], true);
                    return true;
                } else {
                    Cache::set($op_domain['domain']['extension'], false);
                    return false;
                }
            } else {
                return $lockable_state;
            }
        } catch (\Exception $e) {
        }

        return false;
    }
}
