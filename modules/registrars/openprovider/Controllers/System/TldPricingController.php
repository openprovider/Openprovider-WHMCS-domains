<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use OpenProvider\WhmcsRegistrar\src\Configuration;
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

        $advancedConfigurationMaxPeriod = Configuration::getOrDefault('maxRegistrationPeriod', 5);

        foreach ($extensionData['results'] as $extension) {
            if ($extension['minPeriod'] > $advancedConfigurationMaxPeriod) {
                $extension['maxPeriod'] = $extension['minPeriod'];
            } else {
                $extension['maxPeriod'] = $advancedConfigurationMaxPeriod;
            }

            // All the set methods can be chained and utilised together.
            $item = (new ImportItem)
                ->setExtension($extension['name'])
                ->setMinYears($extension['minPeriod'])
                ->setMaxYears($extension['maxPeriod'])
                ->setCurrency($extension['prices']['resellerPrice']['reseller']['currency'])
                ->setEppRequired($extension['isTransferAuthCodeRequired']);

            if(isset($extension['prices']['resellerPrice']['reseller']['price']))
                $item->setRegisterPrice($extension['prices']['resellerPrice']['reseller']['price']);
            elseif(isset($extension['prices']['createPrice']['reseller']['price']))
                $item->setRegisterPrice($extension['prices']['createPrice']['reseller']['price']);

            if(isset($extension['prices']['renewPrice']['reseller']['price']))
                $item->setRenewPrice($extension['prices']['renewPrice']['reseller']['price']);

            if(isset($extension['softQuarantinePeriod']) && isset($extension['prices']['softRestorePrice']['reseller']['price']))
            {
                $item->setGraceFeeDays($extension['softQuarantinePeriod']);

                $item->setGraceFeePrice($extension['prices']['softRestorePrice']['reseller']['price']);
            }
            else
                $item->setGraceFeePrice(0);

            if(isset($extension['quarantinePeriod']) && isset($extension['prices']['restorePrice']['reseller']['price'])) {
                $item->setRedemptionFeePrice($extension['prices']['restorePrice']['reseller']['price']);
                $item->setRedemptionFeeDays($extension['quarantinePeriod']);
            }
            else
                $item->setRedemptionFeePrice(0);

            if($extension['transferAvailable'])
                $item->setTransferPrice($extension['prices']['transferPrice']['reseller']['price']);

            $results[] = $item;
        }

        return $results;
    }
}
