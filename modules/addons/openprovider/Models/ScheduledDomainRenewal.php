<?php
namespace OpenProvider\WhmcsDomainAddon\Models;

use Illuminate\Database\Eloquent\Model;
use WeDevelopCoffee\wPower\Core\Paginator;

/**
 * Domain model
 */
class ScheduledDomainRenewal extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mod_op_scheduled_domain_renewals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['domain'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'original_expiry_date',
        'new_expiry_date',
    ];

    /**
     * Get the phone record associated with the user.
     */
    public function tbldomain()
    {
        return $this->belongsTo('OpenProvider\WhmcsDomainAddon\Models\Domain', 'domain_id', 'id');
    }

}
