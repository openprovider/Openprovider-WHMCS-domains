<?php
namespace WeDevelopCoffee\wPower\Controllers;

/**
 * Client controller dispatcher.
 */
class ClientDispatcher extends Dispatcher {

    /**
     * Define the user level
     *
     * @var string
     */
    protected $level = 'client';

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
        $this->launch($action, $parameters);
    }
}
