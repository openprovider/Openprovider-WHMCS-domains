<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\BalanceWidget;
use WHMCS\Database\Capsule,
    OpenProvider\WhmcsRegistrar\src\OpenProvider;

/**
 * Class AdminWidgetController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class AdminWidgetController
{
    /**
    *
    *
    * @return
    */
    public function showBalance ($vars)
    {
        return new BalanceWidget();
    }
}
