<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\System;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainRenewal;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainTransfer;
use OpenProvider\WhmcsHelpers\Activity;
use WeDevelopCoffee\wPower\Core\Core;
use \WHMCS\Module\Addon\Setting;
use \OpenProvider\WhmcsDomainAddon\Models\Domain;
use WHMCS\Database\Capsule;
use OpenProvider\API\Domain as openprovider_domain;

/**
 * Class SynchroniseController
 * @package WeDevelopCoffee\wDomainAbuseMonitor\Controllers\System
 */
class SynchroniseController extends BaseController
{
    /**
     * @var ScheduledDomainTransfer
     */
    private $scheduledDomainTransfer;
    /**
     * @var OpenProvider
     */
    private $openProvider;
    /**
     * @var ScheduledDomainRenewal
     */
    private $scheduledDomainRenewal;
    /**
     * @var Domain
     */
    protected $domain;
    /**
     * @var openprovider_domain
     */
    private $openprovider_domain;
    /**
     * @var int
     */
    protected $run_id;

    /**
     * SynchroniseController constructor.
     * @param Core $core
     * @param Domain $domain
     * @param ScheduledDomainTransfer $scheduledDomainTransfer
     * @param OpenProvider $openProvider
     */
    public function __construct(Core $core, Domain $domain, ScheduledDomainTransfer $scheduledDomainTransfer, ScheduledDomainRenewal $scheduledDomainRenewal, OpenProvider $openProvider, openprovider_domain $openprovider_domain)
    {
        parent::__construct($core);
        $this->domain = $domain;
        $this->scheduledDomainTransfer = $scheduledDomainTransfer;
        $this->openProvider = $openProvider;
        $this->scheduledDomainRenewal = $scheduledDomainRenewal;
        $this->openprovider_domain = $openprovider_domain;
        $this->run_id = time();
    }

    public function synchronise()
    {
        $this->start();

        $this->updateScheduledDomainTransferTable();

        $this->linkDomainsToWhmcsDomains();

        $this->processNotUpdatedDomains();

        $this->processDomainsWithDisabledAutorenewal();

        $this->shutdown();
    }

    /**
     * Update the table with the latest data from Openprovider.
     */
    public function updateScheduledDomainTransferTable(): void
    {
        $this->printDebug('Downloading all scheduled transfers from Openprovider.');
        $openprovider_scheduled_transfers = $this->getOpenproviderScheduledTransfers();;

        if(empty($openprovider_scheduled_transfers))
        {
            echo "No domains scheduled for transfers at Openprovider.\n";
            $this->printDebug('No domains scheduled for transfers at Openprovider.');
            return;
        }

        $this->printDebug('Storing the scheduled transfers in database.');
        foreach ($openprovider_scheduled_transfers as $scheduled_transfer) {
            $domain = implode('.', $scheduled_transfer['domain']);

            $this->printDebug('Importing ... ' . $domain);

            $model_scheduled_domain_transfer = $this->scheduledDomainTransfer->firstOrNew(['domain' => $domain]);
            $model_scheduled_domain_transfer->cron_run_date = Capsule::raw('now()');
            $model_scheduled_domain_transfer->status = $scheduled_transfer['status'];
            $model_scheduled_domain_transfer->run_id = $this->run_id;
            $model_scheduled_domain_transfer->save();
        }

        $this->printDebug('All scheduled domains are synchronised in WHMCS.');
    }

    /**
     * Fetch the transfers with the Openprovider API.
     */
    protected function getOpenproviderScheduledTransfers($offset = 0, $limit = 1000)
    {
        $filters ['offset'] = $offset;
        $filters ['limit'] = $limit;
        $filters ['status'] = 'SCH';

        $found_domains = $this->openProvider->api->searchDomain($filters)['results'];

        if(!is_array($found_domains)){
            return $found_domains;
        }

        if (!is_null($found_domains) && count($found_domains) == 1000) {
            $new_offset = $offset + $limit;
            $found_domains = array_merge($found_domains, $this->getOpenproviderScheduledTransfers($new_offset, $limit));
        } 

        return $found_domains;
    }

