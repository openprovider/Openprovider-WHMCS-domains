<?php
namespace OpenProvider\WhmcsDomainAddon\Models;
use WHMCS\Database\Capsule as DB;
// use OpenProvider\WhmcsDomainAddon\Lib\ViewFactory;
// use Illuminate\Pagination\Paginator;
use WeDevelopCoffee\wPower\Core\Paginator;

/**
 * Domain model
 */
class Domain extends \WHMCS\Domain\Domain {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['domain'];
}
