
<?php
namespace WeDevelopCoffee\wPower\Handles\Exception;
/**
 * The handle is in use by multiple domians. Instead of updating, a new
 * handle is required.
 */
class HandleUsedByMultipleDomains extends \Exception {};