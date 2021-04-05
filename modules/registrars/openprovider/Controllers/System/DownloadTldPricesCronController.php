<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
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
class DownloadTldPricesCronController extends BaseController
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
     * @var OpenProvider
     */
    private $openProvider;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, OpenProvider $openProvider)
    {
        parent::__construct($core);
        $this->openProvider = $openProvider;
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
            $extensionData = $this->openProvider->api->getTldsAndPricing();
        } catch ( \Exception $e)
        {
            return ['error' => 'This error occurred: ' . $e->getMessage()];
        }

        // Store the cache.
        $tldPriceCache = new TldPriceCache();
        $tldPriceCache->write($extensionData);

        return;
    }
}
