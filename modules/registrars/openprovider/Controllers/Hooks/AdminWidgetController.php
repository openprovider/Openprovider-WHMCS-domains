<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\BalanceWidget;

/**
 * Class AdminWidgetController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class AdminWidgetController
{
    public function showBalance ($vars)
    {
        return new BalanceWidget();
    }
}
