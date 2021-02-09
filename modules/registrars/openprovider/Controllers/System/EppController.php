<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;

/**
 * Class EppController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class EppController extends BaseController
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
     * Get the Epp code.
     * @param $params
     * @return array
     */
    public function get($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);


        $values = array();

        try
        {
            $eppCode = $api->getDomainAuthCodeRequest($this->domain);

            if(!$eppCode)
            {
                throw new Exception('EPP code is not set');
            }
            $values["eppcode"] = $eppCode ? $eppCode : '';
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}