<?php
namespace OpenProvider;
use WHMCS\Database\Capsule;
use OpenProvider\Notification;
use \OpenProvider\WhmcsHelpers\Domain;
use OpenProvider\WhmcsHelpers\Activity;
use \OpenProvider\WhmcsHelpers\General;
use OpenProvider\WhmcsHelpers\Registrar;
use \OpenProvider\WhmcsHelpers\DomainSync as helper_DomainSync;

/**
 * Helper to synchronize the domain info.
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
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
    private $domain;

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
     * Init the class
     *
     * @return void
     **/
    public function __construct($limit = false)
    {
        // Check if there are domains missing from the DomainSync table
        helper_DomainSync::sync_DomainSync_table('openprovider');

        // Get all unprocessed domains
        if($limit === false)
        {
            $limit = Registrar::get('domainProcessingLimit');
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
        $this->OpenProvider = new OpenProvider;
        foreach($this->domains as $domain)
        {
            $this->update_executed_logs = null;
            $this->update_domain_data = null;

            try
            {
                $this->domain 			= $domain;
                $this->op_domain_obj 	= $this->OpenProvider->domain($domain->domain);
                $this->op_domain   		= $this->OpenProvider->api->retrieveDomainRequest($this->op_domain_obj);

                // Set the expire and due date -> openprovider is leading
                $this->process_expiry_and_due_date();

                // Active or pending? -> openprovider is leading
                $this->process_domain_status();

                // auto renew on or not? -> WHMCS is leading.
                $this->process_auto_renew();

                // Identity protectionon or not? -> WHMCS is leading.
                $this->process_idenity_protection();
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
                    $data ['requestString']     = print_r($this->domain, 1);
                    $data ['responseData']      = $ex->getMessage();
                    $data ['processedData']     = null;
                    $data ['replaceVars']       = [];
                    Activity::log('Fetching domain status', $data, true);
                }
            }

            // Save
            Domain::save($this->domain->id, $this->update_domain_data, 'openprovider');

            // Check if we have to log anything.
            if(isset($this->update_executed_logs))
            {
                foreach($this->update_executed_logs as $activity)
                {
                    $activity['data']['id']     = $this->domain->id;
                    $activity['data']['domain'] = $this->domain->domain;
                    Activity::log($activity['activity'], $activity['data']);
                }

                // Do some cleanup
                unset($this->update_executed_logs);
            }
        }

        // Send global notification when the wpp contract us unsigned.
        if(!empty($this->unsigned_wpp_contract_domains))
        {
            $notification = new Notification();
            $notification->WPP_contract_unsigned_multiple_domains($this->unsigned_wpp_contract_domains)
                ->send_to_admins();
        }
    }

    /**
     * Process and match the expiry date.
     *
     * @return void
     **/
    private function process_expiry_and_due_date()
    {
        // Sync the expiry date
        $expiry_date_result = General::compare_dates($this->domain->expirydate, $this->op_domain['renewalDate'], '0', 'Y-m-d H:i:s');

        if($expiry_date_result != 'correct')
        {
            $this->update_domain_data['expirydate'] = $expiry_date_result['date'];

            $activity_data = [
                'old_date'      => $this->domain->expirydate,
                'new_date'      => $expiry_date_result['date']
            ];

            $this->update_executed_logs[] = ['activity' => 'update_domain_expiry_date', 'data' => $activity_data];
        }

        // Sync the next due date
        // Get the number only. Negatives are switched to positives as we do not want make the due date later.
        $next_due_date_result = General::compare_dates($this->domain->nextduedate, $this->op_domain['renewalDate'], Registrar::get('nextDueDateOffset'), 'Y-m-d H:i:s', 'CEST', 60);

        if(Registrar::get('updateNextDueDate') == 'on' && $next_due_date_result != 'correct')
        {
            // Updating the next due dates is tricky since we need to update the next due dates from the invoices as well.
            // First, we need to calculate the difference in time.
            $invoice_item = Capsule::table('tblinvoiceitems')
                ->where('type', 'domain')
                ->where('relid', $this->domain->id)
                ->where('duedate', $this->domain->nextduedate)
                ->orderBy('id', 'desc')
                ->first();

            if(!empty($invoice_item))
            {
                try {
                    $capsule = Capsule::table('tblinvoiceitems')
                        ->where('id', $invoice_item->id)
                        ->update(['duedate' => $next_due_date_result['date']]);

                    /**
                     * Log the activity data
                     */
                    $activity_data = [
                        'id'            => $this->domain->id,
                        'domain'        => $this->domain->domain,
                        'invoiceid'     => $invoice_item->invoiceid,
                        'old_date'      => $this->domain->nextduedate,
                        'new_date'      => $next_due_date_result['date']
                    ];

                    Activity::log('update_invoice_next_due_date', $activity_data);
                } catch (\Exception $e) {
                    logModuleCall('openprovider', 'Update nextduedate for invoiceitem', $invoice_item->id, null, $e->getMessage(), null);

                    // We should not update the next due date as this will result in extra generated invoices by WHMCS.
                    return false;
                }
            }

            $this->update_due_date_for_domain($next_due_date_result);
        }
    }

    protected function update_due_date_for_domain($next_due_date_result)
    {
        $this->update_domain_data['nextduedate'] 		= $next_due_date_result ['date'];
        $this->update_domain_data['nextinvoicedate'] 	= $next_due_date_result ['date'];

        $activity_data = [
            'old_date'      => $this->domain->nextduedate,
            'new_date'      => $next_due_date_result['date']
        ];

        $diff_in_days = $next_due_date_result['difference_in_days'] + Registrar::get('nextDueDateOffset');

        if($diff_in_days < 0)
            $activity_data ['old_due_date_in_future'] = $diff_in_days;

        $this->update_executed_logs[] = ['activity' => 'update_domain_next_due_date', 'data' => $activity_data];
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
            if($this->domain->status == $status)
                return;

            $this->update_domain_data['status'] = $status;

            /**
             * Setup an hook to log the domain status
             */
            $activity_data = [
                'old_status'      => $this->domain->status,
                'new_status'      => $status
            ];

            $this->update_executed_logs[] = ['activity' => 'update_domain_status', 'data' => $activity_data];

            return;
        }

        $op_status_converter = [
            'ACT' => 'Active',		// ACT	The domain name is active
            'DEL' => 'Expired',		// DEL	The domain name has been deleted, but may still be restored.
            /* Leave FAI out of the array as we need to make the if statement fail in order to log an error. 'FAI' => 'error',	// FAI	The domain name request has failed.*/
            'PEN' => 'Pending',		// PEN	The domain name request is pending further information before the process can continue.
            'REQ' => 'Pending',		// REQ	The domain name request has been placed, but not yet finished.
            'RRQ' => 'Pending',		// RRQ	The domain name restore has been requested, but not yet completed.
            'SCH' => 'Pending',		// SCH	The domain name is scheduled for transfer in the future.
        ];

        if(isset($op_status_converter[ $this->op_domain['status'] ]))
        {
            $op_domain_status = $op_status_converter[ $this->op_domain['status'] ];

            // Check if the status matches
            if($this->domain->status != $op_domain_status)
            {
                // Update the due date
                if($this->domain->status == 'Pending' || $this->domain->status == 'Pending Transfer')
                {
                    $next_due_date_result = General::compare_dates($this->domain->nextduedate, $this->op_domain['renewalDate'], Registrar::get('nextDueDateOffset'), 'Y-m-d H:i:s', 'CEST');
                    $this->update_due_date_for_domain($next_due_date_result);
                }

                // It does not, let's update the data.
                $this->update_domain_data['status'] = $op_domain_status;

                /**
                 * Setup an hook to log the domain status
                 */
                $activity_data = [
                    'old_status'      => $this->domain->status,
                    'new_status'      => $op_domain_status
                ];

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
        $result = $this->OpenProvider->toggle_autorenew($this->domain, $this->op_domain);

        if($result != 'correct')
        {
            /**
             * Log the activity data
             */
            $activity_data = [
                'id'            => $this->domain->id,
                'domain'        => $this->domain->domain,
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
    private function process_idenity_protection()
    {
        try {
            $result = $this->OpenProvider->toggle_whois_protection($this->domain, $this->op_domain);
    
        } catch (\Exception $e) {
            \logModuleCall('OpenProvider', 'Save identity toggle', $params['domainname'], [$OpenProvider->domain, @$op_domain, $OpenProvider], $e->getMessage(), [$params['Password']]);
            
            $this->unsigned_wpp_contract_domains[] = $this->domain->domain;
        }

        if($result != 'correct')
        {
            /**
             * Log the activity data
             */
            $activity_data = [
                'id'            => $this->domain->id,
                'domain'        => $this->domain->domain,
                'old_setting'   => $result['old_setting'],
                'new_setting'   => $result['new_setting'],
            ];

            Activity::log('update_identity_protection_setting', $activity_data);
        }
    }



} // END class DomainSync