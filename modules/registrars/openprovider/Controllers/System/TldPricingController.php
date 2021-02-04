<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use OpenProvider\WhmcsRegistrar\src\TldPriceCache;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;

/**
 * Class TldController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class TldPricingController extends BaseController
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
     * @param $params
     * @return array|ResultsList
     */
    public function get($params)
    {
        // Perform API call to retrieve extension information
        // A connection error should return a simple array with error key and message
        // return ['error' => 'This error occurred',];

        $tldPriceCache = new TldPriceCache();

        if(!$tldPriceCache->has())
            throw new \Exception('The cron for downloading the TLD prices was not run yet. You can run the prices download manually here. If this fails, it is likely that your WHMCS installation does not support a long execution time. <a href="https://github.com/openprovider/OP-WHMCS7/blob/master/docs/TLD_Pricing_sync_Utility.md" target="_blank">Check the manual to run the cron command instead</a>.');

        $extensionData = $tldPriceCache->get();

        $results = new ResultsList;

        foreach ($extensionData['results'] as $extension) {
            // All the set methods can be chained and utilised together.
            $item = (new ImportItem)
                ->setExtension($extension['name'])
                ->setMinYears($extension['min_period'])
                ->setMaxYears($extension['max_period'])
                ->setCurrency($extension['prices']['reseller_price']['reseller']['currency'])
                ->setEppRequired($extension['is_transfer_auth_code_required']);

            if(isset($extension['prices']['reseller_price']['reseller']['price']))
                $item->setRegisterPrice($extension['prices']['reseller_price']['reseller']['price']);
            elseif(isset($extension['prices']['create_price']['reseller']['price']))
                $item->setRegisterPrice($extension['prices']['create_price']['reseller']['price']);

            if(isset($extension['prices']['renew_price']['reseller']['price']))
                $item->setRenewPrice($extension['prices']['renew_price']['reseller']['price']);

            if(isset($extension['soft_quarantine_period']) && isset($extension['prices']['soft_restore_price']['reseller']['price']))
            {
                $item->setGraceFeeDays($extension['soft_quarantine_period']);

                $item->setGraceFeePrice($extension['prices']['soft_restore_price']['reseller']['price']);
            }
            else
                $item->setGraceFeePrice(0);

            if(isset($extension['quarantine_period']) && isset($extension['prices']['restore_price']['reseller']['price'])) {
                $item->setRedemptionFeePrice($extension['prices']['restore_price']['reseller']['price']);
                $item->setRedemptionFeeDays($extension['quarantine_period']);
            }
            else
                $item->setRedemptionFeePrice(0);

            if($extension['transfer_available'])
                $item->setTransferPrice($extension['prices']['transfer_price']['reseller']['price']);

            $results[] = $item;
        }

        return $results;
    }
}