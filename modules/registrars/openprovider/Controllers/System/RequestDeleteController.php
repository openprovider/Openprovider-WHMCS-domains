<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\Domain;
use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class RequestDelete
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RequestDeleteController extends BaseController
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

    public function request($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $values = array();

        try
        {
            $api->deleteDomainRequest($this->domain);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}