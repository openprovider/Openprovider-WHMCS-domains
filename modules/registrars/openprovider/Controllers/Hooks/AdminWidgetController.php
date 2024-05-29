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
    const BALANCE_WIDGET_FILE = '/BalanceWidgetNew.php';

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
        $this->copyCustomWidgets();
    }

    private function copyCustomWidgets()
    {
        $destinationLocation = $GLOBALS['whmcsAppConfig']->getRootDir() . '/modules/widgets' . self::BALANCE_WIDGET_FILE;
        $sourceLocation = $GLOBALS['whmcsAppConfig']->getRootDir() . '/modules/registrars/openprovider/Controllers/Hooks/Widgets' . self::BALANCE_WIDGET_FILE;
        if (
            !file_exists($destinationLocation) ||
            empty(file_get_contents($destinationLocation))
        ) {
            // Attempt to copy BalanceWidgetNew.php file into the modules/widgets folder
            if (!copy($sourceLocation, $destinationLocation)) {
                logModuleCall('openprovider', 'copybalancewidgetfile', null, "Balance Widget error! Failed to add BalanceWidgetNew.php to /modules/widgets directory. Please manually upload the contents of '<Module directory>/registrars/openprovider/Controllers/Hooks/Widgets' to the /modules/widget folder of your WHMCS folder i.e. '<your WHMCS directory>/modules/widgets'", null, null);
            }
        }
    }
}
