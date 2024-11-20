<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;
use OpenProvider\API\API;

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
     * @var API
     */
    private $API;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper, API $API)
    {
        parent::__construct($core);

        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
        $this->API = $API;
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
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try {
            $api                =   $this->API;
            $api->setParams($params);
            $domain             =   $this->domain;
            $domain->load(array(
                'name' => $params['sld'],
                'extension' => $params['tld']
            ));
            $nameservers = $api->getNameservers($domain);
            $return = array();
            $i = 1;

            foreach ($nameservers as $ns) {
                $return['ns' . $i] = $ns;
                $i++;
            }

            return $return;
        } catch (\Exception $e) {
            return array(
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
        $domain = $this->domain;
        $domain->load(array(
            'name'      => $params['original']['domainObj']->getSecondLevel(),
            'extension' => $params['original']['domainObj']->getTopLevel(),
        ));

        try {
            $nameServers = \OpenProvider\API\APITools::createNameserversArray($params,$this->apiHelper);
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

        if (($nameServer->name == '.' . $params['sld'] . '.' . $params['tld']) || !$nameServer->ip)
        {
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
        if (($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) || !$newIp || !$currentIp)
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
            $nameServer = new \OpenProvider\API\DomainNameServer();
            $nameServer->name = $params['nameserver'];
            $nameServer->ip = $newIp;

            $this->apiHelper->updateNameserver($nameServer, $currentIp);
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
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        // check if not empty
        if ($params['nameserver'] == '.' . $params['sld'] . '.' . $params['tld']) {
            return array
            (
                'error' => 'You must enter all required fields',
            );
        }

        $this->apiHelper->deleteNameserver($params['nameserver']);

        return 'success';
    }
}
