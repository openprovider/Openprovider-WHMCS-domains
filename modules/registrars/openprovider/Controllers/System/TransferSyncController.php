<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\OpenProvider;
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
     * Synchronise the transfer status.
     *
     * @param $params
     * @return array
     */
    public function sync($params)
    {
        if (isset($param['domainObj'])) {
            $params['sld'] = $params['domainObj']->getSecondLevel();
            $params['tld'] = $params['domainObj']->getTopLevel();
        }

        $domainModel = \OpenProvider\WhmcsRegistrar\Models\Domain::where('id', $params['domainid'])->first();

        try {
            // get data from op
            $api                = $this->openProvider->api;
            $params['Password'] = html_entity_decode($params['Password']);
            $domain = new Domain(array(
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ));

            $opInfo = $api->retrieveDomainRequest($domain);

            if ($opInfo['status'] == 'ACT') {
                if ($domainModel->check_renew_domain_setting_upon_completed_transfer() == true) {
                    $api->renewDomain($domain, $params['regperiod']);

                    // Fetch updated information
                    $opInfo = $api->retrieveDomainRequest($domain);
                }

                return array
                (
                    'completed'  => true,
                    'expirydate' => Carbon::createFromFormat('Y-m-d H:i:s', $opInfo['renewalDate'], 'Europe/Amsterdam')->toDateString()
                );
            }

            return array();
        } catch (\Exception $ex) {
            return array
            (
                'error' => $ex->getMessage()
            );
        }

        return [];
    }
}