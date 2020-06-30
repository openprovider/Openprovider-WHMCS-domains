<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use OpenProvider\API\APIConfig;
use WeDevelopCoffee\wPower\Models\Registrar;
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
        if(!strpos($_SERVER['PHP_SELF'], 'configregistrars.php'))
        {
            // We are not on the admin page. Let's use a cached version of this.
            $cached_dns_template = Registrar::getByKey('openprovider', 'dnstemplate_cache');

            if($cached_dns_template != '')
            {
                $configarray['dnsTemplate'] = json_decode($cached_dns_template, true);
                return $configarray;
            }
        }

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

        Registrar::updateByKey('openprovider', 'dnstemplate_cache', json_encode($configarray['dnsTemplate']));

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
            "version"   => array
            (
                "FriendlyName"  => "Module Version",
                "Type"          => "text",
                "Description"   => APIConfig::getModuleVersion() . "<style>input[name='version']{display: none;}</style>",
            ),
            "OpenproviderAPI"   => array
            (
                "FriendlyName"  => "Openprovider URL",
                "Type"          => "text",
                "Size"          => "60",
                "Description"   => "Include https://",
                "Default"       => "https://rcp.openprovider.eu"
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
            "require_op_dns_servers"          => array
            (
                "Type"          => "yesno",
                "FriendlyName"  => "Require Openprovider DNS servers for DNS management",
                "Description"   => "Show a warning when DNS management is enabled but the Openprovider nameservers are not used.",
                "Default"       => "yes"
            ),
            "sync_settings" => array
            (
                "FriendlyName"  => "Synchronisation settings",
                "Type"          => "text",
                "Description"   => $this->getSyncDescription(),
                "Default"       => ""
            ),
            "syncUseNativeWHMCS" => array
            (
                "FriendlyName"  => "Use the native WHMCS synchronisation?",
                "Type"          => "yesno",
                "Description"   => "Up to V3.3, Openprovider had an internal synchronisation system. Set to no if you want to use the openprovider's own synchronisation engine.",
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
            "syncExpiryDate" => array
            (
                "FriendlyName"  => "Synchronize Expiry date from Openprovider?",
                "Type"          => "yesno",
                "Description"   => "<strong>Setting applies on non-native synchronisation only.</strong> - Expiry dates will be synced from Openprovider to WHMCS.",
                "Default"       => "yes"
            ),
            "updateNextDueDate" => array
            (
                "FriendlyName"  => "Synchronize due-date with offset?",
                "Type"          => "yesno",
                "Description"   => "<strong>Setting applies on non-native synchronisation only.</strong> - WHMCS due dates will be synchronized using the due-date offset.",
            ),
            "nextDueDateOffset" => array
            (
                "FriendlyName"  => "Due-date offset",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "<strong>Setting applies on non-native synchronisation only.</strong> - Number of days to set the WHMCS due date before the Openprovider expiration date.",
                "Default"       => "3"
            ),
            "nextDueDateUpdateMaxDayDifference" => array
            (
                "FriendlyName"  => "Due-date max difference in days",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "<strong>Setting applies on non-native synchronisation only.</strong> - When the difference in days between the expiry date and next due date is more than this number, the next due date is not updated. This is required to prevent that the next due date is updated when the domain is automatically renewed, but not paid for. Or, when a domain is paid for 10 years in advance but is not renewed for 10 years.",
                "Default"       => "100"
            ),
            "updateInterval"     => array
            (
                "FriendlyName"  => "Update interval",
                "Type"          => "text",
                "Size"          => "2",
                "Description"   => "The minimum number of hours between each domain synchronization.",
                "Default"       => "2"
            ),
            "domainProcessingLimit"     => array
            (
                "FriendlyName"  => "Domain process limit",
                "Type"          => "text",
                "Size"          => "4",
                "Description"   => "Maximum number of domains processed each time domain sync runs.",
                "Default"       => "200"
            ),
            "sendEmptyActivityEmail" => array
            (
                "FriendlyName"  => "Send empty activity reports?",
                "Type"          => "yesno",
                "Size"          => "20",
                "Description"   => "Receive emails from domain sync even if no domains were updated.",
                "Default"       => "no"
            ),
            "various_settings" => array
            (
                "FriendlyName"  => "Various settings",
                "Type"          => "text",
                "Description"   => $this->getVariousSettings(),
                "Default"       => ""
            ),
            "renewTldsUponTransferCompletion" => array
            (
                "FriendlyName"  => "Renew domains upon transfer completion",
                "Type"          => "text",
                "Size"          => "20",
                "Description"   => "<i>Enter the TLDs - without a leading dot - like nl,eu with a comma as a separator.</i><br>Some TLDs offer a free transfer, like the nl TLD. If the expiration date is within 30 days, the domain may expiry if the renewal is not performed in time. This setting will always try to renew the TLD. ",
                "Default"       => ""
            ),
            "useNewDnsManagerFeature" => array
            (
                "FriendlyName"  => "Use new DNS feature?",
                "Type"          => "yesno",
                "Size"          => "20",
                "Description"   => "Only enable this when OpenProvider has enabled this for your account.",
                "Default"       => ""
            ),

        );
    }

    /**
     * Return the sync settings description.
     * @return string
     */
    protected function getSyncDescription()
    {
        $syncDescription = <<<EOF
<style>
#openproviderconfig input[name="sync_settings"] {
    display: none;
}
#openproviderconfig h1
{
    margin-top: 20px;
    color: #bb1929;
}
#openproviderconfig .op-disabled,
#openproviderconfig .op-disabled label
{
    text-decoration: line-through;
    opacity: 0.8;
}
</style>
<h1>Synchronisation options</h1>
<p>Choose what settings you want to synchronise between WHMCS and Openprovider</p>
<script>
jQuery(document).ready(function(){
    jQuery.fn.extend({
        op_update_sync_options: function()
        {
            // Comment this for showing the options with a strikethrough and uncomment the following part.
            jQuery("#openproviderconfig input[name='syncExpiryDate']").parent().parent().parent().toggle();
            jQuery("#openproviderconfig input[name='updateNextDueDate']").parent().parent().parent().toggle();
            jQuery("#openproviderconfig input[name='nextDueDateOffset']").parent().parent().toggle();
            jQuery("#openproviderconfig input[name='nextDueDateUpdateMaxDayDifference']").parent().parent().toggle();
            
            // Uncomment this for showing the options with a strikethrough
            // jQuery("#openproviderconfig input[name='syncExpiryDate']").parent().parent().parent().toggleClass('op-disabled');
            // jQuery("#openproviderconfig input[name='syncExpiryDate']").prop('disabled', function(i, v) { return !v; });
            // jQuery("#openproviderconfig input[name='updateNextDueDate']").parent().parent().parent().toggleClass('op-disabled');
            // jQuery("#openproviderconfig input[name='updateNextDueDate']").prop('disabled', function(i, v) { return !v; });
            // jQuery("#openproviderconfig input[name='nextDueDateOffset']").parent().parent().toggleClass('op-disabled');
            // jQuery("#openproviderconfig input[name='nextDueDateOffset']").prop('disabled', function(i, v) { return !v; });
            // jQuery("#openproviderconfig input[name='nextDueDateUpdateMaxDayDifference']").parent().parent().toggleClass('op-disabled');
            // jQuery("#openproviderconfig input[name='nextDueDateUpdateMaxDayDifference']").prop('disabled', function(i, v) { return !v; });
        },
    });
    
    if(jQuery("#openproviderconfig input[name='syncUseNativeWHMCS']").is(':checked'))
        jQuery('#openproviderconfig').op_update_sync_options();
    
    jQuery("#openproviderconfig input[name='syncUseNativeWHMCS']").change(function(){
        jQuery(this).op_update_sync_options();
    });
});
</script>
</script>
EOF;
        return $syncDescription;
    }

    /**
     * Return the various settings description.
     * @return string
     */
    protected function getVariousSettings()
    {
        $syncDescription = <<<EOF
<style>
#openproviderconfig input[name="various_settings"] {
    display: none;
}
</style>
<h1>Various settings</h1>
EOF;
        return $syncDescription;
    }
}