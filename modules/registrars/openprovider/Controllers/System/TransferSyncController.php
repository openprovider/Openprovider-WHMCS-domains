<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;

/**
 * Class TransferSyncController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class TransferSyncController extends BaseController
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

    /**
     * Synchronise the transfer status.
     *
     * @param $params
     * @return array
     */
    public function sync($params)
    {
        if(isset($param['domainObj']))
        {
            $params['sld'] = $params['domainObj']->getSecondLevel();
            $params['tld'] = $params['domainObj']->getTopLevel();
        }

        try
        {
            // get data from op
            $api                = new \OpenProvider\API\API();
            $api->setParams($params);
            $domain             =   new \OpenProvider\API\Domain(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $opInfo             =   $api->retrieveDomainRequest($domain);

            if($opInfo['status'] == 'ACT')
            {
                return array
                (
                    'completed'     =>  true,
                    'expirydate'    =>  date('Y-m-d', strtotime($opInfo['renewalDate'])),
                );
            }

            return array();
        }
        catch (\Exception $ex)
        {
            return array
            (
                'error' =>  $ex->getMessage()
            );
        }

        return [];
    }
}