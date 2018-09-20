<?php
namespace WeDevelopCoffee\wPower\Controllers;

/**
 * Sample Admin Area Dispatch Handler
 */
class AdminDispatcher extends Dispatcher {

    /**
     * Define the user level
     *
     * @var string
     */
    protected $level = 'admin';

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    public function dispatch($action, $parameters)
    {
        parent::dispatch($action, $parameters);
    }
}
