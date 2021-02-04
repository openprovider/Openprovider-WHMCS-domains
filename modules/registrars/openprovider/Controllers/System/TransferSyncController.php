<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use OpenProvider\API\JsonAPI;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use Carbon\Carbon;

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
    public function __construct(Core $core, JsonAPI $API, Domain $domain)
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

        $domainModel = \OpenProvider\WhmcsRegistrar\Models\Domain::where('id', $params['domainid'])->first();

        try
        {
            // get data from op
            $api                = $this->API;
            $api->setParams($params);

            $domain             =   new \OpenProvider\API\Domain(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $opInfo             =   $api->getDomainRequest($domain);

            if($opInfo['status'] == 'ACT')
            {
                if($domainModel->check_renew_domain_setting_upon_completed_transfer() == true)
                {
                    $api->renewDomainRequest($domain, $params['regperiod']);

                    // Fetch updated information
                    $opInfo             =   $api->getDomainRequest($domain);
                }

                return array
                (
                    'completed'     =>  true,
                    'expirydate'    =>  Carbon::createFromFormat('Y-m-d H:i:s',$opInfo['renewal_date'], 'Europe/Amsterdam')->toDateString()
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