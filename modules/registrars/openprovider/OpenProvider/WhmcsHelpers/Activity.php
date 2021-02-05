<?php
namespace OpenProvider\WhmcsHelpers;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Models\Registrar;

/**
 * Activity log
 * WhmcsHelper
 *
 * @copyright Copyright (c) WeDevelop.coffee 2018
 */

class Activity
{
    /**
     * Array with all logged activities.
     *
     * @var array
     */
    protected static $activity_log;

    /**
     * Log the $action with the correct message into WHMCS and prepare for the final report.
     *
     * @param $activity
     * @param $data
     * @param $moduleLog = false
     */
    public static function log($activity, $data, $moduleLog = false)
    {
        $log_entry = self::generate_log_entry($activity, $data);

        self::$activity_log[$activity][] = [
            'log_entry'     => $log_entry,
            'data'          => $data
        ];

        if($moduleLog == false)
            logActivity($log_entry, 0);
        else
        {
            /**
             * Log module call.
             *
             * @param string $module The name of the module
             * @param string $action The name of the action being performed
             * @param string|array $requestString The input parameters for the API call
             * @param string|array $responseData The response data from the API call
             * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
             * @param array $replaceVars An array of strings for replacement
             */
            $replaceVars = $data['replaceVars'];
            $replaceVars [] = Registrar::getByKey('openprovider', 'Username');
            $replaceVars [] = Registrar::getByKey('openprovider','Password');

            logModuleCall('openprovider', $activity, $data['requestString'], $data['responseData'], $data['processedData'], $replaceVars);
        }
    }

    /**
     * Generate a human error message.
     *
     * @param $activity
     * @param $data
     */
    public static function generate_log_entry($activity, $data)
    {
        switch($activity)
        {
            case 'update_invoice_next_due_date':
                $log_entry = 'Updated next due date for domain ' . $data['domain'] .' from '. $data['old_date'] . ' to ' . $data['new_date'] .' on Invoice ID: '.$data['invoiceid'];
                break;

            case 'update_domain_empty_next_due_date':
                $log_entry = 'Updated empty next due date for domain ' . $data['domain'] .' from '. $data['old_date'] . ' to ' . $data['new_date'];

            case 'update_domain_next_due_date':
                $log_entry = 'Updated next due date for domain ' . $data['domain'] .' from '. $data['old_date'] . ' to ' . $data['new_date'];

                if(isset($activity['data'] [ 'old_due_date_too_late' ]))
                    $log_entry .= ' Original next due date was too late ('.($activity['data'] [ 'old_due_date_too_late' ] * -1).' days)';
                elseif(isset($activity['data'] [ 'old_due_date_too_early' ]))
                    $log_entry .= ' Original next due date was too early ('.($activity['data'] [ 'old_due_date_too_early' ] * -1).' days)';
                break;

            case 'update_domain_expiry_date':
                $log_entry = 'Updated expiry date for domain ' . $data['domain'] .' from '. $data['old_date'] . ' to ' . $data['new_date'];
                break;

            case 'update_domain_status':
                $log_entry = 'Updated expired domain status ' . $data['domain'] .' from '. $data['old_status'] . ' to ' . $data['new_status'];
                break;

            case 'update_autorenew_setting':
                $log_entry = 'Updated domain auto renewal at provider for domain ' . $data['domain'] .' from '. $data['old_setting'] . ' to ' . $data['new_setting'];
                break;

            case 'activity_email_sent':
                $log_entry = 'Domain sync activity e-mailed to admins. Subject: "' . $data['subject'] .'"';
                break;

            case 'update_identity_protection_setting':
                $log_entry = 'Updated domain whois identity protection at provider for domain ' . $data['domain'] .' from '. $data['old_setting'] . ' to ' . $data['new_setting'];
                break;

            case 'activity_email_not_sent':
                $log_entry = 'Domain sync activity NOT e-mailed to admins. Subject: "' . $data['subject'] .'"';
                break;

            case 'unexpected_error':
                $log_entry = 'Error while syncing status for '.$data['domain'].'. OpenProvider API response: ' . $data['message'] .'"';
                break;

            default:
                $log_entry = $activity;
                break;
        }

        return $log_entry;
    }

