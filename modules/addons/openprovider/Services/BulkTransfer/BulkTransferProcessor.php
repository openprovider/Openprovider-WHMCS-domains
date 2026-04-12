<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

use Carbon\Carbon;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferBatch;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferItem;
use OpenProvider\WhmcsDomainAddon\Models\Domain;
use WHMCS\Database\Capsule;

class BulkTransferProcessor
{
    private const PENDING_STATUS_RECHECK_MINUTES = 60;
    private const PENDING_STATUS_CLAIM_TIMEOUT_MINUTES = 15;

    /**
     * @var RegistrarModuleInvoker
     */
    protected $registrarModuleInvoker;

    /**
     * @var OpenproviderTransferClient
     */
    protected $openproviderTransferClient;

    public function __construct(
        RegistrarModuleInvoker $registrarModuleInvoker,
        OpenproviderTransferClient $openproviderTransferClient
    ) {
        $this->registrarModuleInvoker = $registrarModuleInvoker;
        $this->openproviderTransferClient = $openproviderTransferClient;
    }

    public function processQueuedItems($limit = 10)
    {
        $processed = 0;
        $claimed = 0;

        $itemIds = BulkTransferItem::where('transfer_status', BulkTransferItem::STATUS_QUEUED)
            ->where('attempt_count', 0)
            ->orderBy('id')
            ->limit(max(1, (int) $limit))
            ->pluck('id')
            ->all();

        foreach ($itemIds as $itemId) {
            $item = $this->claimItem($itemId);
            if (!$item) {
                continue;
            }

            $claimed++;

            try {
                $this->processItem($item);
            } catch (\Throwable $e) {
                $this->markFailed($item, $e->getMessage());
            }

            $processed++;
        }

        return [
            'claimed' => $claimed,
            'processed' => $processed,
        ];
    }

    public function processPendingTransferItems($limit = 50)
    {
        $processed = 0;
        $claimed = 0;
        $now = Carbon::now();
        $eligibleBefore = $now
            ->copy()
            ->subMinutes(self::PENDING_STATUS_RECHECK_MINUTES)
            ->toDateTimeString();
        $staleCheckingBefore = $now
            ->copy()
            ->subMinutes(self::PENDING_STATUS_CLAIM_TIMEOUT_MINUTES)
            ->toDateTimeString();

        $itemIds = BulkTransferItem::where(function ($query) use ($eligibleBefore, $staleCheckingBefore) {
            $query->where(function ($subQuery) use ($eligibleBefore) {
                $subQuery->where('transfer_status', BulkTransferItem::STATUS_TRANSFER_REQUESTED)
                    ->whereRaw(
                        'COALESCE(last_status_check_at, transfer_requested_at, created_at) <= ?',
                        [$eligibleBefore]
                    );
            })->orWhere(function ($subQuery) use ($staleCheckingBefore) {
                $subQuery->where('transfer_status', BulkTransferItem::STATUS_CHECKING_TRANSFER_STATUS)
                    ->whereRaw(
                        'COALESCE(last_status_check_at, updated_at, transfer_requested_at, created_at) <= ?',
                        [$staleCheckingBefore]
                    );
            });
        })
            ->orderByRaw('COALESCE(last_status_check_at, transfer_requested_at, created_at)')
            ->orderBy('id')
            ->limit(max(1, (int) $limit))
            ->pluck('id')
            ->all();

        foreach ($itemIds as $itemId) {
            $item = $this->claimPendingTransferItem($itemId);
            if (!$item) {
                continue;
            }

            $claimed++;

            try {
                $this->processPendingTransferItem($item);
            } catch (\Throwable $e) {
                $this->releasePendingTransferItem($item, $e->getMessage());
            }

            $processed++;
        }

        return [
            'claimed' => $claimed,
            'processed' => $processed,
        ];
    }

