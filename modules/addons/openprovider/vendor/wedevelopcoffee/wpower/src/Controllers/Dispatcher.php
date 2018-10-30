<?php
namespace WeDevelopCoffee\wPower\Controllers;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Module\Module;


/**
 * Controller dispatcher
 */
class Dispatcher
{
    /**
     * All the routers
     * 
     * @param array
     */
    protected $routes;

    /**
     * The router instance
     *
     * @var object
     */
    protected $router;
    
    /**
     * The requested action.
     *
     * @var string
     */
    protected $action;

    /**
     * Define the user level
     *
     * @var string
     */
    protected $level = 'hooks';

    /**
     * Core
     *
     * @var object
     */
    protected $core;

    /**
     * Module
     *
     * @var object
     */
    protected $module;

    /**
     * Constructor
     */
    public function __construct(Core $core, Router $router, Module $module)
    {
        $this->core = $core;
        $this->core->setLevel($this->level);

        $this->router = $router;
        $this->routes = $router->getRoutes();

        $this->module = $module;
    }

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
        if($action == '')
            $action = 'index';


        $this->action = $action;

        $controllerNameAndFunction = $this->getControllerNameAndFunction();

        $controller = $this->getController($controllerNameAndFunction['controller']);

        $launch_controller = new $controller();

        $functionName = (string) $controllerNameAndFunction['function'];
        return $launch_controller->$functionName();
    }

    /**
     * Try to find the expected controller.
     *
     * @return void
     */
    protected function getController($controller)
    {
        $regex = '/([a-zA-Z0-9_]*)$/A';
        
        preg_match($regex, $controller, $matches);
        
        // Print the entire match result
        if(!isset($matches[0]) || $matches[0] == '')
        {
            // It is an exact path
            return $controller;
        }
        else
        {
            $namespace = $this->getExpectedNameSpace();
            return $namespace . '\\' . $controller;
        }
    }

    /**
     * Get the controller name and function
     *
     * @return void
     */
    protected function getControllerNameAndFunction($key = null)
    {
        if($key === null)
            $key = $this->action;

        if(!$rawController = $this->routes[$key])
        {
            throw new \Exception ('NOT FOUND');
        }
            

        if($this->level == 'hooks')
            $rawController = $rawController['controller'];

        $rawController = explode('@', $rawController);

        $controller = $rawController[0];

        if(isset($rawController[1]))
            $function = $rawController[1];
        else
        {
            $function = 'index';
        }

        return ['controller' => $controller, 'function' => $function];
    }

    /**
     * Get the expected namespace
     *
     * @return string The namespace
     */
    protected function getExpectedNameSpace()
    {
        $type = $this->module->getType();
        $name = $this->module->getName();

        if($this->level == 'admin')
            return $GLOBALS['wAutoloader'][$type][$name]['namespace'] . '\Controllers\Admin';
        elseif($this->level == 'client')
            return $GLOBALS['wAutoloader'][$type][$name]['namespace'] . '\Controllers\Client';
        elseif($this->level == 'hooks')
            return $GLOBALS['wAutoloader'][$type][$name]['namespace'] . '\Controllers\Hooks';
    }
}
