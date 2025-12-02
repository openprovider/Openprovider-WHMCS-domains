<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class RegistrarLockController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RegistrarLockController extends BaseController
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
    public function __construct(Core $core, ApiHelper $apiHelper, Domain $domain)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
        $this->domain    = $domain;
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
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $lockStatus = $this->apiHelper->getDomain($domain)['isLocked'];
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
            $domain = $this->domain;
            $domain->load(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));
            $domainOp = $this->apiHelper->getDomain($domain);
            $this->apiHelper->updateDomain($domainOp['id'], [
                'isLocked' => $params["lockenabled"] == "locked",
            ]);
            $values['success'] = true;
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}
