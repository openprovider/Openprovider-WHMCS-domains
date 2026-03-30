<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

use Carbon\Carbon;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferBatch;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferItem;
use OpenProvider\WhmcsDomainAddon\Models\Domain;
use WHMCS\Database\Capsule;

class BulkTransferProcessor
{
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

        $this->updateItemStatus($item, BulkTransferItem::STATUS_UNLOCKING);
        $this->registrarModuleInvoker->unlockDomain([
            'domainid' => (int) $domainRecord->id,
        ]);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_GETTING_EPP);
        $eppCode = $this->registrarModuleInvoker->getEppCode([
            'domainid' => (int) $domainRecord->id,
        ]);

        $moduleParams = $this->registrarModuleInvoker->buildModuleParams($domainRecord, $client);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_READY_FOR_TRANSFER);

        $this->updateItemStatus($item, BulkTransferItem::STATUS_CREATING_HANDLE);
        $handles = $this->openproviderTransferClient->createOrReuseTransferHandles($moduleParams);

        $item->op_owner_handle = $handles['owner_handle'];
        $item->op_admin_handle = $handles['admin_handle'];
        $item->op_tech_handle = $handles['tech_handle'];
        $item->op_billing_handle = $handles['billing_handle'];
        $item->save();

        $this->updateItemStatus($item, BulkTransferItem::STATUS_TRANSFERRING);
        $this->openproviderTransferClient->transferDomain([
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

        $this->markSuccess($item);
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

    protected function updateItemStatus(BulkTransferItem $item, $status)
    {
        $item->transfer_status = $status;
        $item->updated_at = Carbon::now();
        $item->save();
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
            ->selectRaw("SUM(CASE WHEN transfer_status IN ('validating', 'ready_for_transfer', 'unlocking', 'getting_epp', 'creating_handle', 'transferring') THEN 1 ELSE 0 END) AS processing_domains")
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
}
