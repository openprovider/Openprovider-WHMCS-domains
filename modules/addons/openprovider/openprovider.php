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

use WHMCS\Authentication\CurrentUser;
use OpenProvider\API\APIConfig;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Configuration;

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
function openprovider_output($params)
{
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    echo openprovider_addon_launch('admin')
        ->output($params, $action);
}


function openprovider_clientarea($vars)
{

    $core = openprovider_registrar_core();
    $core->launch();
    $launcher = openprovider_bind_required_classes($core->launcher);
    $apiHelper = $launcher->get(\OpenProvider\API\ApiHelper::class);
    
    $PAGE_TITLE  = 'DNSSEC Records';
    $PAGE_NAME   = 'DNSSEC Records';
    $MODULE_NAME = 'dnssec';

    $currentUser = new CurrentUser();
    $authUser = $currentUser->user();
    $selectedClient = $currentUser->client();

    if (!$authUser || !$selectedClient) {
        if (isset($_SERVER["HTTP_REFERER"])) {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
            return;
        }

        header("Location: " . Configuration::getServerUrl());
        return;
    }

    $domainId = $_GET['domainid'];
    $domain = Capsule::table('tbldomains')
        ->where('id', $domainId)
        ->where('userid', $selectedClient->id)
        ->first();

    if (!$domain) {
        if (isset($_SERVER["HTTP_REFERER"])) {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
            return;
        }

        header("Location: " . Configuration::getServerUrl());
        return;
    }

    $domainObj = DomainFullNameToDomainObject::convert($domain->domain);

    try {
        $core = openprovider_registrar_core();
        $core->launch();
        $launcher = openprovider_bind_required_classes($core->launcher);

        $apiHelper = $launcher->get(\OpenProvider\API\ApiHelper::class);
        $domainOp = $apiHelper->getDomain($domainObj);
        $dnssecKeys = $domainOp['dnssecKeys'];
        $isDnssecEnabled = $domainOp['isDnssecEnabled'];

        $openproviderNameserversCount = 0;
        foreach ($domainOp['nameServers'] as $nameServer) {
            if (!in_array($nameServer['name'], APIConfig::getDefaultNameservers())) {
                continue;
            }
            $openproviderNameserversCount++;
        }
    } catch (\Exception $e) {
        if (isset($_SERVER["HTTP_REFERER"])) {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
            return;
        }

        header("Location: " . Configuration::getServerUrl());
        return;
    }

    $apiUrlUpdateDnssecRecords = Configuration::getApiUrl('dnssec-record-update');
    $apiUrlTurnOnOffDnssec = Configuration::getApiUrl('dnssec-enabled-update');
    $jsModuleUrl = Configuration::getJsModuleUrl($MODULE_NAME);
    $cssModuleUrl = Configuration::getCssModuleUrl($MODULE_NAME);

    //logModuleCall('openprovider-addon', 'TEST Log', $apiUrlUpdateDnssecRecords, $_GET['domainid'], null, null);

    return array(
        'pagetitle' => $PAGE_TITLE,
        'breadcrumb' => array('index.php?m=openprovider' => $PAGE_NAME),
        'templatefile' => '/includes/templates/dnssec',
        'requirelogin' => true, # accepts true/false
        'forcessl' => false, # accepts true/false
        'vars' => array(
            'dnssecKeys' => $dnssecKeys,
            'isDnssecEnabled' => $isDnssecEnabled,
            'apiUrlUpdateDnssecRecords' => $apiUrlUpdateDnssecRecords,
            'apiUrlTurnOnOffDnssec' => $apiUrlTurnOnOffDnssec,
            'domainId' => $domainId,
            'jsModuleUrl' => $jsModuleUrl,
            'cssModuleUrl' => $cssModuleUrl
        ),
    );
}








