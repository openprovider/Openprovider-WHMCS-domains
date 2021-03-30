<?php

namespace WeDevelopCoffee\wPower\Core;

/**
 * Class Launch
 * @package WeDevelopCoffee\wPower\Core
 */
class Launch
{
    /**
     * @var Core object  \WeDevelopCoffee\wPower\Core\Core::class
     */
    protected $core;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Instance
     */
    private $instance;

    /**
     * @var Hooks
     */
    private $hooks;

    public function __construct(Core $core, Router $router, Instance $instance, Hooks $hooks)
    {
        $this->core = $core;
        $this->router = $router;
        $this->instance = $instance;
        $this->hooks = $hooks;
    }

    /**
     * Return the output for the specific route.
     *
     * @param $params
     * @param $action
     */
    public function output($params, $action = '')
    {
        if($action == '')
            $action = 'index';

        $route      = $this->router->findRoute($action);

        $this->instance->createInstance($route['class']);

        return $this->instance->execute($route['function'], $params);
    }

    /**
     * Listen for all configured hooks.
     */
    public function hooks()
    {
        $routes = $this->router->getRoutes();

        foreach($routes as $key => $route)
        {
            if(!isset($route['priority']))
                $route['priority'] = '1';

            /**
             * Generate a hook. We'll be linking $this as controller and generate a hook name.
             * The __call method will create the required instance to launch the hook.
             */
            $hookFunctionName = 'hook_' . $key;

            // Add the hook.
            $this->hooks->add_hook($route['hookPoint'], $route['priority'], [$this, $hookFunctionName]);
        }
    }

    /**
     * Hooks into the
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $action = str_replace('hook_', '', $name);

        $route      = $this->router->findRoute($action);

        $this->instance->createInstance($route['class']);

        return $this->instance->execute($route['function'], $arguments[0]);
    }
}
