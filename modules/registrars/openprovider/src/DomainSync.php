<?php
namespace OpenProvider\WhmcsRegistrar\src;
use Carbon\Carbon;
use WeDevelopCoffee\wPower\Models\Registrar;
use WHMCS\Database\Capsule;
use OpenProvider\WhmcsHelpers\Domain;
use OpenProvider\API\Domain as api_domain;
use OpenProvider\WhmcsHelpers\Activity;
use OpenProvider\WhmcsHelpers\General;
use OpenProvider\WhmcsHelpers\DomainSync as helper_DomainSync;

/**
 * Helper to synchronize the domain info.
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class DomainSync
{
    /**
     * The domains that need to get processed
     *
     * @var string
     **/
    private $domains;

    /**
     * The WHMCS domain
     *
     * @var array
     **/
    private $objectDomain;

    /**
     * The OpenProvider object
     *
     * @var object
     **/
    private $OpenProvider;

    /**
     * The op domain object
     *
     * @var object
     **/
    private $op_domain_obj;

    /**
     * The op domain info
     *
     * @var object
     **/
    private $op_domain;

    /**
     * Update the domain data
     *
     * @var array
     **/
    private $update_domain_data;

    /**
     * An list with logs to be updated once the update has completed.
     *
     * @var array
     **/
    private $update_executed_logs;

    /**
     * All domains who need a signed whois identity protection contract
     *
     * @var array
     */
    private $unsigned_wpp_contract_domains;

    /**
     * @var \WeDevelopCoffee\wPower\Models\Domain
     */
    private $domain;

    /**
     * Init the class
     *
     * @return void
     **/
    public function __construct($limit = false)
    {
        // Check if there are domains missing from the DomainSync table
        helper_DomainSync::sync_DomainSync_table('openprovider');
        helper_DomainSync::remove_DomainSync_table_doubles('openprovider');

        $this->domain = new \WeDevelopCoffee\wPower\Models\Domain();

        // Get all unprocessed domains
        if($limit === false)
        {
            $limit = Configuration::getOrDefault('domainProcessingLimit', 200);
        }

        $this->get_unprocessed_domains($limit);
    }

    /**
     * Get the unprocessed domains
     *
     * @return void
     **/
    private function get_unprocessed_domains($limit)
    {
        $domains = helper_DomainSync::get_unprocessed_domains('openprovider', $limit);

        // Save the domains
        $this->domains = $domains;
    }

    /**
     * Check if there are domains to process
     *
     * @return boolean true when there are domains to process
     **/
    public function has_domains_to_process()
    {
        if(empty($this->domains))
            return false;
        else
            return true;
    }

    /**
     * Process the domains
     *
     * @return void
     **/
    public function process_domains()
    {
        $this->printDebug('PROCESSING DOMAINS...');
        $this->OpenProvider = new OpenProvider;
        $this->OpenProvider->api->getResellerStatistics('domainsync');

        $setting['syncExpiryDate'] = Configuration::getOrDefault('syncExpiryDate', true);
        $setting['syncDomainStatus'] = Configuration::getOrDefault('syncDomainStatus', true);
        $setting['syncAutoRenewSetting'] = Configuration::getOrDefault('syncAutoRenewSetting', true);
        $setting['syncIdentityProtectionToggle'] = Configuration::getOrDefault('syncIdentityProtectionToggle', true);
        $setting['updateNextDueDate'] = Configuration::getOrDefault('updateNextDueDate', false) ;
        $setting['syncUseNativeWHMCS'] = Configuration::getOrDefault('syncUseNativeWHMCS', false);

        foreach($this->domains as $domain)
        {
            // Do not process domains marked as fraud.
            if($setting['syncUseNativeWHMCS'] == true && strtolower($domain->status) == 'fraud')
                continue;

            // Do not process active or pending transfer domains when the native sync feature is enabled.
            if($setting['syncUseNativeWHMCS'] == true
                && (strtolower($domain->status) == 'active'
                || strtolower($domain->status) == 'pending'))
                continue;

            $this->printDebug('WORKING ON ' . $domain->domain);
            $this->update_executed_logs = null;
            $this->update_domain_data   = null;

            try
            {
                $this->objectDomain  = $domain;
                $this->op_domain_obj = $this->OpenProvider->domain($domain->domain);
                $this->op_domain   	 = $this->OpenProvider->api->retrieveDomainRequest($this->op_domain_obj);

                // Set the expire and due date -> openprovider is leading
                if($setting['syncExpiryDate'] == true)
                    $this->process_expiry_date();

                // Active or pending? -> openprovider is leading
                if($setting['syncDomainStatus'] == true)
                    $this->process_domain_status();

                if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false) {
                    // auto renew on or not? -> WHMCS is leading.
                    if ($setting['syncAutoRenewSetting'] == true) {
                        $this->process_auto_renew();
                    }

                    // Identity protection or not? -> WHMCS is leading.
                    if ($setting['syncIdentityProtectionToggle'] == true) {
                        $this->process_identity_protection();
                    }
                }

            }
            catch (\Exception $ex)
            {
                // Do not process for pending or transfer pending domains.
                if($domain->status == 'Pending'
                    || $domain->status == 'Pending Transfer')
                    continue;

                if($ex->getMessage() == 'This action is prohibitted for current domain status.') {
                    // Set the status to expired.
                    $this->process_domain_status('Expired');
                }
                else if($ex->getMessage() == 'The domain is not in your account; please transfer it to your account first.') {
                    // Set the status to expired.
                    $this->process_domain_status('Cancelled');
                }
                else
                {
                    $activity['data']['id']     = $this->objectDomain->id;
                    $activity['data']['domain'] = $this->objectDomain->domain;
                    $activity['data']['message'] =  $ex->getMessage();;
                    Activity::log('unexpected_error', $activity['data']);
                }
            }

            // Save
            Domain::save($this->objectDomain->id, $this->update_domain_data, 'openprovider');

            // Check if we have to log anything.
            if(isset($this->update_executed_logs))
            {
                foreach($this->update_executed_logs as $activity)
                {
                    $activity['data']['id']     = $this->objectDomain->id;
                    $activity['data']['domain'] = $this->objectDomain->domain;
                    Activity::log($activity['activity'], $activity['data']);
                }

                // Do some cleanup
                unset($this->update_executed_logs);
            }
        }

        if(Configuration::getOrDefault('syncUseNativeWHMCS', false) == false) {
            if($setting['updateNextDueDate'] == true)
                $this->process_next_due_dates();
        }

        $this->process_empty_next_due_dates();

        // Send global notification when the wpp contract us unsigned.
        if(!empty($this->unsigned_wpp_contract_domains))
        {
            $notification = new Notification();
            $notification->WPP_contract_unsigned_multiple_domains($this->unsigned_wpp_contract_domains)
                ->send_to_admins();
        }

        $this->printDebug('DONE PROCESSING DOMAINS');
    }

    /**
     * Process and match the expiry date.
     *
     * @return void
     **/
    private function process_expiry_date()
    {
        $this->printDebug('PROCESSING EXPIRY DATE FOR ' . $this->objectDomain->domain .' (WAS ' . $this->objectDomain->expirydate . ')');

        // Sync the expiry date
        $whmcs_expiry_date = new Carbon($this->objectDomain->expirydate);

        // Check if we have a valid timestamp.
        if($whmcs_expiry_date->getTimestamp() > 0)
        {
            $expiry_date_result = General::compare_dates($whmcs_expiry_date->toDateString(), $this->op_domain['renewalDate'], '0', 'Y-m-d H:i:s');
        }
        else
        {
            // There is no valid timestamp.
            $expiry_date_result = [
                'date' => $this->op_domain['renewalDate']
            ];
        }

        if($expiry_date_result != 'correct')
        {
            $this->printDebug('UPDATING EXPIRY DATE FOR ' . $this->objectDomain->domain .' TO ' . $expiry_date_result['date'] . ')');
            $this->update_domain_data['expirydate'] = $expiry_date_result['date'];

            $activity_data = [
                'old_date'      => $this->objectDomain->expirydate,
                'new_date'      => $expiry_date_result['date']
            ];

            $this->update_executed_logs[] = ['activity' => 'update_domain_expiry_date', 'data' => $activity_data];
        }
    }

    protected function process_next_due_dates()
    {
        $this->printDebug('PROCESSING NEXT DUE DATES SYNC FOR ALL OP DOMAINS');
        $days_before_expiry_date = Configuration::getOrDefault('nextDueDateOffset',14);
        $nextDueDateUpdateMaxDayDifference = Configuration::getOrDefault('nextDueDateUpdateMaxDayDifference', 100);

        $updated_domains = $this->domain->updateNextDueDateOffset($days_before_expiry_date, $nextDueDateUpdateMaxDayDifference, 'openprovider');

        // Nothing has been updated.
        if(empty($updated_domains))
            return;

        foreach($updated_domains as $domain)
        {
            $activity_data = [
                'id' => $domain['domain']->id,
                'domain' => $domain['domain']->domain,
                'old_date'      => $domain['original_nextduedate']->toDateString(),
                'new_date'      => $domain['domain']->nextduedate
            ];

            $this->printDebug('NEXT DUE DATE WAS UPDATED FOR ' . $domain['domain']->domain . ' FROM ' . $activity_data['old_date'] . ' TO ' . $activity_data['new_date']);

            $diff_in_days = $domain['domain']->new_nextduedate_difference;

            if($diff_in_days < 0)
                $activity_data ['old_due_date_too_late'] = $diff_in_days;
            elseif($diff_in_days > 0)
                $activity_data ['old_due_date_too_early'] = $diff_in_days;

            Activity::log('update_domain_next_due_date', $activity_data);
        }

        $this->printDebug('DONE PROCESSING NEXT DUE DATES');
    }

    /**
     * Update domains with the next due date set to something like 00/00/0000 to the expiry date.
     */
    protected function process_empty_next_due_dates()
    {
        $this->printDebug('START PROCESSING EMPTY NEXT DUE DATES');

        $days_before_expiry_date = Configuration::getOrDefault('nextDueDateOffset',14);

        $updated_domains = $this->domain->updateEmptyNextDueDates('openprovider', $days_before_expiry_date);

        // Nothing has been updated.
        if(empty($updated_domains))
            return;

        foreach($updated_domains as $domain)
        {
            $activity_data = [
                'id' => $domain['domain']->id,
                'domain' => $domain['domain']->domain,
                'old_date'      => $domain['original_nextduedate']->toDateString(),
                'new_date'      => $domain['domain']->nextduedate
            ];

            $this->printDebug('EMPTY NEXT DUE DATE WAS UPDATED FOR ' . $domain['domain']->domain . ' FROM ' . $activity_data['old_date'] . ' TO ' . $activity_data['new_date']);

            Activity::log('update_domain_empty_next_due_date', $activity_data);
        }

        $this->printDebug('DONE PROCESSING EMPTY NEXT DUE DATES');
    }

    /**
     * Process the Domain status setting
     *
     * @param  string $status Set the domain status
     * @return void
     **/
    private function process_domain_status($status = null)
    {
        if($status == 'Cancelled' || $status == 'Expired')
        {
            // Nothing to do.
            if($this->objectDomain->status == $status)
                return;

            $this->update_domain_data['status'] = $status;

            /**
             * Setup an hook to log the domain status
             */
            $activity_data = [
                'old_status'      => $this->objectDomain->status,
                'new_status'      => $status
            ];

            $this->update_executed_logs[] = ['activity' => 'update_domain_status', 'data' => $activity_data];

            return;
        }

        $op_domain_status = api_domain::convertOpStatusToWhmcs($this->op_domain['status']);

        if($op_domain_status != false)
        {
            // Check if the status matches
            if($this->objectDomain->status != $op_domain_status)
            {
                if($this->objectDomain->status == 'Pending Transfer' && $op_domain_status == 'Active')
                {
                    if($this->objectDomain->check_renew_domain_setting_upon_completed_transfer() == true)
                    {
                        $this->OpenProvider->api->renewDomain($this->op_domain_obj, $this->objectDomain->registrationperiod);

                        // Fetch updated information
                        $this->op_domain             =    $this->OpenProvider->api->retrieveDomainRequest($this->op_domain_obj);
                    }

                    // Since this is a transfer, we will always update the expiration date. We ignore the configuration setting
                    // since this is an initial transfer.
                    $this->process_expiry_date();
                }

                // It does not, let's update the data.
                $this->update_domain_data['status'] = $op_domain_status;

                /**
                 * Setup an hook to log the domain status
                 */
                $activity_data = [
                    'old_status'      => $this->objectDomain->status,
                    'new_status'      => $op_domain_status
                ];

                $this->printDebug('PROCESSING DOMAIN STATUS CHANGE ' . $this->objectDomain->domain .' ( ' . $this->objectDomain->status . ' => ' . $op_domain_status . ')');

                $this->update_executed_logs[] = ['activity' => 'update_domain_status', 'data' => $activity_data];

            }

        }
        else
        {
            logModuleCall('openprovider', 'update_domain_status', '', $this->op_domain, 'OpenProvider domain status: '.$this->op_domain['status'], null);
        }
    }

    /**
     * Process the Domain autorenew setting
     *
     * @return void
     **/
    private function process_auto_renew()
    {
        $result = $this->OpenProvider->toggle_autorenew($this->objectDomain, $this->op_domain);

        if($result != 'correct')
        {
            $this->printDebug('PROCESSING AUTO RENEW CHANGE FOR ' . $this->objectDomain->domain .' ( ' . $result['old_setting'] . ' => ' . $result['new_setting'] . ')');
            /**
             * Log the activity data
             */
            $activity_data = [
                'id'            => $this->objectDomain->id,
                'domain'        => $this->objectDomain->domain,
                'old_setting'   => $result['old_setting'],
                'new_setting'   => $result['new_setting'],
            ];

            Activity::log('update_autorenew_setting', $activity_data);
        }
    }

    /**
     * Process the Domain identity protection setting
     *
     * @return void
     **/
    private function process_identity_protection()
    {
        try {
            $result = $this->OpenProvider->toggle_whois_protection($this->objectDomain, $this->op_domain);

        } catch (\Exception $e) {
            \logModuleCall('OpenProvider', 'Save identity toggle', $this->objectDomain->domain, [$this->op_domain_obj->domain, @$this->op_domain, $this->op_domain_obj], $e->getMessage(), [$params['Password']]);

            $this->unsigned_wpp_contract_domains[] = $this->objectDomain->domain;
        }

        if($result != 'correct')
        {
            /**
             * Log the activity data
             */
            $activity_data = [
                'id'            => $this->objectDomain->id,
                'domain'        => $this->objectDomain->domain,
                'old_setting'   => $result['old_setting'],
                'new_setting'   => $result['new_setting'],
            ];

            $this->printDebug('PROCESSING IDENTITY PROTECTIONS CHANGE FOR ' . $this->objectDomain->domain .' ( ' . $result['old_setting'] . ' => ' . $result['new_setting'] . ')');

            Activity::log('update_identity_protection_setting', $activity_data);
        }
    }


    public function printDebug($message)
    {
        if(defined('OP_REG_DEBUG'))
            echo $message . "\n";
    }

} // END class DomainSync