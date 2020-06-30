<?php

namespace WeDevelopCoffee\wPower\Core;

use Exception;

/**
 * Class Router
 * @package WeDevelopCoffee\wPower\Core
 */
/**
 * Router for WHMCS
 */
class Router {
    /**
     * All routes
     *
     * @var array
     */
    protected $routes;

    /**
     * The current route.
     *
     * @var string
     */
    protected $route;

    /**
     * The admin route.
     *
     * @var string
     */
    protected $adminRoute;

    /**
     * All URL parameters.
     *
     * @var array
     */
    protected $params = [];

    /**
     * The module instance
     *
     * @var object
     */
    protected $module;

    /**
     * The path instance
     *
     * @var object
     */
    protected $path;

    /**
     * The core instance
     *
     * @var object
     */
    protected $core;

    /**
     * Router constructor.
     * @param Core $core
     * @param Path $path
     */
    public function __construct(Core $core, Path $path)
    {
        $this->path = $path;
        $this->core = $core;
    }

    /**
     * Return the route.
     *
     * @param $action
     */
    public function findRoute($action)
    {
        $this->loadRoutes();

        if(!isset($this->routes[$action]))
            throw new Exception('Route does not exist.');

        $route = $this->routes[$action];

        if($this->core->getLevel() == 'hooks')
            $route = $route['controller'];

        $expl_route = explode('@', $route);
        $controller = $expl_route[0];

        if(isset($expl_route[1]))
            $function = $expl_route[1];
        else
            $function = 'index';

        return ['class' => $controller, 'function' => $function];
    }

    /**
     * Generate the URL
     *
     * @return string $url
     */
    public function getURL()
    {
        if(!isset($_SERVER['DOCUMENT_URI']))
            $_SERVER['DOCUMENT_URI'] = $_SERVER['REQUEST_SCHEME'] . '://' .$_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

        $url = $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);

        if(isset($parse_url['query']))
        {
            parse_str($parse_url['query'], $query_items);
            foreach($query_items as $key => $value)
            {
                if($key != 'module')
                    unset($query_items[$key]);
            }

            if(is_array($query_items))
                $query_items = array_merge($query_items, $this->params);
            else
                $query_items = $this->params;
        }
        else
            $query_items = $this->params;

        if($this->adminRoute == '')
            $query_items['action'] = $this->route;
        else
            $query_items['action'] = $this->adminRoute;

        if(!empty($query_items))
            $url = $_SERVER['DOCUMENT_URI'] .'?'.http_build_query($query_items);
        else
            $url = $_SERVER['DOCUMENT_URI'];

        return $url;
    }

    /**
     * getCurrentURL
     *
     * @param array $removeParam
     *
     * @return string
     */
    public function getCurrentURL ( array $removeParam = [])
    {
        if($this->core->isCli())
            return false;
        $url = $_SERVER['REQUEST_URI'];
        $query_items = [];
        $parse_url = parse_url($url);
        if(isset($parse_url['query']))
        {
            parse_str($parse_url['query'], $original_query_items);

            foreach($original_query_items as $key => $value)
            {
                $key = str_replace('amp;', '', $key);
                $query_items [ $key ] = $value;
            }
            if(!empty($removeParam))
            {
                foreach($removeParam as $param)
                {
                    if(isset($query_items[$param]))
                        unset($query_items[$param]);
                }
            }
        }
        $url_path =  $_SERVER['DOCUMENT_URI'] .'?'.http_build_query($query_items);

        return $url_path;
    }

    /**
     * Generate the URL
     *
     * @return string $url
     */
    public function getAdminURL()
    {
        $url = $this->getBaseURL() . $GLOBALS['whmcs']->get_admin_folder_name() . '/';
        return $url;
    }

    /** URL PATHS */
    /**
     * getBaseURl
     *
     * @return string
     */
    public function getBaseURL ()
    {
        $results = localAPI('GetConfigurationValue', ['setting' => 'SystemURL']);
        return $results['value'];
    }

    /**
     * getAddonUrl
     *
     * @return string
     */
    public function getAddonURL ()
    {
        if($this->core->isCli())
            return false;
        return $this->getBaseURL() . 'modules/addons/' . $this->core->getModuleName() . '/';
    }

    /**
     * Load all routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        $level  = $this->core->getLevel();

        $path   = $this->path->getModulePath() . 'routes/' . $level . '.php';

        // Include the routes.
        $this->routes = include( $path);
    }

    /**
     * Get all routes
     *
     * @return  array
     */
    public function getRoutes()
    {
        if(empty($this->routes))
            $this->loadRoutes();
        return $this->routes;
    }

    /**
     * Set the value of route
     *
     * @return  self
     */
    public function setRoute($route)
    {
        if($route == '')
            $route = 'index';

        $this->route = $route;

        return $this;
    }

    /**
     * Set the value of admin route
     *
     * @return  self
     */
    public function setAdminRoute($adminRoute)
    {
        $this->adminRoute = $adminRoute;

        return $this;
    }

    /**
     * Set the value of params
     *
     * @return  self
     */
    public function setParams($params)
    {
        unset($params['route']);

        $this->params = $params;

        return $this;
    }
}