    /**
     * Link domains that exist in the openprovider table to WHMCS domains.
     */
    public function linkDomainsToWhmcsDomains()
    {
        $unlinked_scheduled_domain_transfers = $this->scheduledDomainTransfer->where('domain_id', null)->get();

        if(!count($unlinked_scheduled_domain_transfers))
            return;

        foreach ($unlinked_scheduled_domain_transfers as $unlinked_scheduled_domain_transfer) {
            try {
                $model_domain = $this->domain->where('domain', $unlinked_scheduled_domain_transfer->domain)->firstOrFail();
                $unlinked_scheduled_domain_transfer->domain_id = $model_domain->id;
                $unlinked_scheduled_domain_transfer->save();
            } catch (ModelNotFoundException $e) {
                // Nothing to do.
            }
        }
    }

    /**
     * Find domains that are not found in updateScheduledDomainTransferTable and
     * check their status. It is possible that domain transfers have finished
     * or are cancelled.
     */
    public function processNotUpdatedDomains()
    {
        $this->printDebug('Processing not updated domains.');

        // Fetch all unprocessed domains
        $scheduled_domain_transfers = $this->scheduledDomainTransfer
            ->where('run_id', '!=', $this->run_id)
            ->where('domain_id', '!=', null)
            ->get();

        if(!count($scheduled_domain_transfers))
            // Nothing was found.
            return true;

        foreach($scheduled_domain_transfers as $scheduled_domain_transfer)
        {
            $this->printDebug('Processing ' . $scheduled_domain_transfer->domain . ' ... ');
            $openprovider_domain = $this->getDomainFromOpenprovider($scheduled_domain_transfer);

            if($openprovider_domain == false)
                // No domain with a scheduled transfer was found. Skip.
                continue;

            // Process the status
            $openprovider_status = $this->detectOpenproviderDomainStatus($openprovider_domain['status']);

            if($openprovider_status == 'Active')
            {
                $this->printDebug('Status ' . $scheduled_domain_transfer->domain . ' is active. ');
                // Domain is active.
                $scheduled_domain_transfer->status = $openprovider_domain['status'];
                $scheduled_domain_transfer->finished_transfer_date = Capsule::raw('now()');
                $scheduled_domain_transfer->cron_run_date = Capsule::raw('now()');
                $scheduled_domain_transfer->save();

                // Update the domain to active.
                $scheduled_domain_transfer->tbldomain->status = 'Active';

                // Update the registrar with to openprovider.
                $domain =  $scheduled_domain_transfer->tbldomain;
                $domain->registrar = 'openprovider';
                $domain->expirydate = $openprovider_domain['expirationDate'];
                $domain->save();

                //Log to the Activity log.
                $message = 'Openprovider Scheduled Transfer: [DOMAIN ' . $scheduled_domain_transfer->domain . '] updated expiry date, status and registrar.';
                $this->logActivity($message);
                $this->printDebug($message);

                try {
                    $scheduled_domain_renewal = $this->scheduledDomainRenewal->where('domain_id', $scheduled_domain_transfer->domain_id)->firstOrFail();
                    $title = 'Check if new expiry date matches';
                    $description = 'The domain transfer of ' . $scheduled_domain_renewal->domain .' to openprovider has been completed. Between the scheduled transfer and the completion, the customer has renewed the domain subscription. This renewal has been blocked at the original registrar and the new expiry date has been recorded. Please make sure that the new expiry date matches.
Original expiry date: ' . $scheduled_domain_renewal->original_expiry_date .'
New expiry date: ' . $scheduled_domain_renewal->new_expiry_date;

                    $this->add_todo($title, $description);

                } catch ( ModelNotFoundException $e)
                {
                    // Do nothing.
                }
            }
            elseif($openprovider_status == 'Pending Transfer')
            {
                // Do nothing.
                continue;
            }
            else
            {
                $this->printDebug('No status for ' . $scheduled_domain_transfer->domain . ' is found. Removing scheduled domain transfer record. ');
                // Domain has a different status. Let's remove the domain.
                $scheduled_domain_transfer->delete();
            }
        }

    }

    /**
     * Convert the status.
     * @param object $domain
     * @return string
     */
    protected function detectOpenproviderDomainStatus($status)
    {
        return $this->openprovider_domain->convertOpStatusToWhmcs($status);
    }

