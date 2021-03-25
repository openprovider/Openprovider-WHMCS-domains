<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Core\Core;
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
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
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

        try {
            // get data from op
            $domain = $this->domain;
            $domain->load([
                'name'      => $params['sld'],
                'extension' => $params['tld']
            ]);
            $opInfo = $this->apiHelper->getDomain($domain);

            if ($opInfo['status'] == 'ACT') {
                if ($domainModel->check_renew_domain_setting_upon_completed_transfer() == true) {
                    $this->apiHelper->renewDomain($opInfo['id'], $params['regperiod']);

                    // Fetch updated information
                    $opInfo = $this->apiHelper->getDomain($domain);
                }

                $expiryDate = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $opInfo['renewalDate'],
                        'Europe/Amsterdam'
                    )->toDateString();

                return [
                    'completed'  => true,
                    'expirydate' => $expiryDate
                ];
            }

            return [];
        } catch (\Exception $ex) {
            return [
                'error' => $ex->getMessage()
            ];
        }

        return [];
    }
}
