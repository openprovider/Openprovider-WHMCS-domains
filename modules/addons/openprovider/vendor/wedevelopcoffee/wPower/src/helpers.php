<?php
use WeDevelopCoffee\wPower\Router;
use Illuminate\Pagination\Paginator;

if (! function_exists('wLaunch')) {
    /**
     * Automatic dependency injection.
     *
     * @param  string  $view
     * @param  array   $data
     * @return object
     */
    function wLaunch($class)
    {
        $arguments = func_get_args();
        $arguments[0] = $class;

        $launcher = new WeDevelopCoffee\wPower\Core\Launcher();

        return call_user_func_array([$launcher, 'launchClass'], $arguments);
    }
}

if (! function_exists('wPower')) {
    /**
     * Returns the global wPower class.
     *
     * @param  string  $view
     * @param  array   $data
     * @return \Smarty
     */
    function wPower()
    {
        // Return the same instance to everybody.
        if(!isset($_GLOBAL['wPower']))
            $_GLOBAL['wPower'] = wLaunch('WeDevelopCoffee\wPower\wPower');
        
        return $_GLOBAL['wPower'];
    }
}

if (! function_exists('wView')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @return \Smarty
     */
    function wView($view, $data = [])
    {
        return wPower()
            ->View()
            ->setData($data)
            ->setView($view)
            ->render();
    }
}

/**
 * Launch the system
 */
if (!class_exists('PHPUnit\Framework\TestCase')) 
    wPower()->Bootstrap();

