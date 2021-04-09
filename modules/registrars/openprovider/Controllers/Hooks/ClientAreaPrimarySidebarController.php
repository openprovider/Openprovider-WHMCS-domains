<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\API;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsRegistrar\helpers\DNS;
use WeDevelopCoffee\wPower\Models\Registrar;
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

            // TODO: update this fragment when api change
            $api = new API();
            $params = (new Registrar())->getRegistrarData()['openprovider'];
            $api->setParams($params);
            try {
                $openProvider = new OpenProvider();
                $domain = $openProvider->domain($isDomainEnabled->domain);
                $op_domain = $api->retrieveDomainRequest($domain);
                if (!$op_domain)
                    return;
            } catch (\Exception $e) {
                return;
            }

            if (!$isDomainEnabled->dnsmanagement)
                return;

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