    protected function processItem(BulkTransferItem $item)
    {
        $domainRecord = Domain::where('domain', $item->domain)->first();
        if (!$domainRecord) {
            $this->markValidationFailed($item, 'Domain was not found in WHMCS.');
            return;
        }

        if (strcasecmp((string) $domainRecord->status, 'Active') !== 0) {
            $this->markValidationFailed($item, 'Domain is not active in WHMCS.');
            return;
        }

        if (empty($domainRecord->userid)) {
            $this->markValidationFailed($item, 'Domain is not linked to a WHMCS client.');
            return;
        }

        if (empty($domainRecord->registrar)) {
            $this->markValidationFailed($item, 'Domain has no registrar module assigned.');
            return;
        }

        if (strcasecmp((string) $domainRecord->registrar, 'openprovider') === 0) {
            $this->markValidationFailed($item, 'Domain is already assigned to the Openprovider registrar module.');
            return;
        }

        $client = Capsule::table('tblclients')->where('id', (int) $domainRecord->userid)->first();
        if (!$client) {
            $this->markValidationFailed($item, 'WHMCS client record was not found.');
            return;
        }

        $item->client_id = (int) $client->id;
        $item->domain_id = (int) $domainRecord->id;
        $item->failure_reason = null;
        $item->save();

        try {
            $moduleParams = $this->registrarModuleInvoker->buildModuleParams($domainRecord, $client);
        } catch (\Throwable $e) {
            $this->markValidationFailed($item, $e->getMessage());
            return;
        }

        $this->updateItemStatus($item, BulkTransferItem::STATUS_READY_FOR_TRANSFER);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_UNLOCKING);
        $this->registrarModuleInvoker->unlockDomain([
            'domainid' => (int) $domainRecord->id,
        ]);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_GETTING_EPP);
        $eppCode = $this->registrarModuleInvoker->getEppCode([
            'domainid' => (int) $domainRecord->id,
        ]);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_CREATING_HANDLE);
        $handles = $this->openproviderTransferClient->createOrReuseTransferHandles($moduleParams);

        $item->op_owner_handle = $handles['owner_handle'];
        $item->op_admin_handle = $handles['admin_handle'];
        $item->op_tech_handle = $handles['tech_handle'];
        $item->op_billing_handle = $handles['billing_handle'];
        $item->save();

        $this->updateItemStatus($item, BulkTransferItem::STATUS_TRANSFERRING);
        $transferResponse = $this->openproviderTransferClient->transferDomain([
            'domain' => [
                'name' => $moduleParams['sld'],
                'extension' => $moduleParams['tld'],
            ],
            'auth_code' => $eppCode,
            'owner_handle' => $handles['owner_handle'],
            'admin_handle' => $handles['admin_handle'],
            'tech_handle' => $handles['tech_handle'],
            'billing_handle' => $handles['billing_handle'],
            'autorenew' => !empty($domainRecord->donotrenew) ? 'off' : 'default',
            'is_private_whois_enabled' => !empty($domainRecord->idprotection),
            'is_dnssec_enabled' => false,
            'import_nameservers_from_registry' => true,
        ]);

        $transferState = $this->openproviderTransferClient->normalizeTransferState($transferResponse);
        $this->storeTransferState($item, $transferState, true);

        if ($this->openproviderTransferClient->isActiveTransferStatus($transferState['status'])) {
            $this->finalizeCompletedTransfer($item, $domainRecord, $moduleParams, $transferState);
            return;
        }

        if ($this->openproviderTransferClient->isFailedTransferStatus($transferState['status'])) {
            $this->markFailed(
                $item,
                $this->buildTransferFailureMessage($transferState, 'Openprovider transfer returned a failed status.')
            );
            return;
        }

        $this->markTransferRequested($item, $transferState);
    }

    protected function claimItem($itemId)
    {
        $now = Carbon::now()->toDateTimeString();

        $updated = Capsule::table('mod_op_bulk_transfer_items')
            ->where('id', (int) $itemId)
            ->where('transfer_status', BulkTransferItem::STATUS_QUEUED)
            ->where('attempt_count', 0)
            ->update([
                'transfer_status' => BulkTransferItem::STATUS_VALIDATING,
                'attempt_count' => 1,
                'started_at' => $now,
                'updated_at' => $now,
            ]);

        if (!$updated) {
            return null;
        }

        $item = BulkTransferItem::find($itemId);
        if ($item) {
            $this->refreshBatchStatistics($item->batch_id);
        }

        return $item;
    }

    protected function claimPendingTransferItem($itemId)
    {
        $now = Carbon::now()->toDateTimeString();
        $staleCheckingBefore = Carbon::now()
            ->subMinutes(self::PENDING_STATUS_CLAIM_TIMEOUT_MINUTES)
            ->toDateTimeString();

        $updated = Capsule::table('mod_op_bulk_transfer_items')
            ->where('id', (int) $itemId)
            ->where(function ($query) use ($staleCheckingBefore) {
                $query->where('transfer_status', BulkTransferItem::STATUS_TRANSFER_REQUESTED)
                    ->orWhere(function ($subQuery) use ($staleCheckingBefore) {
                        $subQuery->where('transfer_status', BulkTransferItem::STATUS_CHECKING_TRANSFER_STATUS)
                            ->whereRaw(
                                'COALESCE(last_status_check_at, updated_at, transfer_requested_at, created_at) <= ?',
                                [$staleCheckingBefore]
                            );
                    });
            })
            ->update([
                'transfer_status' => BulkTransferItem::STATUS_CHECKING_TRANSFER_STATUS,
                'last_status_check_at' => $now,
                'updated_at' => $now,
            ]);

        if (!$updated) {
            return null;
        }

        $item = BulkTransferItem::find($itemId);
        if ($item) {
            $this->refreshBatchStatistics($item->batch_id);
        }

        return $item;
    }

    protected function processPendingTransferItem(BulkTransferItem $item)
    {
        $domainLookupObject = DomainLookupObject::fromDomain($item->domain);
        $domainDetails = $this->openproviderTransferClient->getDomainDetails(
            $domainLookupObject->getSecondLevel(),
            $domainLookupObject->getTopLevel()
        );

        $transferState = $this->openproviderTransferClient->normalizeTransferState($domainDetails);
        $this->storeTransferState($item, $transferState);

        if ($this->openproviderTransferClient->isActiveTransferStatus($transferState['status'])) {
            $domainRecord = $this->getExistingDomainRecord($item);
            if (!$domainRecord) {
                $this->markFailed($item, 'Domain could not be found in WHMCS during transfer finalization.');
                return;
            }

            $this->finalizeCompletedTransfer($item, $domainRecord, [], $transferState, false);
            return;
        }

        if ($this->openproviderTransferClient->isFailedTransferStatus($transferState['status'])) {
            $this->markFailed(
                $item,
                $this->buildTransferFailureMessage($transferState, 'Openprovider transfer reached a failed status.')
            );
            return;
        }

        $this->markTransferRequested($item, $transferState);
    }

    protected function updateItemStatus(BulkTransferItem $item, $status)
    {
        $item->transfer_status = $status;
        $item->updated_at = Carbon::now();
        $item->save();
    }

    protected function markTransferRequested(BulkTransferItem $item, array $transferState)
    {
        $now = Carbon::now();

        $item->transfer_status = BulkTransferItem::STATUS_TRANSFER_REQUESTED;
        $item->failure_reason = null;
        $item->finished_at = null;
        $item->transfer_requested_at = $item->transfer_requested_at ?: $now;
        $item->last_status_check_at = $now;
        $item->last_status_message = $this->buildPendingStatusMessage($transferState);
        $item->updated_at = $now;
        $item->save();

        $this->refreshBatchStatistics($item->batch_id);
    }

    protected function releasePendingTransferItem(BulkTransferItem $item, $message)
    {
        $now = Carbon::now();

        $item->transfer_status = BulkTransferItem::STATUS_TRANSFER_REQUESTED;
        $item->last_status_check_at = $now;
        $item->last_status_message = (string) $message;
        $item->updated_at = $now;
        $item->save();

        $this->refreshBatchStatistics($item->batch_id);
    }

    protected function markValidationFailed(BulkTransferItem $item, $reason)
    {
        $this->markTerminalStatus($item, BulkTransferItem::STATUS_VALIDATION_FAILED, $reason);
    }

    protected function markFailed(BulkTransferItem $item, $reason)
    {
        $this->markTerminalStatus($item, BulkTransferItem::STATUS_FAILED, $reason);
    }

    protected function markSuccess(BulkTransferItem $item)
    {
        $this->markTerminalStatus($item, BulkTransferItem::STATUS_SUCCESS, null);
    }

    protected function finalizeCompletedTransfer(
        BulkTransferItem $item,
        Domain $domainRecord,
        array $moduleParams,
        array $transferState,
        $allowRefresh = true
    ) {
        $finalState = $transferState;

        if (empty($finalState['renewal_date']) || empty($finalState['status'])) {
            if (!$allowRefresh) {
                throw new \RuntimeException('Openprovider status check returned incomplete data for transfer finalization.');
            }

            $domainLookupObject = !empty($moduleParams['domainObj'])
                ? $moduleParams['domainObj']
                : DomainLookupObject::fromDomain($domainRecord->domain);

            $finalDomainDetails = $this->openproviderTransferClient->getDomainDetails(
                $domainLookupObject->getSecondLevel(),
                $domainLookupObject->getTopLevel()
            );
            $finalState = $this->openproviderTransferClient->normalizeTransferState($finalDomainDetails);
        }

        $this->storeTransferState($item, $finalState);
        $this->finalizeDomainRecord($domainRecord, $finalState);
        $this->markSuccess($item);
    }

    protected function markTerminalStatus(BulkTransferItem $item, $status, $reason = null)
    {
        $item->transfer_status = $status;
        $item->failure_reason = $reason;
        $item->finished_at = Carbon::now();
        $item->updated_at = Carbon::now();
        $item->save();

        $this->refreshBatchStatistics($item->batch_id);
    }

    protected function refreshBatchStatistics($batchId)
    {
        $statistics = Capsule::table('mod_op_bulk_transfer_items')
            ->where('batch_id', (int) $batchId)
            ->selectRaw('COUNT(*) AS total_domains')
            ->selectRaw("SUM(CASE WHEN transfer_status IN ('validation_failed', 'success', 'failed') THEN 1 ELSE 0 END) AS processed_domains")
            ->selectRaw("SUM(CASE WHEN transfer_status = 'success' THEN 1 ELSE 0 END) AS success_domains")
            ->selectRaw("SUM(CASE WHEN transfer_status IN ('validation_failed', 'failed') THEN 1 ELSE 0 END) AS failed_domains")
            ->selectRaw("SUM(CASE WHEN transfer_status IN ('validating', 'ready_for_transfer', 'unlocking', 'getting_epp', 'creating_handle', 'transferring', 'transfer_requested', 'checking_transfer_status') THEN 1 ELSE 0 END) AS processing_domains")
            ->first();

        $batch = BulkTransferBatch::find($batchId);
        if (!$batch || !$statistics) {
            return;
        }

        $batch->total_domains = (int) $statistics->total_domains;
        $batch->processed_domains = (int) $statistics->processed_domains;
        $batch->success_domains = (int) $statistics->success_domains;
        $batch->failed_domains = (int) $statistics->failed_domains;

        if ((int) $statistics->processed_domains === 0 && (int) $statistics->processing_domains === 0) {
            $batch->status = BulkTransferBatch::STATUS_QUEUED;
        } elseif ((int) $statistics->processing_domains > 0 || (int) $statistics->processed_domains < (int) $statistics->total_domains) {
            $batch->status = BulkTransferBatch::STATUS_PROCESSING;
        } else {
            $batch->status = (int) $statistics->failed_domains > 0
                ? BulkTransferBatch::STATUS_COMPLETED_WITH_ERRORS
                : BulkTransferBatch::STATUS_COMPLETED;
        }

        $batch->save();
    }

    protected function storeTransferState(BulkTransferItem $item, array $transferState, $setRequestedAt = false)
    {
        if (!empty($transferState['status'])) {
            $item->op_transfer_status = $transferState['status'];
        }

        if (!empty($transferState['domain_id'])) {
            $item->op_domain_id = (int) $transferState['domain_id'];
        }

        if ($setRequestedAt && empty($item->transfer_requested_at)) {
            $item->transfer_requested_at = Carbon::now();
        }

        if (!empty($transferState['message'])) {
            $item->last_status_message = $transferState['message'];
        }

        $item->updated_at = Carbon::now();
        $item->save();
    }

    protected function buildPendingStatusMessage(array $transferState)
    {
        if (!empty($transferState['message'])) {
            return $transferState['message'];
        }

        if (!empty($transferState['status'])) {
            return sprintf(
                'Openprovider transfer is pending finalization with status %s.',
                $transferState['status']
            );
        }

        return 'Openprovider transfer is pending finalization.';
    }

    protected function buildTransferFailureMessage(array $transferState, $fallbackMessage)
    {
        if (!empty($transferState['message'])) {
            return $transferState['message'];
        }

        if (!empty($transferState['status'])) {
            return sprintf('%s Status: %s.', rtrim($fallbackMessage, '.'), $transferState['status']);
        }

        return $fallbackMessage;
    }

    protected function getExistingDomainRecord(BulkTransferItem $item)
    {
        if (!empty($item->domain_id)) {
            $domainRecord = Domain::find((int) $item->domain_id);
            if ($domainRecord) {
                return $domainRecord;
            }
        }

        return Domain::where('domain', $item->domain)->first();
    }

    protected function finalizeDomainRecord(Domain $domainRecord, array $transferState)
    {
        $renewalDate = $transferState['renewal_date'] ?? $transferState['expiration_date'] ?? null;
        if (empty($renewalDate)) {
            throw new \RuntimeException('Openprovider did not return a renewal date for transfer finalization.');
        }

        $domainRecord->registrar = 'openprovider';
        $domainRecord->expirydate = $this->formatWhmcsDate($renewalDate);
        $domainRecord->save();
    }

    protected function formatWhmcsDate($dateTime)
    {
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', (string) $dateTime, 'Europe/Amsterdam')->toDateString();
        } catch (\Throwable $e) {
            return Carbon::parse((string) $dateTime)->toDateString();
        }
    }
}
