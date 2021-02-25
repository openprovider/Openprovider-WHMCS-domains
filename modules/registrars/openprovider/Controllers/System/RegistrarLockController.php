<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\OpenProvider;
use OpenProvider\API\Domain;

/**
 * Class RegistrarLockController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RegistrarLockController extends BaseController
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
     * Get the current lock status.
     *
     * @param $params
     * @return string
     */
    public function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try {
            $api = $this->openProvider->api;
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $lockStatus = $api->getRegistrarLock($domain);
        } catch (\Exception $e) {
            //Nothing...
        }

        return $lockStatus ? 'locked' : 'unlocked';
    }

    /**
     * Save the new lock status.
     *
     * @param $params
     * @return array
     */
    public function save($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $values = array();

        try {
            $api = $this->openProvider->api;
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));
            $lockStatus = $params["lockenabled"] == "locked" ? 1 : 0;

            $api->saveRegistrarLock($domain, $lockStatus);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}