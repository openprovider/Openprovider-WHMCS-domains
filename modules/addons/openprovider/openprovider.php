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
    return array(
        'name' => 'OpenProvider', // Display name for your module
        'description' => 'OpenProvider domain addon.', // Description displayed within the admin interface
        'author' => 'OpenProvider', // Module author name
        'language' => 'english', // Default language
        'version' => '1.0', // Version number
    );
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

// Activation
function openprovider_activate()
{

    try {
        if(!Capsule::schema()->hasTable('op_correctioninvoices'))
        {
            Capsule::schema()->table(
                'tbldomains',
                function ($table) {
                    $table->float('op_correctioninvoices', 10,4);
                }
            );
        }
        if(!Capsule::schema()->hasTable('tblinvoiceitems'))
        {
            Capsule::schema()->table('tblinvoiceitems', function (Blueprint $table) {
                $table->integer('op_correctioninvoices');
            });
        }
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
    
    return array(
        'status' => 'success', // Supported values here include: success, error or inf
    );
}


// Deactivation
function openprovider_deactivate()
{

    try {
        Capsule::schema()->table(
            'tbldomains',
            function ($table) {
                $table->dropColumn('op_correctioninvoices');
            }
        );
        Capsule::schema()->table(
            'tblinvoiceitems',
            function ($table) {
                $table->dropColumn('op_correctioninvoices');
            }
        );
    } catch (\Exception $e) {
        // Ignore errors, if the column does not exist it is good enough.
    }
    
    return array(
        'status' => 'success', // Supported values here include: success, error or inf
    );
}
