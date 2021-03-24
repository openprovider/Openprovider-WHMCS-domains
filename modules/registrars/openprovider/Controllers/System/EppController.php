<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\ApiInterface;
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
     * @var API
     */
    private $API;
    /**
     * @var ApiInterface
     */
    private $apiClient;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->apiClient = $apiClient;
        $this->domain = $domain;
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

        try
        {
            $domain             =   $this->domain;
            $domain->load(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $args = [
                'domainNamePattern' => $domain->name,
                'extension' => $domain->extension
            ];
            $domainOp = $this->apiClient->call('searchDomainRequest', $args)->getData()['results'][0];
            $eppCode = $domainOp['authCode'];
            $values["eppcode"] = $eppCode ?? '';

            if(!$eppCode)
            {
                throw new Exception('EPP code is not set');
            }
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}
