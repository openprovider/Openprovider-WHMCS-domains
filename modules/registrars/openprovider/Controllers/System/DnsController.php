<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

/**
 * Class DnsController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2019
 */
use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiHelper;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

class DnsController extends BaseController
{
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Get the DNS results.
     *
     * @return array
     */
    public function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $dnsRecordsArr = array();
        $this->domain->load(array(
            'name'      => $params['sld'],
            'extension' => $params['tld']
        ));

        $dnsInfo = $this->apiHelper->getDns($this->domain);

        if (empty($dnsInfo)) {
            return [];
        }

        $supportedDnsTypes = APIConfig::$supportedDnsTypes;
        $domainName        = $params['sld'] . '.' . $params['tld'];

        foreach ($dnsInfo['records'] as $dnsRecord) {
            if (!in_array($dnsRecord['type'], $supportedDnsTypes)) {
                continue;
            }

            $hostname = $dnsRecord['name'];

            if ($hostname == $domainName) {
                $hostname = '';
            } else {
                $pos = stripos($hostname, '.' . $domainName);
                if ($pos !== false) {
                    $hostname = substr($hostname, 0, $pos);
                }
            }

            $prio            = is_numeric($dnsRecord['prio']) ? $dnsRecord['prio'] : '';
            $dnsRecordsArr[] = [
                'hostname' => $hostname,
                'type'     => $dnsRecord['type'],
                'address'  => $dnsRecord['value'],
                'priority' => $prio
            ];
        }

        return $dnsRecordsArr;
    }

    /**
     * Save the new DNS settings.
     *
     * @param $params
     * @return array|string
     */
    public function save($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $dnsRecordsArr = [];
        $values = [];
        foreach ($params['dnsrecords'] as $tmpDnsRecord) {
            if (!$tmpDnsRecord['hostname'] && !$tmpDnsRecord['address']) {
                continue;
            }

            $dnsRecord        = new \OpenProvider\API\DNSrecord();
            $dnsRecord->type  = $tmpDnsRecord['type'];
            $dnsRecord->name  = $tmpDnsRecord['hostname'];
            $dnsRecord->value = $tmpDnsRecord['address'];
            $dnsRecord->ttl   = APIConfig::$dnsRecordTtl;

            // priority - required for MX records and SRV records; ignored for all other record types
            if ('MX' == $dnsRecord->type or 'SRV' == $dnsRecord->type) {
                if (is_numeric($tmpDnsRecord['priority'])) {
                    $dnsRecord->prio = $tmpDnsRecord['priority'];
                } else {
                    $dnsRecord->prio = APIConfig::$dnsRecordPriority;
                }
            }

            if (!$dnsRecord->value) {
                continue;
            }

            if (in_array($dnsRecord, $dnsRecordsArr)) {
                continue;
            }

            $dnsRecordsArr[] = $dnsRecord;
        }

        $domain = $this->domain;
        $domain->name = $params['sld'];
        $domain->extension = $params['tld'];

        try {
            if (count($dnsRecordsArr)) {
                $dnsZone = $this->apiHelper->getDns($domain);
                if ($dnsZone) {
                    $this->apiHelper->updateDnsRecords($domain, $dnsZone['records'], $dnsRecordsArr);
                } else {
                    $this->apiHelper->createDnsRecords($domain, $dnsRecordsArr);
                }

            } else {
                $this->apiHelper->deleteDnsRecords($domain);
            }

            return "success";
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}
