<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class ConfigController
 */
class NameserverController extends BaseController
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
     * Get the nameservers.
     *
     * This method needed to show Nameservers paragraph on domain information page in the client area.
     *
     * This data takes from domainInformationController
     * by parameter ->setNameservers($nameservers)
     * And here we no need additional request to get only nameservers data from openprovider
     * because it already loaded in the DomainInformationController
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        try {
            // Resolve domain from 'domain' or 'domainid' (Local API), or from client area object
            $domainName = $params['domain'] ?? '';
            if (!$domainName && !empty($params['domainid'])) {
                $domainName = Capsule::table('tbldomains')
                    ->where('id', (int)$params['domainid'])
                    ->value('domain');
            }
            if (!$domainName && isset($params['original']['domainObj'])) {
                $domainName = $params['original']['domainObj']->getDomain();
            }
            if (!$domainName) {
                return ['error' => 'Missing domain identifier (domainid/domain).'];
            }

            // Split SLD/TLD
            $parts = explode('.', $domainName);
            if (count($parts) < 2) {
                return ['error' => 'Invalid domain format.'];
            }
            $tld = array_pop($parts);
            $sld = implode('.', $parts);

            // Load domain DTO and call REST via the injected ApiHelper instance (NOT static)
            $domain = $this->domain;
            $domain->load(['name' => $sld, 'extension' => $tld]);

            $op = $this->apiHelper->getDomain($domain); // <-- instance call

            $items = $op['nameServers'] ?? [];
            if (!is_array($items)) {
                return ['error' => 'Registrar returned no nameservers array.'];
            }

            // Order by seqNr and build ns list (prefer hostname, fallback IP)
            usort($items, static fn($a, $b) => ($a['seqNr'] ?? 0) <=> ($b['seqNr'] ?? 0));
            $ns = [];
            foreach ($items as $it) {
                $host = $it['name'] ?? null;
                $ip   = $it['ip']   ?? null;
                if ($host) {
                    $ns[] = $host;
                } elseif ($ip) {
                    $ns[] = $ip;
                }
            }
            $ns = array_values(array_unique(array_filter($ns)));

            if (count($ns) < 2) {
                $status = $op['status'] ?? '';
                return ['error' => 'Registrar returned fewer than 2 nameservers.' . ($status ? " Status: {$status}" : '')];
            }

            $output = ['result' => 'success'];
            foreach (range(1, 5) as $i) {
                $output["ns{$i}"] = $ns[$i - 1] ?? '';
            }
            return $output;
        } catch (\Throwable $e) {
            return ['error' => 'Registrar Error: ' . $e->getMessage()];
        }
    }

    /**
     * Save the nameservers.
     *
     * @param $params
     * @return array|string
     */
    public function save($params)
    {
        $domain = $this->domain;
        $domain->load(array(
            'name'      => $params['original']['domainObj']->getSecondLevel(),
            'extension' => $params['original']['domainObj']->getTopLevel(),
        ));

        try {
            $nameServers = \OpenProvider\API\APITools::createNameserversArray($params, $this->apiHelper);
            $this->apiHelper->saveDomainNameservers($domain, $nameServers);
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }

        return 'success';
    }

    /**
     * Register a nameserver.
     *
     * @param $params
     * @return array|string
     */
    public function register($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        // get data from op
        $domain             =   $this->domain;
        $domain->load(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $nameServer         =   new \OpenProvider\API\DomainNameServer();
        $nameServer->name   =   $params['nameserver'];
        $nameServer->ip     =   $params['ipaddress'];

        if (($nameServer->name == '.' . $params['sld'] . '.' . $params['tld']) || !$nameServer->ip) {
            return [
                'error' => 'You must enter all required fields'
            ];
        }

        try {
            $this->apiHelper->createNameserver($nameServer);
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }

        return 'success';
    }

    /**
     * Modify a nameserver.
     *
     * @param $params
     * @return array|string
     */
    public function modify($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $newIp      =   $params['newipaddress'];
        $currentIp  =   $params['currentipaddress'];

        // check if not empty
        if (($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) || !$newIp || !$currentIp) {
            return array(
                'error' => 'You must enter all required fields',
            );
        }

        // check if the addresses are different
        if ($newIp == $currentIp) {
            return array(
                'error' => 'The Current IP Address is the same as the New IP Address',
            );
        }

        try {
            $nameServer = new \OpenProvider\API\DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip = $newIp;

            $this->apiHelper->updateNameserver($nameServer, $currentIp);
        } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
        }

        return 'success';
    }

    /**
     * Delete a nameserver.
     *
     * @param $params
     * @return array|string
     */
    public function delete($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        // check if not empty
        if ($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) {
            return array(
                'error' => 'You must enter all required fields',
            );
        }

        $this->apiHelper->deleteNameserver($params['nameserver']);

        return 'success';
    }
}
