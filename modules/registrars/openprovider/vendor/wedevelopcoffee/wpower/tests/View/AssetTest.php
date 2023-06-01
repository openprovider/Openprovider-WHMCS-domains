<?php
namespace WeDevelopCoffee\wPower\Tests\View;
use Mockery;
use WeDevelopCoffee\wPower\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\View\Asset;

class AssetTest extends TestCase
{
    protected $asset;
    
    public function test_asset_css ()
    {
        $this->prep_addonURL();
        $url             = 'https://domain.com/resources/assets/css/file.css';
        $params['css']   = 'file.css';
        
        // Tag
        $result          = $this->asset->asset($params);
        $expectedResult  = '<link rel="stylesheet" href="'.$url.'" type="text/css">';
        
        $this->assertEquals($result, $expectedResult);
        
        // Tag with extra params
        $params['rel']   = 'stylesheet';
        $result          = $this->asset->getCssTag($params);
        $expectedResult  = '<link rel="stylesheet" href="'.$url.'" type="text/css" rel="stylesheet">';
        $this->assertEquals($result, $expectedResult);
        
        // URL
        $result          = $this->asset->assetURL($params);
        $expectedResult  = $url;
        
        $this->assertEquals($result, $expectedResult);
    }
    
    public function test_asset_js ()
    {
        $this->prep_addonURL();
        $url             = 'https://domain.com/resources/assets/js/file.js';
        $params['js']    = 'file.js';
        
        // Tag
        $result          = $this->asset->asset($params);
        $expectedResult  = '<script src="'.$url.'"></script>';
        
        $this->assertEquals($result, $expectedResult);
        
        // Tag with extra params
        $params['async'] = 'async';
        $result          = $this->asset->getJsTag($params);
        $expectedResult  = '<script src="'.$url.'" async="async"></script>';
        $this->assertEquals($result, $expectedResult);
        
        // URL
        $result          = $this->asset->assetURL($params);
        $expectedResult  = $url;
        
        $this->assertEquals($result, $expectedResult);
    }
    
    public function test_asset_jpg ()
    {
        $this->prep_addonURL();
        $url             = 'https://domain.com/resources/assets/img/file.jpg';
        $params['img']   = 'file.jpg';
        
        // Tag
        $result          = $this->asset->asset($params);
        $expectedResult  = '<img src="'.$url.'">';
        
        $this->assertEquals($result, $expectedResult);
        
        // Tag with extra params
        $params['alt']   = 'ALT_TEXT';
        $result          = $this->asset->getImgTag($params);
        $expectedResult  = '<img src="'.$url.'" alt="ALT_TEXT">';
        $this->assertEquals($result, $expectedResult);
        
        // URL
        $result          = $this->asset->assetURL($params);
        $expectedResult  = $url;
        
        $this->assertEquals($result, $expectedResult);
    }
    
    /**
    * prep_addonURL
    * 
    * @return 
    */
    public function prep_addonURL ()
    {
        $this->mockedRouter->shouldReceive('getAddonURL')
        ->times(3)
        ->andReturn('https://domain.com/');
    }
    
    /**
    * setUp
    * 
    * @return void
    */
    public function setUp ()
    {
        $this->mockedRouter = Mockery::mock(Router::class);
        $this->asset = new Asset($this->mockedRouter);
    }
    
}