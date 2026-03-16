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
    public function showBalanceWidget()
    {
        $this->cleanupLegacyBalanceWidget();

        return new BalanceWidget();
    }

    private function cleanupLegacyBalanceWidget(): void
    {
        $legacyWidgetPath =
            $GLOBALS['whmcsAppConfig']->getRootDir()
            . '/modules/widgets/'
            . 'BalanceWidget.php';

        if (file_exists($legacyWidgetPath)) {
            @unlink($legacyWidgetPath);

            logModuleCall(
                'OpenProvider NL',
                'cleanupLegacyBalanceWidget',
                null,
                'Removed legacy BalanceWidget file to prevent duplicate widgets.',
                null,
                null
            );
        }
    }
}
