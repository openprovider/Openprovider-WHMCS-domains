<?php
namespace WeDevelopCoffee\wPower\Controllers;

/**
 * Hooks controller dispatcher.
 */
class HooksDispatcher extends Dispatcher {
    /**
     * Define the level
     *
     * @var string
     */
    protected $level = 'hooks';

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    public function dispatch()
    {   
        foreach($this->routes as $key => $route)
        {
            if(!isset($route['priority']))
                $route['priority'] = '1';

            $controllerNameAndFunction = $this->getControllerNameAndFunction($key);

            $controller = $this->getController($controllerNameAndFunction['controller']);
            $function   = $controllerNameAndFunction['function'];
            
            add_hook($route['hookPoint'], $route['priority'], [$controller, $function]);
        }
    }
}
