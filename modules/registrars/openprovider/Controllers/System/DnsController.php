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
use OpenProvider\OpenProvider;
use OpenProvider\API\Domain;
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
    public function __construct(Core $core, OpenProvider $openProvider, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = $openProvider;
        $this->domain       = $domain;
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
        try {
            $this->domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $api     = $this->openProvider->api;
            $dnsInfo = $api->getDNS($this->domain);

            if (is_null($dnsInfo)) {
                return array();
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
                $prio = is_numeric($dnsRecord['prio']) ? $dnsRecord['prio'] : '';

                $dnsRecordsArr[] = array(
                    'hostname' => $hostname,
                    'type'     => $dnsRecord['type'],
                    'address'  => $dnsRecord['value'],
                    'priority' => $prio
                );
            }
        } catch (\Exception $e) {
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

        $domain            = $this->domain;
        $domain->name      = $params['sld'];
        $domain->extension = $params['tld'];

        try {
            $api = $this->openProvider->api;
            if (count($dnsRecordsArr)) {
                $api->saveDNS($domain, $dnsRecordsArr);
            } else {
                $api->deleteDNS($domain);
            }

            return "success";
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}