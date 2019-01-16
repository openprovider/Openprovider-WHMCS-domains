<?php
/**
 * OpenProvider domain addon.
 * 
 * @copyright Copyright (c) WeDevelopCoffee 2018
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once('init.php');

// Include required classes.
use Illuminate\Database\Schema\Blueprint;
use WeDevelopCoffee\wPower\Controllers\AdminDispatcher;

// Standard classes
use WHMCS\Database\Capsule;

/**
 * Define OpenProvider configuration.
 * 
 * @return array
 */
function openprovider_config()
{
    openprovider_addon_migrate();

    return array(
        'name' => 'OpenProvider', // Display name for your module
        'description' => 'OpenProvider domain addon.', // Description displayed within the admin interface
        'author' => 'OpenProvider', // Module author name
        'language' => 'english', // Default language
        'version' => '1.0', // Version number
    );
}

function openprovider_addon_migrate()
{
    try {
        if (Capsule::schema()->hasColumn('tbldomains', 'op_correctioninvoices')) {
            Capsule::schema()->table(
                'tbldomains',
                function ($table) {
                    $table->dropColumn('op_correctioninvoices');
                }
            );
        }
        if (Capsule::schema()->hasColumn('tblinvoiceitems', 'op_correctioninvoices')) {
            Capsule::schema()->table(
                'tblinvoiceitems',
                function ($table) {
                    $table->dropColumn('op_correctioninvoices');
                }
            );
        }
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Admin Area Output.
 *
 * @see OpenProvider\WhmcsDomainAddon\Lib\AdminControllerDispatcher
 *
 * @return string
 */
function openprovider_output($vars)
{
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = wLaunch(AdminDispatcher::class);
    $response = $dispatcher->dispatch($action, $vars);

    echo $response;
}
