<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\OpenProvider;
use OpenProvider\API\Domain;

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
    public function __construct(Core $core, OpenProvider $openProvider, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = $openProvider;
        $this->domain       = $domain;
    }

    public function request($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();
        $values        = array();

        try {
            $api = $this->openProvider->api;
            $domain = new Domain(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $api->requestDelete($domain);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}