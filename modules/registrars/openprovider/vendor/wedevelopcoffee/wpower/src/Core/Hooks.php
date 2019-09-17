<?php

namespace WeDevelopCoffee\wPower\Core;

/**
 * Class Hooks
 * @package WeDevelopCoffee\wPower\Core
 */
class Hooks
{
    /**
     * Simple wrapper for adding a hook to WHMCS.
     *
     * @param $hookPoint
     * @param $priority
     * @param $function
     */
    public function add_hook($hookPoint, $priority, $function)
    {
        \add_hook($hookPoint, $priority, $function);
    }
}