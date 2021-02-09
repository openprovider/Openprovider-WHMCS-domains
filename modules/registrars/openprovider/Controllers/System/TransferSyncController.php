<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
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
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
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
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);

        $domainModel = \OpenProvider\WhmcsRegistrar\Models\Domain::where('id', $params['domainid'])->first();

        try {
            // get data from op
            $opInfo = $api->getDomainRequest($this->domain);

            if ($opInfo['status'] == 'ACT') {
                if ($domainModel->check_renew_domain_setting_upon_completed_transfer() == true) {
                    $api->renewDomainRequest($this->domain, $params['regperiod']);

                    // Fetch updated information
                    $opInfo = $api->getDomainRequest($this->domain);
                }

                return array
                (
                    'completed'  => true,
                    'expirydate' => Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $opInfo['renewal_date'],
                        'Europe/Amsterdam'
                    )->toDateString()
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