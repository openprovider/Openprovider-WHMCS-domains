<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\OpenProvider;
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
    public function __construct(Core $core, OpenProvider $openProvider, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = $openProvider;
        $this->domain       = $domain;
    }

    /**
     * Get the Epp code.
     * @param $params
     * @return array
     */
    public function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $values = array();

        try {
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $api = $this->openProvider->api;
            $eppCode = $api->getEPPCode($domain);

            if (!$eppCode) {
                throw new Exception('EPP code is not set');
            }
            $values["eppcode"] = $eppCode ? $eppCode : '';
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}