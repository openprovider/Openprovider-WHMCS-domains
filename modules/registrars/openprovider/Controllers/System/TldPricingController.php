<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
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

        try {
            $this->API->setParams($params);
            $extensionData = $this->API->getTldsAndPricing();
        } catch ( \Exception $e)
        {
            return ['error' => 'This error occurred: ' . $e->getMessage()];
        }

        $results = new ResultsList;

        foreach ($extensionData['results'] as $extension) {
            // All the set methods can be chained and utilised together.
            $item = (new ImportItem)
                ->setExtension($extension['name'])
                ->setMinYears($extension['minPeriod'])
                ->setMaxYears($extension['maxPeriod'])
                ->setCurrency($extension['prices']['resellerPrice']['reseller']['currency'])
                ->setEppRequired($extension['isTransferAuthCodeRequired']);

            if(isset($extension['prices']['createPrice']['reseller']['price']))
                $item->setRegisterPrice($extension['prices']['createPrice']['reseller']['price']);

            if(isset($extension['prices']['renewPrice']['reseller']['price']))
                $item->setRenewPrice($extension['prices']['renewPrice']['reseller']['price']);

            if(isset($extension['softQuarantinePeriod']) && isset($extension['prices']['softRestorePrice']['reseller']['price']))
            {
                $item->setGraceFeeDays($extension['softQuarantinePeriod']);//$extension['softQuarantinePeriod']);
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