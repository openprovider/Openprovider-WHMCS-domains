<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\OpenProvider;
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
     * @var OpenProvider
     */
    private $openProvider;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core)
    {
        parent::__construct($core);
        $this->openProvider = new OpenProvider();
    }

    /**
     * @return array|ResultsList
     */
    public function Download()
    {
        // Perform API call to retrieve extension information
        // A connection error should return a simple array with error key and message
        // return ['error' => 'This error occurred',];

        $api = $this->openProvider->getApi();

        try {
            $extensionData = $api->listTldsRequest();
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