    /**
     * Generate an e-mail report and send this to configured e-mail address.
     */
    public static function send_email_report()
    {
        if(empty(self::$activity_log) && Configuration::getOrDefault('sendEmptyActivityEmail') != true)
            return;

        $command    = 'SendAdminEmail';
        $subject    = 'OpenProvider Activity E-mail';
        $postData   = array(
            'customsubject' => $subject,
            'custommessage' => self::generate_email()

        );
        $adminUsername = General::get_admin_user(); // Optional for WHMCS 7.2 and later

        $results = localAPI($command, $postData, $adminUsername);

        if($results['result'] == 'success')
            self::log('activity_email_sent', ['admin' => $adminUsername, 'subject' => $subject]);
        else
            self::log('activity_email_not_sent', ['admin' => $adminUsername, 'subject' => $subject]);
    }

    /**
     * Generate the status e-mail
     *
     * @return string $email
     */
    private static function generate_email()
    {
        $setting['syncExpiryDate'] = Configuration::getOrDefault('syncExpiryDate', true);
        $setting['syncDomainStatus'] = Configuration::getOrDefault('syncDomainStatus', true);
        $setting['syncAutoRenewSetting'] = Configuration::getOrDefault('syncAutoRenewSetting', true);
        $setting['syncIdentityProtectionToggle'] = Configuration::getOrDefault('syncIdentityProtectionToggle', true);
        $setting['updateNextDueDate'] = Configuration::getOrDefault('updateNextDueDate', false) ;

        $email = "<p>Dear Administrator,<br>
        <br>\n
        Please find the domain synchronisation update below for your Openprovider domains.<br>\n<br>\n";

        // Did we find unexpected settings?
        if(isset(self::$activity_log['unexpected_error']))
        {
            $email .= "
             <font color=\"red\">ERRORS: Please check manually the following domains:</font>
             <table>
             <tr>
                 <td>Domain</td>
                 <td>Message</td>
             </tr>";
            foreach (self::$activity_log['unexpected_error'] as $activity) {
                $email .= "<tr>
                     <td>" . $activity['data'] ['domain'] . "</td>
                     <td>" . $activity['data'] ['message'] . "</td>
                 </tr>\n";
            }
            $email .= "</table>\n";
        }

        // Expiry
        if($setting['syncExpiryDate'] == true)
        {
            $email .= "
        The following domains have been processed for <strong>expiry date</strong> updates:</p>
        <table>
        <tr>
            <td>Domain</td>
            <td>Old date</td>
            <td>New date</td>
        </tr>";
            foreach (self::$activity_log['update_domain_expiry_date'] as $activity) {
                $email .= "<tr>
                <td>" . $activity['data'] ['domain'] . "</td>
                <td>" . $activity['data'] ['old_date'] . "</td>
                <td>" . $activity['data'] ['new_date'] . "</td>
            </tr>\n";
            }

            $email .= "</table>\n";
        }

        // Due date
        if(isset(self::$activity_log['update_domain_empty_next_due_date']) && !empty(self::$activity_log['update_domain_empty_next_due_date']))
        {
            // First, check which domains have an updated invoice due date.
            $updated_invoice_dates = [];
            foreach (self::$activity_log['update_domain_empty_next_due_date'] as $activity) {
                $updated_invoice_dates[$activity['data'] ['domain']] = true;
            }

            $email .= "<p>
        The following domains had 00/00/0000 as next due dates. This usually happens after a completed registration or transfer.</p>
        <table>
        <tr>
            <td>Domain</td>
            <td>Old date</td>
            <td>New date</td>
        </tr>";
            foreach (self::$activity_log['update_domain_empty_next_due_date'] as $activity) {
                if (isset($updated_invoice_dates[$activity['data'] ['domain']]))
                    $invoice_update = 'yes';
                else
                    $invoice_update = 'no';

                $email .= "<tr>
                <td>" . $activity['data'] ['domain'] . "</td>
                <td>" . $activity['data'] ['old_date'] . "</td>
                <td>" . $activity['data'] ['new_date'] . "</td>
                <td>" . $invoice_update . "</td>
                ";


                $email .= ' <td></td>';

                $email .= "
            </tr>\n";
            }

            $email .= "</table>\n";
        }

        // Due date
        if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false && $setting['updateNextDueDate'] == true)
        {
            // First, check which domains have an updated invoice due date.
            $updated_invoice_dates = [];
            foreach (self::$activity_log['update_invoice_next_due_date'] as $activity) {
                $updated_invoice_dates[$activity['data'] ['domain']] = true;
            }

            $email .= "<p>
        The following domains have been processed for <strong>due date</strong> updates:</p>
        <table>
        <tr>
            <td>Domain</td>
            <td>Old date</td>
            <td>New date</td>
            <td>Invoice updated?</td>
            <td>Comments</td>
        </tr>";
            foreach (self::$activity_log['update_domain_next_due_date'] as $activity) {
                if (isset($updated_invoice_dates[$activity['data'] ['domain']]))
                    $invoice_update = 'yes';
                else
                    $invoice_update = 'no';

                $email .= "<tr>
                <td>" . $activity['data'] ['domain'] . "</td>
                <td>" . $activity['data'] ['old_date'] . "</td>
                <td>" . $activity['data'] ['new_date'] . "</td>
                <td>" . $invoice_update . "</td>
                ";

                if(isset($activity['data'] [ 'old_due_date_too_late' ]))
                    $email .= ' <td><strong><font color="red">Original next due date was too late ('.($activity['data'] [ 'old_due_date_too_late' ] * -1).' days)</font></strong></td>';
                elseif(isset($activity['data'] [ 'old_due_date_too_early' ]))
                    $email .= ' <td><strong><font color="red">Original next due date was too early ('.($activity['data'] [ 'old_due_date_too_early' ] * -1).' days)</font></strong></td>';
                else
                    $email .= ' <td></td>';

                $email .= "
            </tr>\n";
            }

            $email .= "</table>\n";
        }


