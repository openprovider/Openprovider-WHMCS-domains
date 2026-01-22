<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DNS;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WHMCS\Database\Capsule;

/**
 * Class DnsAuthController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class ClientAreaPrimarySidebarController
{
    const DNSSEC_PAGE_NAME = '/dnssec.php';
    private const EXTRA_KEY_DNSSEC_MGMT = 'openprovider_dnssecmanagement';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    public function show($primarySidebar)
    {
        $this->replaceDnsMenuItem($primarySidebar);

        $this->addDNSSECMenuItem($primarySidebar);
    }

    private function replaceDnsMenuItem($primarySidebar)
    {
        // Filter to the Domain menu
        if (!$domainDetailsManagement = $primarySidebar->getChild('Domain Details Management'))
            return;

        if (!$dnsManagement = $domainDetailsManagement->getChild('Manage DNS Host Records'))
            return;

        if ($url = DNS::getDnsUrlOrFail($_REQUEST['domainid'])) {
            // Update the URL.
            $dnsManagement->setUri($url);

            // WHMCS does not natively support target="_blank".
            $id    = $dnsManagement->getId();
            $label = $dnsManagement->getLabel() . '</a>
<script >
jQuery( document ).ready(function() {
    jQuery("#' . $id . '").attr("target", "_blank");
});
</script>
<a href=\'#\' style=\'display:none;\'>';
            $dnsManagement->setLabel($label);
        }
    }

    private function addDNSSECMenuItem($primarySidebar)
    {
        // Check if dnssec.php file exists in the root directory
        if(
            !file_exists($GLOBALS['whmcsAppConfig']->getRootDir() . self::DNSSEC_PAGE_NAME) ||
            empty(file_get_contents($GLOBALS['whmcsAppConfig']->getRootDir() . self::DNSSEC_PAGE_NAME))
        ){
            $source_location = $GLOBALS['whmcsAppConfig']->getRootDir()."/modules/registrars/openprovider/custom-pages" . self::DNSSEC_PAGE_NAME;
            $destination_location = $GLOBALS['whmcsAppConfig']->getRootDir() . self::DNSSEC_PAGE_NAME;
            // Attempt to copy dnssec.php file into the root file
            if (!copy($source_location, $destination_location)) {
                logModuleCall('openprovider', 'copydnssecfile', null, "DNSSEC page error! Failed to add dnssec.php to root directory. Please manually upload the contents of '<Module directory>/registrars/openprovider/custom-pages' to the top level of your WHMCS folder i.e. '<your WHMCS directory>/'" , null, null);
            }
        }
        
        if (            
            file_exists($GLOBALS['whmcsAppConfig']->getRootDir() . self::DNSSEC_PAGE_NAME) &&
            !is_null($primarySidebar->getChild('Domain Details Management'))
        ) {
            $domainId        = isset($_REQUEST['domainid']) ? $_REQUEST['domainid'] : $_REQUEST['id'];
            $isDomainEnabled = Capsule::table('tbldomains')
                ->where('id', $domainId)
                ->select('status','domain')
                ->first();
            
            if (!$isDomainEnabled) {
                return;
            }
            
            $dnssecMgmt = $this->getDnssecManagementFlag((int)$domainId); // 0/1
            if ($dnssecMgmt !== 1) {
                return;
            }

            $domain = DomainFullNameToDomainObject::convert($isDomainEnabled->domain);
            $tld = null;
            if (is_object($domain) && method_exists($domain, 'getExtension')) {
                $tld = $domain->getExtension(); 
            } else {
                $labels = array_values(array_filter(explode('.', strtolower($isDomainEnabled->domain))));
                if (count($labels) > 1) {
                    array_shift($labels);
                    $tld = implode('.', $labels);
                }
            }

            if (empty($tld)) {
                logModuleCall('openprovider', 'supportsDnssec', ['domain' => $isDomainEnabled->domain], null, 'Could not derive TLD', null);
                return;
            }

            try {
                if (!$this->apiHelper->supportsDnssec($tld)) {
                    return;
                }
            } catch (\Exception $e) {
                return;
            }
            
            try {
                $op_domain = $this->apiHelper->getDomain($domain);
            } catch (\Exception $e) {
                return;
            }

            $dnssecItemClass = '';

            if ($isDomainEnabled->status != 'Active')
                $dnssecItemClass = 'disabled';

            $primarySidebar->getChild('Domain Details Management')
                ->addChild('DNSSEC')
                ->setLabel(\Lang::trans('dnssectabname'))
                ->setUri("dnssec.php?domainid={$domainId}")
                ->setClass($dnssecItemClass)
                ->setOrder(100);
        }
    }

    private function getDnssecManagementFlag(int $domainId): int
    {
        try {
            $val = Capsule::table('tbldomains_extra')
                ->where('domain_id', $domainId)
                ->where('name', self::EXTRA_KEY_DNSSEC_MGMT)
                ->value('value');

            // No row = default behavior
            if ($val === null) {
                return self::DEFAULT_DNSSEC_MGMT;
            }

            return ((string)$val === '1') ? 1 : 0;
        } catch (\Exception $e) {
            // Fail-safe: keep old behavior (default enabled)
            return self::DEFAULT_DNSSEC_MGMT;
        }
    }

}
