<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainNameServer;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\OpenProvider;
use OpenProvider\API\Domain;

/**
 * Class ConfigController
 */
class NameserverController extends BaseController
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
     * Get the nameservers.
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try {
            $api    = $this->openProvider->api;
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));
            $nameservers = $api->getNameservers($domain);
            $return      = array();
            $i           = 1;

            foreach ($nameservers as $ns) {
                $return['ns' . $i] = $ns;
                $i++;
            }

            return $return;
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
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
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try {
            $api    = $this->openProvider->api;
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));
            $nameServers = APITools::createNameserversArray($params);

            $api->saveNameservers($domain, $nameServers);
        } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
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
        $api    = $this->openProvider->api;
        $domain = $this->domain;
        $domain->load(array(
            'name'      => $params['sld'],
            'extension' => $params['tld']
        ));

        try {

            $nameServer       = new DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip   = $params['ipaddress'];

            if (($nameServer->name == '.' . $params['sld'] . '.' . $params['tld']) || !$nameServer->ip) {
                throw new Exception('You must enter all required fields');
            }

            $api = $this->openProvider->api;
            $api->nameserverRequest('create', $nameServer);

            return 'success';
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
        }
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

        $newIp     = $params['newipaddress'];
        $currentIp = $params['currentipaddress'];

        // check if not empty
        if (($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) || !$newIp || !$currentIp) {
            return array(
                'error' => 'You must enter all required fields',
            );
        }

        // check if the addresses are different
        if ($newIp == $currentIp) {
            return array
            (
                'error' => 'The Current IP Address is the same as the New IP Address',
            );
        }

        try {
            $nameServer       = new DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip   = $newIp;

            $api = $this->openProvider->api;
            $api->nameserverRequest('modify', $nameServer, $currentIp);
        } catch (\Exception $e) {
            return array
            (
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

        try {
            $nameServer       = new DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip   = $params['ipaddress'];

            // check if not empty
            if ($nameServer->name == '.' . $params['sld'] . '.' . $params['tld']) {
                return array
                (
                    'error' => 'You must enter all required fields',
                );
            }

            $api = $this->openProvider->api;
            $api->nameserverRequest('delete', $nameServer);

            return 'success';
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
        }
    }
}