        // Domain status
        if($setting['syncDomainStatus'] == true) {
            $email .= "
            The following domains have been processed for <strong>domain status</strong> updates:</p>
            <table>
            <tr>
                <td>Domain</td>
                <td>Old status</td>
                <td>New status</td>
            </tr>";
            foreach (self::$activity_log['update_domain_status'] as $activity) {
                $email .= "<tr>
                    <td>" . $activity['data'] ['domain'] . "</td>
                    <td>" . $activity['data'] ['old_status'] . "</td>
                    <td>" . $activity['data'] ['new_status'] . "</td>
                </tr>\n";
            }

            $email .= "</table>\n";
        }

        // Domain auto renew setting
        if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false && $setting['syncAutoRenewSetting'] == true) {
            $email .= "
            The following domains have been processed for <strong>domain autorenew</strong> updates:</p>
            <table>
            <tr>
                <td>Domain</td>
                <td>Old setting</td>
                <td>New setting</td>
            </tr>";
            foreach (self::$activity_log['update_autorenew_setting'] as $activity) {
                $email .= "<tr>
                    <td>" . $activity['data'] ['domain'] . "</td>
                    <td>" . $activity['data'] ['old_setting'] . "</td>
                    <td>" . $activity['data'] ['new_setting'] . "</td>
                </tr>\n";
            }

            $email .= "</table>\n";
        }

        // Domain whois protection setting
        if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false && $setting['syncIdentityProtectionToggle'] == true) {
            $email .= "
            The following domains have been processed for <strong>domain whois privacy protection</strong> updates:</p>
            <table>
            <tr>
                <td>Domain</td>
                <td>Old setting</td>
                <td>New setting</td>
            </tr>";
            foreach (self::$activity_log['update_identity_protection_setting'] as $activity) {
                $email .= "<tr>
                    <td>" . $activity['data'] ['domain'] . "</td>
                    <td>" . ($activity['data'] ['old_setting'] == 1 ? 'Enabled' : 'Disabled') . "</td>
                    <td>" . ($activity['data'] ['new_setting'] == 1 ? 'Enabled' : 'Disabled') . "</td>
                </tr>\n";
            }

            $email .= "</table>\n";
        }

        return $email;
    }


} // END class General
