<?php

namespace OpenProvider\WhmcsDomainAddon\Models;

use Illuminate\Database\Eloquent\Model;
use WeDevelopCoffee\wPower\Models\Admin;

class BulkTransferBatch extends Model
{
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    const STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    protected $table = 'mod_op_bulk_transfer_batches';

    /**
     * @var array
     */
    protected $fillable = [
        'bulk_reference',
        'reseller_id',
        'initiated_by_admin_id',
        'description',
        'total_domains',
        'processed_domains',
        'success_domains',
        'failed_domains',
        'status',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(BulkTransferItem::class, 'batch_id', 'id');
    }

    public function initiatedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'initiated_by_admin_id', 'id');
    }
}
