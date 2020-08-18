<?php

namespace OpenProvider\WhmcsDomainAddon\Controllers\Hooks;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenProvider\API\Domain as openprovider_domain;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainRenewal;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainTransfer;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Setting;

/**
 * Class RenewHookController
 * @package WeDevelopCoffee\WhmcsDomainAddon\Controllers\Hooks
 */
class RenewHookController extends BasePermissionController
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
     * @var Capsule
     */
    private $capsule;

    /**
     * DomainAdminHookController constructor.
     */
    public function __construct(
        Core $core,
        ScheduledDomainTransfer $scheduledDomainTransfer,
        ScheduledDomainRenewal $scheduledDomainRenewal,
        OpenProvider $openProvider,
        Capsule $capsule,
        openprovider_domain $openprovider_domain
    ) {
        parent::__construct($core);
        $this->scheduledDomainTransfer = $scheduledDomainTransfer;
        $this->openProvider = $openProvider;
        $this->scheduledDomainRenewal = $scheduledDomainRenewal;
        $this->capsule = $capsule;
        $this->openprovider_domain = $openprovider_domain;
    }

    /**
     * Store the renewal when this is for a non-openprovider domain.
     * @return array
     */
    public function processRenewal($params)
    {
        // Get the domain details
        $domain = $this->capsule->table('tbldomains')
            ->find($params['params']['domainid']);

        if ($this->checkIsNotOpenprovider($domain) == false) // It is an openprovider domain. Skip this one.
        {
            return true;
        }

        // Check if the domain is scheduled for a transfer.
        if ($this->checkScheduledTransferAtOpenprovider($domain) == true) {
            // Calculate the new +/- expiry date
            $new_expiry_date = new Carbon($domain->expirydate);

            $new_expiry_date->addYears($params['params']);

            $scheduled_domain_renewal = $this->scheduledDomainRenewal;
            $scheduled_domain_renewal->domain_id = $domain->id;
            $scheduled_domain_renewal->domain = $domain->domain;
            $scheduled_domain_renewal->original_expiry_date = $domain->expirydate;
            $scheduled_domain_renewal->new_expiry_date = $new_expiry_date;
            $scheduled_domain_renewal->save();

            // Update the scheduled transfer date to today
            $openprovider_api_domain = $this->openprovider_domain;
            $openprovider_api_domain->name = explode('.', $domain->domain)[0];
            $openprovider_api_domain->extension = str_replace($openprovider_api_domain->name . '.', '',
                $domain->domain);

            try {
                $openprovider_domain = $this->openProvider->api->modifyScheduledTransferDate($openprovider_api_domain, date("Y-m-d H:i:s"));
            } catch (\Exception $ex) {
                return false;
            }

            return array (
                'abortWithSuccess' => true,
            );
        }

        return false;
    }

    /**
     * Check if the domain is not registered with Openprovider.
     *
     * @param $domain
     * @return boolean
     */
    protected function checkIsNotOpenprovider($domain)
    {
        if ($domain->registrar != 'openprovider') {
            return true;
        }
        return false;
    }

    /**
     * Check if a domain transfer is scheduled with Openprovider.
     * If so, record the renewal and prevent that the renewal is
     * being executed at the original provider.
     * @param $domain
     * @return boolean
     */
    protected function checkScheduledTransferAtOpenprovider($domain)
    {
        // First, find the domain in the local list.
        try {
            $scheduled_domain_transfer = $this->scheduledDomainTransfer->where('domain',
                $domain->domain)->firstOrFail();

            // SCH means scheduled transfer.
            if ($scheduled_domain_transfer->status == 'SCH') {
                return true;
            } else // The domain does exist with Openprovider but is not scheduled for a transfer.
            {
                return false;
            }

        } catch (ModelNotFoundException $e) {
            // Nothing was found. Let's continue.
        }

        $openprovider_api_domain = $this->openprovider_domain;
        $openprovider_api_domain->name = explode('.', $scheduled_domain_transfer->domain)[0];
        $openprovider_api_domain->extension = str_replace($openprovider_api_domain->name . '.', '',
            $scheduled_domain_transfer->domain);

        try {
            $openprovider_domain = $this->openProvider->api->retrieveDomainRequest($openprovider_api_domain);
            $domain_status = $this->openprovider_domain->convertOpStatusToWhmcs($openprovider_domain['status']);

            if ($domain_status == 'Active' || $domain_status == 'Pending' || $domain_status == 'Pending Transfer') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * When the autorenewal is disabled, remove the scheduled transfer.
     * When the autorenewal setting is enabled, create a todo record in WHMCS.
     */
    public function processAutorenewalSetting($params)
    {
        if (!isset($_POST['domain']) && !isset($_POST['autorenew'])) {
            return;
        }

        // Get the domain details
        $domain = $this->capsule->table('tbldomains')
            ->where('id', $params['domainid'])
            ->get()[0];

        // Do not run for Openprovider.
        if ($domain->registrar == 'openprovider') {
            return;
        }

        if ($domain->donotrenew == 1) {
            $renewal_action = 'disable';
        } else {
            $renewal_action = 'enable';
        }

        if ($renewal_action == 'disable') {
            if (!$this->checkScheduledTransferAtOpenprovider($domain))
                // When the domain is not scheduled for a transfer with openprovider,
                // abort the process.
            {
                return false;
            }

            try {
                // We are disabling the renewal. Therefore, we do not need to track the renewal.
                $scheduledDomainTransfer = $this->scheduledDomainTransfer
                    ->where('domain_id', $domain->id)
                    ->where('status', 'SCH')
                    ->firstOrFail();

                // Remove the scheduled transfer from Openprovider
                $openprovider_api_domain = $this->openprovider_domain;
                $openprovider_api_domain->name = explode('.', $domain->domain)[0];
                $openprovider_api_domain->extension = str_replace($openprovider_api_domain->name . '.', '',
                    $domain->domain);
                $this->openProvider->api->requestDelete($openprovider_api_domain);

                $scheduledDomainTransfer->delete();
            } catch (ModelNotFoundException $e) {
                // There was no scheduled transfer tracked. This is all good.
            }
        } elseif ($renewal_action == 'enable') {
            $this->add_todo('The autorenewal for ' . $domain->domain . ' has been enabled. Reschedule the transfer with Openprovider.',
                'Reschedule the transfer in case this domain was supposed to get transferred.');
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