<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\BalanceWidget;
use OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\CrossSellWidget;
use WHMCS\Database\Capsule;

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
        return new BalanceWidget();
    }

    public function showCrossSellWidget()
    {
        return new CrossSellWidget();
    }

    public function handleCrossSellDismiss($vars)
    {
        if (
            !isset($_GET['op_crosssell_action']) || $_GET['op_crosssell_action'] !== 'dismiss'
            || !isset($_GET['crosssell_product'])
        ) {
            return;
        }

        if (!isset($_GET['token'])) {
            return;
        }

        if (function_exists('\\check_token')) {
            try {
                \check_token('WHMCS.admin.default', true);
            } catch (\Throwable $e) {
                return;
            }
        } elseif (function_exists('\\verify_token') && !\verify_token('link', $_GET['token'])) {
            return;
        }

        $product = (string) $_GET['crosssell_product'];
        $validProducts = array_keys(CrossSellWidget::PRODUCTS);

        if (!in_array($product, $validProducts, true)) {
            header('Location: index.php');
            exit;
        }

        $moduleName = CrossSellWidget::PRODUCTS[$product]['module_name'];

        CrossSellWidget::ensureDismissTableExists();

        try {
            $existing = Capsule::table(CrossSellWidget::DISMISS_TABLE)
                ->where('module_name', $moduleName)
                ->first();

            $now = date('Y-m-d H:i:s');

            if ($existing) {
                Capsule::table(CrossSellWidget::DISMISS_TABLE)
                    ->where('module_name', $moduleName)
                    ->update([
                        'dismissed' => 1,
                        'updated_at' => $now,
                    ]);
            } else {
                Capsule::table(CrossSellWidget::DISMISS_TABLE)
                    ->insert([
                        'module_name' => $moduleName,
                        'dismissed' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        header('Location: index.php');
        exit;
    }
}
