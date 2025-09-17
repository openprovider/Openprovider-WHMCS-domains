<?php
namespace WeDevelopCoffee\wPower\Handles\Models;

use \Exception;
use WeDevelopCoffee\wPower\Models\Domain;
use WHMCS\Database\Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use WeDevelopCoffee\wPower\Handles\Exception\HandleUsedByMultipleDomains;

/**
 * Handle system
 */
class Handle extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wHandles';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the data
     *
     * @param  string  $value
     * @return string
     */
    public function getDataAttribute($value)
    {
        $data = unserialize($value);

        return $data;
    }

    /**
     * Set the data
     *
     * @param  string  $value
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = serialize($value);
    }

    /**
     * Check if the handle is used for one handle only.
     *
     * @param integer $domainId to filter out.
     * @return boolean true if unique | false if the handle is used by multiple domains.
     */
    public function isUsedByOtherDomains ($domainId)
    {
        $related_domains = $this->domains()
            ->wherePivot('domain_id', '!=', $domainId)->count();

        // Multiple domains are using this handle.
        if($related_domains == 0)
            return false;

        return true;
    }

    /**
     * Find an existing handle
     *
     * @return object|boolean Array with possible handles or false when nothing.
     */
    public function findExisting ()
    {
        try
        {
            $result = $this->where('data', $this->attributes['data'])
                ->where('user_id', $this->user_id)
                ->where('registrar', $this->registrar)
                ->where(function ($query) {
                    $query->where('type', $this->type)
                        ->orWhere('type', 'all');
                })
                ->where('handle', '!=', '')
                ->firstOrFail();
        }
        catch ( ModelNotFoundException $e)
        {
            return false;
        }

        return $result;
    }

    /**
     * Save with the domain id.
     *
     * @return $this
     */
    public function saveWithDomain ($domainId, $type = 'all')
    {
        if($type == null)
            $type = 'all';

        // Prevent that the same is created twice.
        if(!$existingHandle = $this->findExisting())
            $this->save();
        else
        {
            $this->id       = $existingHandle->id;
            $this->exists   = true;
        }

        $domain = Domain::find($domainId);

        if($type != 'all')
        {
            try {
                $sync[] = $this->generateSync('registrant', $domain);
            } catch ( \Exception $e)
            {
                $sync[]= ['handle_id' => $this->id, 'type' => 'registrant'];
            }

            try {
                $sync[] = $this->generateSync('billing', $domain);
            } catch ( \Exception $e)
            {
                $sync[]= ['handle_id' => $this->id, 'type' => 'billing'];
            }

            try {
                $sync[] = $this->generateSync('admin', $domain);
            } catch ( \Exception $e)
            {
                $sync[]= ['handle_id' => $this->id, 'type' => 'admin'];
            }

            try {
                $sync[] = $this->generateSync('tech', $domain);
            } catch ( \Exception $e)
            {
                $sync[]= ['handle_id' => $this->id, 'type' => 'tech'];
            }
        }
        else
        {
            // The type is all, so we configure the handle for everything.

            $sync[]= ['handle_id' => $this->id, 'type' => 'registrant'];
            $sync[]= ['handle_id' => $this->id, 'type' => 'billing'];
            $sync[]= ['handle_id' => $this->id, 'type' => 'admin'];
            $sync[]= ['handle_id' => $this->id, 'type' => 'tech'];
        }

        $domain->handles()->sync($sync);

        return $this;
    }

    /**
     * Generate sync with one change.
     *
     * @param string $type
     * @param object $model \WeDevelopCoffee\wPower\Models\Domain
     * @return array $sync
     */
    protected function generateSync ($type, $domain)
    {
        if($this->type == $type)
            $handleId = $this->id;
        else
        {
            try
            {
                $handleId = $domain
                    ->handles()
                    ->wherePivot('type', $type)
                    ->firstOrFail()->id;

            } catch ( ModelNotFoundException $e)
            {
                throw new Exception('Handle not found');
            }
        }

        $sync = ['handle_id' => $handleId, 'type' => $type];

        return $sync;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        // If the model already exists in the database we need to make sure that it unique.
        if ($this->exists && !isset($options['overrideUniqueCheck'])) {
            if($this->isUsedByOtherDomains())
            {
                throw new HandleUsedByMultipleDomains();
            }
        }
        elseif(isset($options['overrideUniqueCheck']))
            unset($options['overrideUniqueCheck']);

        $return = parent::save($options);
    }

    /**
     * Has mall
     *
     * @return void
     */
    public function domains()
    {
        return $this->belongsToMany('WeDevelopCoffee\wPower\Models\Domain','wDomain_handle');
    }

    /**
     * Find an existing handle for a given user ID, registrar, and type.
     *
     * @param int $userId
     * @param string $type
     * @return Handle|false
     */
    public static function findExistingByUserId(int $userId, string $type = 'all')
    {
        try {
            return self::where('user_id', $userId)
                ->where(function ($query) use ($type) {
                    $query->where('type', $type)
                        ->orWhere('type', 'all');
                })
                ->where('handle', '!=', '')
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }
}
