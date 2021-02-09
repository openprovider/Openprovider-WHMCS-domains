<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\Domain;
use OpenProvider\OpenProvider;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

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
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        try {
            $lockStatus = $api->getDomainRegistrarLockRequest($this->domain);
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $values = array();

        try {
            $lockStatus = $params["lockenabled"] == "locked";

            $api->updateDomainRegistrarLockRequest($this->domain, $lockStatus);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}