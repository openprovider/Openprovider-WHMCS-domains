<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;

/**
 * Class RequestDelete
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RequestDeleteController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, Domain $domain)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;
    }

    public function request($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();
        $values = array();

        try
        {
            $api                =   new \OpenProvider\API\API();
            $api->setParams($params);
            $domain             =   new \OpenProvider\API\Domain(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $api->requestDelete($domain);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}