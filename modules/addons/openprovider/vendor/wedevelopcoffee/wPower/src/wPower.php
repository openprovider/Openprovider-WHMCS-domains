<?php
namespace WeDevelopCoffee\wPower;

/**
 * Basic wPower functions
 */
class wPower {

    /**
     * All launched instances
     *
     * @var array
     */
    protected $instances;


    /**
    * Bootstrap
    * 
    */
    public function Bootstrap ()
    {
        /**
         * Setup the pagination data.
         */
        $this->View()->setupPagination();
    }

    /**
     * Return the Core class
     *
     * @return Core\Core
     */
    public function Core()
    {
        return $this->launch(Core\Core::class);
    }

    /**
     * Return the Module class
     *
     * @return Module\Module
     */
    public function Module()
    {
        return $this->launch(Module\Module::class);
    }

    /**
     * Return the Path class
     *
     * @return Core\Path
     */
    public function Path()
    {
        return $this->launch(Core\Path::class);
    }

    /**
     * Return the Router class
     *
     * @return Core\Router
     */
    public function Router()
    {
        return $this->launch(Core\Router::class);
    }

    /**
     * Return the View class
     *
     * @return View\View
     */
    public function View()
    {
        return $this->launch(View\View::class);
    }

    /**
     * Return the instance of $class
     *
     * @param class $class
     * @return instance
     */
    public function launch($class)
    {
        if(!isset($this->instances[$class]))
            $this->instances[$class] = wLaunch($class);

        return $this->instances[$class];
        
    }
    
}

