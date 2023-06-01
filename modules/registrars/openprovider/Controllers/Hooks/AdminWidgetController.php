<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\XmlApiAdapter;
use OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\BalanceWidget;

/**
 * Class AdminWidgetController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class AdminWidgetController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var XmlApiAdapter
     */
    private $xmlApiAdapter;

    public function __construct(ApiHelper $apiHelper, XmlApiAdapter $xmlApiAdapter)
    {
        $this->apiHelper = $apiHelper;
        $this->xmlApiAdapter = $xmlApiAdapter;
    }

    public function showBalance ($vars)
    {
        return new BalanceWidget($this->apiHelper, $this->xmlApiAdapter);
    }
}
