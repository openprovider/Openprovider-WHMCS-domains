<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

/**
 * Class DnsController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2019
 */

use OpenProvider\API\APIConfig;
use OpenProvider\API\DNSrecord;
use OpenProvider\API\Domain;

use OpenProvider\OpenProvider;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

class DnsController extends BaseController
{
    /**
     * @var OpenProvider
     */
    private $openProvider;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
        $this->domain       = $domain;
    }

    /**
     * Get the DNS results.
     *
     * @return array
     */
    public function get($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $dnsRecordsArr = array();
        try {
            $dnsInfo = $api->getDNSZoneRecordsRequest($this->domain);

            if (is_null($dnsInfo)) {
                return array();
            }

            $supportedDnsTypes = APIConfig::$supportedDnsTypes;
            $domainName        = $this->domain->getFullName();

            foreach ($dnsInfo['results'] as $dnsRecord) {
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

                $prio = is_numeric($dnsRecord['prio']) ? $dnsRecord['prio'] : '';

                $dnsRecordsArr[] = array(
                    'hostname' => $hostname,
                    'type'     => $dnsRecord['type'],
                    'address'  => $dnsRecord['value'],
                    'priority' => $prio
                );
            }
        } catch (\Exception $e) {}

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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $dnsRecordsArr = array();
        $values        = array();

        foreach ($params['dnsrecords'] as $tmpDnsRecord) {
            if (!$tmpDnsRecord['hostname'] && !$tmpDnsRecord['address']) {
                continue;
            }

            $dnsRecord        = new DNSrecord();
            $dnsRecord->type  = $tmpDnsRecord['type'];
            $dnsRecord->name  = $tmpDnsRecord['hostname'];
            $dnsRecord->value = $tmpDnsRecord['address'];
            $dnsRecord->ttl   = APIConfig::$dnsRecordTtl;

            if ('MX' == $dnsRecord->type or 'SRV' == $dnsRecord->type) // priority - required for MX records and SRV records; ignored for all other record types
            {
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

        try {
            if (count($dnsRecordsArr)) {
                $api->createOrUpdateDNSZoneRequest($this->domain, $dnsRecordsArr);
            } else {
                $api->deleteDNSZoneRequest($this->domain);
            }

            return "success";
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}