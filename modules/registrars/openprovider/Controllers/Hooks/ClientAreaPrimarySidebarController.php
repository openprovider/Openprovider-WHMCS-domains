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
        if (
            file_exists($GLOBALS['whmcsAppConfig']->getRootDir() . self::DNSSEC_PAGE_NAME) &&
            !is_null($primarySidebar->getChild('Domain Details Management'))
        ) {
            $domainId        = isset($_REQUEST['domainid']) ? $_REQUEST['domainid'] : $_REQUEST['id'];
            $isDomainEnabled = Capsule::table('tbldomains')
                ->where('id', $domainId)
                ->select('status', 'dnsmanagement', 'domain')
                ->first();

            $domain = DomainFullNameToDomainObject::convert($isDomainEnabled->domain);
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
                ->setLabel('DNSSEC Management')
                ->setUri("dnssec.php?domainid={$domainId}")
                ->setClass($dnssecItemClass)
                ->setOrder(100);
        }
    }
}
