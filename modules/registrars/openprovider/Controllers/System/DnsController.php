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
    const RETURN_SUCCESS = "success";

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

        if (empty($dnsInfo) || empty($dnsInfo['records'])) {
            return [];
        }

        $dnsRecords = $this->removeUnsupportedRecords($dnsInfo['records']);

        return $dnsRecords ?
            $this->prepareRecordsToDisplay($dnsRecords, sprintf('%s.%s', $params['sld'], $params['tld'])) :
            [];
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

        $records = $this->convertToObjects($params['dnsrecords']);

        $domain = $this->domain;
        $domain->name = $params['sld'];
        $domain->extension = $params['tld'];

        try {
            if (empty($records)) {
                $this->apiHelper->deleteDnsRecords($domain);

                return self::RETURN_SUCCESS;
            }

            $dnsZone = $this->apiHelper->getDns($domain);
            if ($dnsZone) {
                $this->apiHelper->updateDnsRecords($domain, $records);

                return self::RETURN_SUCCESS;
            }

            $this->apiHelper->createDnsRecords($domain, $records);

            return self::RETURN_SUCCESS;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array $records
     *
     * @return array records filtered by type. Type must be included in APIConfig::$supportedDnsTypes array
     */
    private function removeUnsupportedRecords(array $records): array
    {
        $result = [];
        foreach ($records as $record) {
            if (!in_array($record['type'], APIConfig::$supportedDnsTypes)) {
                continue;
            }

            $result[] = $record;
        }

        return $result;
    }

    /**
     * @param array $records
     * @param string $domainName
     *
     * @return array records to display on dns management page or empty array
     */
    private function prepareRecordsToDisplay(array $records, string $domainName): array
    {
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'hostname' => $this->removeDomainNameFromRecordName($record['name'], $domainName),
                'type'     => $record['type'],
                'address'  => $record['value'],
                'priority' => is_numeric($record['prio']) ? $record['prio'] : ''
            ];
        }

        return $result;
    }

    /**
     * example:
     * "www.domain.com" => "www"
     * "ftp.domain.com" => "ftp"
     * "domain.com" => ""
     *
     * @param string $name
     * @param string $domainName
     *
     * @return string name without domain part
     */
    private function removeDomainNameFromRecordName(string $name, string $domainName): string
    {
        if ($name == $domainName) {
            return '';
        }

        $pos = stripos($name, '.' . $domainName);
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }

        return $name;
    }

    /**
     * @param array $records
     *
     * @return DNSrecord[] array of objects
     */
    private function convertToObjects(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $result = [];
        foreach ($records as $record) {
            if (!$record['hostname'] && !$record['address']) {
                continue;
            }

            $dnsRecord        = new DNSrecord();
            $dnsRecord->type  = $record['type'];
            $dnsRecord->name  = $record['hostname'];
            $dnsRecord->value = $record['address'];
            $dnsRecord->ttl   = APIConfig::$dnsRecordTtl;

            // priority - required for MX records and SRV records; ignored for all other record types
            if ('MX' == $dnsRecord->type or 'SRV' == $dnsRecord->type) {
                if (is_numeric($record['priority'])) {
                    $dnsRecord->prio = $record['priority'];
                } else {
                    $dnsRecord->prio = APIConfig::$dnsRecordPriority;
                }
            }

            if (!$dnsRecord->value) {
                continue;
            }

            if (in_array($dnsRecord, $result)) {
                continue;
            }

            $result[] = $dnsRecord;
        }

        return $result;
    }
}
