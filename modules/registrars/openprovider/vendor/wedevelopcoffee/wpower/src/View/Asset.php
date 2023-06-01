<?php
namespace WeDevelopCoffee\wPower\View;
use WeDevelopCoffee\wPower\Core\Router;

class Asset
{

    /**
     * The Router instance
     *
     * @var object
     */
    protected $router;

    /**
     * Constructor
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
    * asset
    * $params
    * @return $params
    */
    public function asset ($params)
    {
        if(isset($params['css']))
            return $this->getCssTag($params);
        
        elseif(isset($params['js']))
            return $this->getJsTag($params);
        
        elseif(isset($params['img']))
            return $this->getImgTag($params);
    }

    /**
    * asset
    * $params
    * @return $params
    */
    public function assetURL ($params)
    {
        if(isset($params['css']))
            return $this->getCssURL($params);
        
        elseif(isset($params['js']))
            return $this->getJsURL($params);
        
        elseif(isset($params['img']))
            return $this->getImgURL($params);
    }

    /**
    * parseParams
    * @param array $params All additional parameters
    * @param string $skip Skip this key.
    * @return $html
    */
    protected function parseParams ($params, $skip)
    {
        // Skip the main key
        unset($params[$skip]);
        
        $html = '';
        
        // Check if any params are left.
        if(count($params) != 0)
        {
            foreach($params as $key => $value)
            {
                $html .= ' ' . $key . '="'.$value.'"';
            }
        }
        return $html;
    }

    /**
    * getCssTag
    * 
    * Returns the CSS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getCssTag ($params)
    {
        $url = $this->getCssURL($params);
        
        $additional_params = $this->parseParams($params, 'css');
        $html = '<link rel="stylesheet" href="'.$url.'" type="text/css"'.$additional_params.'>'; 

        return $html;
    }

    /**
    * getJsTag
    * 
    * Returns the JS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getJsTag ($params)
    {
        $url = $this->getJsURL($params);
        $additional_params = $this->parseParams($params, 'js');
        $html = '<script src="'.$url.'"'.$additional_params.'></script>'; 

        return $html;
    }

    /**
    * getImgTag
    * 
    * Returns the JS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getImgTag ($params)
    {
        $url = $this->getImgURL($params);
        $additional_params = $this->parseParams($params, 'img');
        $html = '<img src="'.$url.'"'.$additional_params.'>'; 

        return $html;
    }

    /**
    * getCssURL
    * 
    * Returns the CSS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getCssURL ($params)
    {
        return $this->getResourcesURL('css') . $params['css'];
    }

    /**
    * getJsURL
    * 
    * Returns the JS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getJsURL ($params)
    {
        
        return $this->getResourcesURL('js') . $params['js'];
    }

    /**
    * getImgUrl
    * 
    * Returns the JS tag.
    * 
    * @param array $params
    * @return $html
    */
    public function getImgURL ($params)
    {
        return $this->getResourcesURL('img') . $params['img'];
    }

    /**
    * getResourcesURL
    * 
    * @param string $type img|css|js
    * @return string $url
    */
    protected function getResourcesURL ($type)
    {
        return $this->router->getAddonURL() . 'resources/assets/' . $type .'/';
    }
}