    /**
     * Check if there are domains with disabled auto renewals.
     */
    public function processDomainsWithDisabledAutorenewal()
    {
        $this->printDebug('Searching for scheduled domain transfers with disabled auto renewal...');

        // Fetch all unprocessed domains
        $scheduled_domain_transfers = $this->scheduledDomainTransfer
            ->where('status', 'SCH')
            ->whereHas('tbldomain', function ($query){
                $query->where('donotrenew', 1);
            })
            ->get();

        if(count($scheduled_domain_transfers) == 0)
        {
            echo "Import Completed.\n";
            $this->printDebug('Done');
            // Nothing was found.
            return true;
        }

        foreach($scheduled_domain_transfers as $scheduled_domain_transfer) {
            $this->printDebug('Processing ' . $scheduled_domain_transfer->domain . ' ... ');

            $openprovider_domain = $this->getDomainFromOpenprovider($scheduled_domain_transfer);

            if($openprovider_domain == false)
                // No domain with a scheduled transfer was found. Skip.
                continue;

            $openprovider_status = $this->detectOpenproviderDomainStatus($openprovider_domain['status']);
            if($openprovider_status != 'Pending')
            {
                $this->printDebug('DOMAIN ' . $scheduled_domain_transfer->domain . ' is not pending a transfer. Removing from local cache.');
                $scheduled_domain_transfer->delete();
                continue;
            }

            // Now we are sure the domain is pending.
            // Remove the domain.
            $this->deleteScheduledDomainTransferFromOpenprovider($scheduled_domain_transfer);
            $scheduled_domain_transfer->delete();
        }

        $this->printDebug('Done ');
    }

    /**
     * Get the domain from openprovider. Removes the transfer when it is not scheduled.
     *
     * @param $scheduled_domain_transfer
     * @return mixed|boolean|object Returns false when there is no scheduled domain transfer.
     */
    protected function getDomainFromOpenprovider($scheduled_domain_transfer)
    {
        // Fetch for each domain the status.
        $this->openprovider_domain->name = explode('.', $scheduled_domain_transfer->domain)[0];
        $this->openprovider_domain->extension = str_replace($this->openprovider_domain->name . '.', '',$scheduled_domain_transfer->domain);

        try
        {
            $openprovider_domain = $this->openProvider->api->retrieveDomainRequest($this->openprovider_domain);
        }
        catch (\Exception $ex)
        {
            // Do not process for pending or transfer pending domains.
            if($scheduled_domain_transfer->tbldomain->status == 'Pending'
                || $scheduled_domain_transfer->tbldomain->status == 'Pending Transfer')
                return false;

            if($ex->getMessage() == 'This action is prohibitted for current domain status.') {
                $scheduled_domain_transfer->delete();
                return false;
            }
            else if($ex->getMessage() == 'The domain is not in your account; please transfer it to your account first.') {
                $scheduled_domain_transfer->delete();
                return false;
            }
        }

        return $openprovider_domain;
    }

    /**
     * Delete the scheduled transfer from openprovider.
     */
    protected function deleteScheduledDomainTransferFromOpenprovider($scheduled_domain_transfer)
    {
        $this->printDebug('STARTING DELETION OF ' . $scheduled_domain_transfer->domain . ' ... ');

        // Fetch for each domain the status.
        $openprovider_api_domain = new openprovider_domain();
        $openprovider_api_domain->name = explode('.', $scheduled_domain_transfer->domain)[0];
        $openprovider_api_domain->extension = str_replace($openprovider_api_domain->name . '.', '',$scheduled_domain_transfer->domain);

        try
        {
            $this->openProvider->api->requestDelete($openprovider_api_domain);
            $this->printDebug('SCHEDULED DOMAIN TRANSFER ' . $scheduled_domain_transfer->domain . ' IS DELETED IN OPENPROVIDER');
        }
        catch (\Exception $ex)
        {
            $this->printDebug('DELETION OF SCHEDULED DOMAIN TRANSFER ' . $scheduled_domain_transfer->domain . ' IN OPENPROVIDER FAILED');
            return false;
        }

        return true;
    }

    /**
     * @param $title
     * @param $description
     */
    protected function add_todo($title, $description)
    {
        $today = Carbon::now();
        $tomorrow = $today->addDay();

        $todo = [
            'date' => $today,
            'title' => $title,
            'description' => $description,
            'admin' => 0,
            'status' => 'Pending',
            'duedate' => $tomorrow
        ];

        Capsule::table('tbltodolist')->insert($todo);
    }
}