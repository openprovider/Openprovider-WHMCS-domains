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
use OpenProvider\API\DNSrecord;
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

        $this->domain->load(array(
            'name'      => $params['sld'],
            'extension' => $params['tld']
        ));

        try {
            $dnsInfo = $this->apiHelper->getDns($this->domain);
        } catch (\Exception $e) {
            return [];
        }

        if (empty($dnsInfo)) {
            return [];
        }

        $domainName = $params['sld'] . '.' . $params['tld'];

        $dnsRecords = $this->getDisplayedRecords($dnsInfo['records']);

        return $this->prepareRecordsToDisplay($dnsRecords, $domainName);
    }

    /**
     * Save the new DNS settings.
     *
     * @param $params
     *
     * @return array|string
     */
    public function save($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $dnsRecordsArr = $this->prepareRecordsToSave($params['dnsrecords']);

        $domain = $this->domain;
        $domain->name = $params['sld'];
        $domain->extension = $params['tld'];

        $values = [];
        try {
            if (count($dnsRecordsArr)) {
                $dnsZone = $this->apiHelper->getDns($domain);
                if ($dnsZone) {
                    $this->apiHelper->updateDnsRecords($domain, $dnsRecordsArr);
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

    /**
     * @param array $allRecords
     *
     * @return array records filtered by type. Type must be included in APIConfig::$supportedDnsTypes array
     */
    private function getDisplayedRecords(array $allRecords = []): array
    {
        if (empty($allRecords)) {
            return [];
        }

        $dnsRecordsArr = [];
        foreach ($allRecords as $tmpDnsRecord) {
            if (!in_array($tmpDnsRecord['type'], APIConfig::$supportedDnsTypes)) {
                continue;
            }

            $dnsRecordsArr[] = $tmpDnsRecord;
        }

        return $dnsRecordsArr;
    }

    /**
     * @param array $records
     * @param string $domainName
     *
     * @return array same records but domain name removed from every name parameter
     */
    private function formatNamesOfDnsRecords(array $records, string $domainName): array
    {
        if (empty($records)) {
            return [];
        }

        return array_map(function ($item) use ($domainName) {
            if ($item['name'] == $domainName) {
                $item['name'] = '';
            } else {
                $pos = stripos($item['name'], '.' . $domainName);
                if ($pos !== false) {
                    $item['name'] = substr($item['name'], 0, $pos);
                }
            }

            return $item;
        }, $records);
    }

    /**
     * @param array $records
     * @param string $domainName
     *
     * @return array records to display on dns management page or empty array
     */
    private function prepareRecordsToDisplay(array $records, string $domainName): array
    {
        if (empty($records)) {
            return [];
        }

        $records = $this->formatNamesOfDnsRecords($records, $domainName);
        $dnsRecordsArr = [];
        foreach ($records as $record) {
            $hostname = $record['name'];

            if ($hostname == $domainName) {
                $hostname = '';
            } else {
                $pos = stripos($hostname, '.' . $domainName);
                if ($pos !== false) {
                    $hostname = substr($hostname, 0, $pos);
                }
            }

            $prio            = is_numeric($record['prio']) ? $record['prio'] : '';
            $dnsRecordsArr[] = [
                'hostname' => $hostname,
                'type'     => $record['type'],
                'address'  => $record['value'],
                'priority' => $prio
            ];
        }

        return $dnsRecordsArr;
    }

    /**
     * @param array $records
     *
     * @return DNSrecord[]|array records to replace or empty array
     */
    private function prepareRecordsToSave(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $dnsRecordsArr = [];
        foreach ($records as $tmpDnsRecord) {
            if (!$tmpDnsRecord['hostname'] && !$tmpDnsRecord['address']) {
                continue;
            }

            $dnsRecord        = new DNSrecord();
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

        return $dnsRecordsArr;
    }
}
