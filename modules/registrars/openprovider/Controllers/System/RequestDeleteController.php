<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class RequestDelete
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class RequestDeleteController extends BaseController
{
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiInterface
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
    }

    /**
     * @param $params
     * @return array
     */
    public function request($params): array
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();
        $values        = [];

        try {
            $domain = $this->domain;
            $domain->load([
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ]);

            $domainOp = $this->apiHelper->getDomain($domain);
            $this->apiHelper->deleteDomain($domainOp['id']);
        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}
