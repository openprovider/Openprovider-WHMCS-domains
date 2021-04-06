<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class EppController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class EppController extends BaseController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
        $this->domain    = $domain;
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

        $domain             =   $this->domain;
        $domain->load(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $domainOp = $this->apiHelper->getDomain($domain);
        $eppCode = $domainOp['authCode'];
        $values["eppcode"] = $eppCode ?? '';

        if(!$eppCode)
        {
            throw new Exception('EPP code is not set');
        }

        return $values;
    }
}
