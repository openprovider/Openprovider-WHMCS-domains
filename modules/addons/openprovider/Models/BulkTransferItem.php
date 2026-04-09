<?php

namespace OpenProvider\WhmcsDomainAddon\Models;

use Illuminate\Database\Eloquent\Model;
use WeDevelopCoffee\wPower\Models\Client;

class BulkTransferItem extends Model
{
    const STATUS_QUEUED = 'queued';
    const STATUS_VALIDATING = 'validating';
    const STATUS_VALIDATION_FAILED = 'validation_failed';
    const STATUS_READY_FOR_TRANSFER = 'ready_for_transfer';
    const STATUS_UNLOCKING = 'unlocking';
    const STATUS_GETTING_EPP = 'getting_epp';
    const STATUS_CREATING_HANDLE = 'creating_handle';
    const STATUS_TRANSFERRING = 'transferring';
    const STATUS_TRANSFER_REQUESTED = 'transfer_requested';
    const STATUS_CHECKING_TRANSFER_STATUS = 'checking_transfer_status';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    protected $table = 'mod_op_bulk_transfer_items';

    /**
     * @var array
     */
    protected $fillable = [
        'batch_id',
        'client_id',
        'domain_id',
        'domain',
        'op_owner_handle',
        'op_admin_handle',
        'op_tech_handle',
        'op_billing_handle',
        'op_transfer_status',
        'op_domain_id',
        'transfer_status',
        'failure_reason',
        'transfer_requested_at',
        'last_status_check_at',
        'last_status_message',
        'attempt_count',
        'started_at',
        'finished_at',
    ];

    public function batch()
    {
        return $this->belongsTo(BulkTransferBatch::class, 'batch_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function domainRecord()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function isTerminalStatus()
    {
        return in_array($this->transfer_status, [
            self::STATUS_VALIDATION_FAILED,
            self::STATUS_SUCCESS,
            self::STATUS_FAILED,
        ], true);
    }

    public function isPendingTransferStatus()
    {
        return in_array($this->transfer_status, [
            self::STATUS_TRANSFER_REQUESTED,
            self::STATUS_CHECKING_TRANSFER_STATUS,
        ], true);
    }
}
