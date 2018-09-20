<?php
namespace WeDevelopCoffee\wPower\View;
use Smarty;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Core\Path;
use Illuminate\Pagination\Paginator;


class View
{
    /**
    * Data for the view
    *
    * @var array
    */
    protected $data;
    
    /**
    * The view
    *
    * @var string
    */
    protected $view;
    
    /**
    * The router instance
    *
    * @var object
    */
    protected $router;
    
    /**
    * The Asset instance
    *
    * @var object
    */
    protected $asset;
    
    /**
    * The Path instance
    *
    * @var object
    */
    protected $path;
    
    /**
    * Constructor
    *
    * @param Object $router
    * @param Object $asset
    * @param Object $path
    */
    public function __construct(Smarty $smarty, Router $router, Asset $asset, Path $path)
    {
        $this->smarty   = $smarty;
        $this->router   = $router;
        $this->asset    = $asset;
        $this->path     = $path;
    }
    
    /**
    * render
    * 
    * @return $template
    */
    public function render ()
    {
        $smarty = clone $this->smarty;
        $smarty->registerPlugin('function', 'get_route', [$this, 'getRoute']);
        $smarty->registerPlugin('function', 'get_admin_route', [$this, 'getAdminRoute']);
        $smarty->registerPlugin('function', 'get_current_url', [$this->router, 'getCurrentURL']);
        $smarty->registerPlugin('function', 'asset', [$this->asset, 'asset'] );
        $smarty->registerPlugin('function', 'asset_url', [$this->asset, 'assetURL'] );
        
        $smarty->assign($this->data);
        
        $views_path = $this->path->getAddonPath() . 'resources/views/';
        
        return $smarty->display( $views_path . $this->view . '.tpl');
    }

    /**
    * setupPagination
    * 
    * @return 
    */
    public function setupPagination ()
    {   
        // Pagination fix.
        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $_REQUEST[($pageName)];
            
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return $page;
            }
            
            return 1;
        });
        
    }
    
    /**
    * getRoute
    * $params
    * @return $route
    */
    public function getRoute ($var)
    {
        $route = $this->router->setRoute($var['route'])
        ->setParams($var)
        ->getURL();

        return $route;
    }
    
    /**
    * getRoute
    * $params
    * @return $route
    */
    public function getAdminRoute ($var)
    {
        $route = $this->router->setAdminRoute($var['route'])
        ->setParams($var)
        ->getURL();
        return $route;
    }
    
    
    /**
    * Get data for the view
    *
    * @return  array
    */ 
    public function getData()
    {
        return $this->data;
    }
    
    /**
    * Set data for the view
    *
    * @param  array  $data  Data for the view
    *
    * @return  self
    */ 
    public function setData(array $data)
    {
        $this->data = $data;
        
        return $this;
    }
    
    /**
    * Get the view
    *
    * @return  string
    */ 
    public function getView()
    {
        return $this->view;
    }
    
    /**
    * Set the view
    *
    * @param  string  $view  The view
    *
    * @return  self
    */ 
    public function setView( $view)
    {
        $this->view = $view;
        
        return $this;
    }
    
    
    
}
