<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainNameServer;
use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
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
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
        $this->domain = $domain;
    }

    /**
     * Get the nameservers.
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        try {
            $nameservers = $api->getDomainNameserversRequst($this->domain);
            $return = array ();
            $i = 1;

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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        try
        {
            $nameServers        =   APITools::createNameserversArray($params);

            $api->updateDomainNameserversRequest($this->domain, $nameServers);

        }
        catch (\Exception $e)
        {
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        try
        {

            $nameServer         =   new DomainNameServer();
            $nameServer->name   =   $params['nameserver'];
            $nameServer->ip     =   $params['ipaddress'];

            if (($nameServer->name == '.' . $this->domain->getFullName()) || !$nameServer->ip)
            {
                throw new Exception('You must enter all required fields');
            }

            $api->createDnsNameserversRequest($nameServer);

            return 'success';
        }
        catch (\Exception $e)
        {
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $newIp      =   $params['newipaddress'];
        $currentIp  =   $params['currentipaddress'];

        // check if not empty
        if (($params['nameserver'] == '.' . $this->domain->getFullName()) || !$newIp || !$currentIp)
        {
            return array(
                'error' => 'You must enter all required fields',
            );
        }

        // check if the addresses are different
        if ($newIp == $currentIp)
        {
            return array
            (
                'error' => 'The Current IP Address is the same as the New IP Address',
            );
        }

        try
        {
            $nameServer = new DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip = $newIp;

            $api->updateDnsNameserversRequest($nameServer, $currentIp);
        }
        catch (\Exception $e)
        {
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        try
        {
            $nameServer             =   new DomainNameServer();
            $nameServer->name       =   $params['nameserver'];
            $nameServer->ip         =   $params['ipaddress'];

            // check if not empty
            if ($nameServer->name == '.' . $this->domain->getFullName())
            {
                return array
                (
                    'error'     =>  'You must enter all required fields',
                );
            }

            $api->deleteDnsNameserversRequest($nameServer);

            return 'success';
        }
        catch (\Exception $e)
        {
            return array
            (
                'error' => $e->getMessage(),
            );
        }
    }
}