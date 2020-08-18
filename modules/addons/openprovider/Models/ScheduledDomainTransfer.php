<?php
namespace OpenProvider\WhmcsDomainAddon\Models;

use Illuminate\Database\Eloquent\Model;
use WeDevelopCoffee\wPower\Core\Paginator;

/**
 * Domain model
 */
class ScheduledDomainTransfer extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mod_op_scheduled_domain_transfers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['domain'];

    /**
     * Get the phone record associated with the user.
     */
    public function tbldomain()
    {
        return $this->belongsTo('OpenProvider\WhmcsDomainAddon\Models\Domain', 'domain_id', 'id');
    }

}
