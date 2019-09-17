<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class ConfigController
 */
class ConfigController extends BaseController
{
    /**
     * @var API
     */
    private $API;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API)
    {
        parent::__construct($core);

        $this->API = $API;
    }

    /**
     * Generate the configuration array.
     * @param $params
     * @return array|mixed
     */
    public function getConfig($params)
    {
        // Get the basic data.
        $configarray = $this->getConfigArray();

        // Process any updated data.
        list($configarray, $params) = $this->parsePostInput($params, $configarray);

        // If we have some login data, let's try to login.
        if(isset($params['Password']) && isset($params['Username']) && isset($params['OpenproviderAPI']))
        {
            try
            {
                // Try to login and fetch the DNS template data.
                $configarray = $this->fetchDnsTemplates($params, $configarray);
            }
            catch (\Exception $ex)
            {
                // Failed to login. Generate a warning.
                $configarray = $this->generateLoginError($configarray);
            }
        }

        return $configarray;
    }

    /**
     * Process the latest post information as WHMCS does not provide the latest information by default.
     *
     * @param $params
     * @param array $configarray
     * @return array
     */
    protected function parsePostInput($params, array $configarray)
    {
        $x = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
        $filename = end($x);
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' && $filename == 'configregistrars.php') {
            foreach ($_REQUEST as $key => $val) {
                if (isset($configarray[$key])) {
                    // Prevent that we will overwrite the actual password with the stars.
                    if (substr($val, 0, 3) != '***') {
                        $params[$key] = $val;
                    }
                }
            }
        }

        return array ($configarray, $params);
    }

    /**
     * Try to login and fetch the DNS templates.
     *
     * @param $params
     * @param $configarray
     * @return mixed
     */
    protected function fetchDnsTemplates($params, $configarray)
    {
        if(isset($GLOBALS['op_registrar_module_config_dnsTemplate']))
        {
            $configarray['dnsTemplate'] = $GLOBALS['op_registrar_module_config_dnsTemplate'];
            return $configarray;
        }

        $api = $this->API;
        $api->setParams($params);
        $templates = $api->searchTemplateDnsRequest();

        if (isset($templates['total']) && $templates['total'] > 0) {
            $tpls = 'None,';
            foreach ($templates['results'] as $template) {
                $tpls .= $template['name'] . ',';
            }
            $tpls = trim($tpls, ',');

            $configarray['dnsTemplate'] = array
            (
                "FriendlyName" => "DNS Template",
                "Type" => "dropdown",
                "Description" => "DNS template will be used when a domain is created or transferred to your account",
                "Options" => $tpls
            );
        }

        $GLOBALS['op_registrar_module_config_dnsTemplate'] = $configarray['dnsTemplate'];

        return $configarray;
    }

    /**
     * Generate a login error message.
     *
     * @param $configarray
     * @return mixed
     */
    protected function generateLoginError($configarray)
    {
        //warn user that login failed
        $configarray['loginFailed'] = [
            'FriendlyName' => '<b><strong style="color:Tomato;">Login Unsuccessful:</strong></b>',
            'Description' => '<b><strong style="color:Tomato;">please ensure credentials and URL are correct</strong></b>'
        ];

        $configarray['Username']['FriendlyName'] = '<b><strong style="color:Tomato;">*Username</strong></b>';
        $configarray['Password']['FriendlyName'] = '<b><strong style="color:Tomato;">*Password</strong></b>';
        $configarray['OpenproviderAPI']['FriendlyName'] = '<b><strong style="color:Tomato;">*Openprovider URL</strong></b>';
        return $configarray;
    }

    /**
     * The configuration array base.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array
        (
            "OpenproviderAPI"   => array
            (
                "FriendlyName"  => "Openprovider URL",
                "Type"          => "text",
                "Size"          => "60",
                "Description"   => "Include https://",
                "Default"       => "https://"
            ),
            "OpenproviderPremium"   => array
            (
                "FriendlyName"  => "Support premium domains",
                "Description"   => "Yes <i>NOTE: Premium pricing must also be activated in WHMCS via Setup -> Products / Services -> Domain pricing</i>. <br><br><strong>WARNING</strong>: to prevent billing problems with premium domains, your WHMCS currency must be the same as the currency you use in Openprovider. Otherwise, you will be billed the premium fee but your client will be billed the non-premium fee due to a <a href=\"https://requests.whmcs.com/topic/major-bug-premium-domains-billed-incorrectly\" target=\"_blank\">bug in WHMCS.</a>",
                "Type"          => "yesno"
            ),
            "Username"          => array
            (
                "FriendlyName"  => "Username",
                "Type"          => "text",
                "Size"          => "20",
                "Description"   => "Openprovider login",
            ),
            "Password"          => array
            (
                "FriendlyName"  => "Password",
                "Type"          => "password",
                "Size"          => "20",
                "Description"   => "Openprovider password",
            ),
            "syncExpiryDate" => array
            (
                "FriendlyName"  => "Synchronize Expiry date from Openprovider?",
                "Type"          => "yesno",
                "Description"   => "Expiry dates will be synced from Openprovider to WHMCS..",
                "Default"       => "yes"
            ),
            "syncDomainStatus" => array
            (
                "FriendlyName"  => "Synchronize Domain status from Openprovider?",
                "Type"          => "yesno",
                "Description"   => "The domain status will be synced from Openprovider to WHMCS.",
                "Default"       => "yes"
            ),
            "syncAutoRenewSetting" => array
            (
                "FriendlyName"  => "Synchronize Auto renew setting to Openprovider?",
                "Type"          => "yesno",
                "Description"   => "The auto renewal will be synced to Openprovider from WHMCS.",
                "Default"       => "yes"
            ),
            "syncIdentityProtectionToggle" => array
            (
                "FriendlyName"  => "Synchronize Identity protection to Openprovider?",
                "Type"          => "yesno",
                "Description"   => "The identity protection setting will be synced to Openprovider from WHMCS.",
                "Default"       => "yes"
            ),
            "updateNextDueDate" => array
            (
                "FriendlyName"  => "Synchronize due-date with offset?",
                "Type"          => "yesno",
                "Description"   => "WHMCS due dates will be synchronized using the due-date offset ",
            ),
            "nextDueDateOffset" => array
            (
                "FriendlyName"  => "Due-date offset",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "Number of days to set the WHMCS due date before the Openprovider expiration date",
                "Default"       => "3"
            ),
            "nextDueDateUpdateMaxDayDifference" => array
            (
                "FriendlyName"  => "Due-date max difference in days",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "When the difference in days between the expiry date and next due date is more than this number, the next due date is not updated. This is required to prevent that the next due date is updated when the domain is automatically renewed, but not paid for. Or, when a domain is paid for 10 years in advance but is not renewed for 10 years.",
                "Default"       => "100"
            ),
            "updateInterval"     => array
            (
                "FriendlyName"  => "Update interval",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "The minimum number of hours between each domain synchronization",
                "Default"       => "2"
            ),
            "domainProcessingLimit"     => array
            (
                "FriendlyName"  => "Domain process limit",
                "Type"          => "text",
                "Size"          => "4",
                "Description"   => "Maximum number of domains processed each time domain sync runs",
                "Default"       => "200"
            ),
            "sendEmptyActivityEmail" => array
            (
                "FriendlyName"  => "Send empty activity reports?",
                "Type"          => "yesno",
                "Size"          => "20",
                "Description"   => "Receive emails from domain sync even if no domains were updated",
                "Default"       => "no"
            ),
        );
    }
}