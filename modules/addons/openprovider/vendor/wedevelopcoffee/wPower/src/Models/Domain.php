<?php
namespace WeDevelopCoffee\wPower\Models;

/**
 * Handle system
 */
class Domain extends \WHMCS\Domain\Domain {

    /**
     * Get the handles for this domain.
     */
    public function handles()
    {
        return $this->belongsToMany('WeDevelopCoffee\wPower\Models\Handle','wDomain_handle');
    }
}

