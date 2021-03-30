<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiInterface;
use OpenProvider\WhmcsRegistrar\src\TldPriceCache;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;

/**
 * Class TldController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DownloadTldPricesCronController extends BaseController
{
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiInterface $apiClient)
    {
        parent::__construct($core);
        $this->apiClient = $apiClient;
    }

    /**
     * @param $params
     * @return array|ResultsList
     */
    public function Download($params)
    {
        // Perform API call to retrieve extension information
        // A connection error should return a simple array with error key and message
        // return ['error' => 'This error occurred',];
        try {
            $extensionData = $this->apiClient->call('searchExtensionRequest')->getData();
        } catch (\Exception $e) {
            return ['error' => 'This error occurred: ' . $e->getMessage()];
        }

        // Store the cache.
        $tldPriceCache = new TldPriceCache();
        $tldPriceCache->write($extensionData);

        return;
    }
}
