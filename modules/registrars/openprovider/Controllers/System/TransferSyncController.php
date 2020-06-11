<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Models\Registrar;
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

        $domainModel = \OpenProvider\WhmcsRegistrar\Models\Domain::where('id', $params['domainid'])->first();

        try
        {
            // get data from op
            $api                = new \OpenProvider\API\API();
            $params['Password'] = html_entity_decode($params['Password']);
            $api->setParams($params);
            $domain             =   new \OpenProvider\API\Domain(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $opInfo             =   $api->retrieveDomainRequest($domain);

            if($opInfo['status'] == 'ACT')
            {
                if($domainModel->check_renew_domain_setting_upon_completed_transfer() == true)
                {
                    $api->renewDomain($domain, $params['regperiod']);

                    // Fetch updated information
                    $opInfo             =   $api->retrieveDomainRequest($domain);
                }

                return array
                (
                    'completed'     =>  true,
                    'expirydate'    =>  Carbon::createFromFormat('Y-m-d H:i:s',$opInfo['renewalDate'], 'Europe/Amsterdam')->toDateString()
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

    /**
     * Check if the domain should be renewed.
     *
     * @param $domain
     */
    protected function check_renew_domain_setting_upon_completed_transfer($domain)
    {
        $setting_value = Registrar::getByKey('openprovider', 'renewTldsUponTransferCompletion', '');

        // When nothing was found; return false.
        if(count($setting_value) == 0
            || count($setting_value ) && $setting_value == '')
            return false;

        $tlds = explode(",",$setting_value);

        // We found it!
        if(in_array($domain->extension, $tlds))
            return true;

        // The domain TLD does not match with the renewal TLDs.
        return false;
    }
}
