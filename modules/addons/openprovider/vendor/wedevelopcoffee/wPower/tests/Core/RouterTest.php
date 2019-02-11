<?php
namespace Tests\Core;
use Mockery;
use Tests\TestCase;
use phpmock\mockery\PHPMockery;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Module\Module;

class RouterTest extends TestCase
{
    protected $router;
    protected $mockedCore;
    protected $mockedModule;
    protected $mockedPath;

    // Example test values
    protected $base_url = 'http://dev.domain.com/';
    protected $addon_url = 'http://dev.domain.com/admin/addonmodules.php';
    protected $raw_addon_url = 'http://dev.domain.com/modules/addons/wedevelopcoffee/';
    protected $addon_module_name = 'wedevelopcoffee';
    protected $custom_admin_folder = 'custom-admin-folder';

    public function test_get_url_with_addon_url_without_params()
    {
        $url = '';
        $_SERVER['REQUEST_URI']     = $this->addon_url . '?module=wedevelopcoffee';
        $_SERVER['DOCUMENT_URI']    = $this->addon_url;

        $result = $this->router->getURL();

        $this->assertEquals($this->addon_url  . '?module=wedevelopcoffee', $result);
    }

    public function test_get_url_with_addon_url_with_params()
    {
        $url = 'http://dev.domain.com/admin/addonmodules.php';
        $_SERVER['REQUEST_URI']     = $url . '?module=wedevelopcoffee';
        $_SERVER['DOCUMENT_URI']    = $url;

        $this->router->setParams(['test' => 'testvalue']);
        $result = $this->router->getURL();

        $this->assertEquals( $this->addon_url . '?module=wedevelopcoffee&test=testvalue', $result);
    }

    public function test_get_url_with_normal_url()
    {
        $url = 'http://dev.domain.com/admin/addonmodules.php';
        $_SERVER['REQUEST_URI']     = $url;
        $_SERVER['DOCUMENT_URI']    = $url;

        $result = $this->router->getURL();

        $this->assertEquals( $this->addon_url . '?', $result);
    }

    public function test_get_admin_url()
    {
        $this->prep_local_api_url();
        
        $GLOBALS['whmcs'] = Mockery::mock( \whmcs::class);
        $GLOBALS['whmcs']->shouldReceive('get_admin_folder_name')
            ->once()
            ->andReturn($this->custom_admin_folder);

        $result = $this->router->getAdminURL();

        $this->assertEquals( $this->base_url . $this->custom_admin_folder . '/', $result);
    }

    public function test_get_base_url()
    {
        $this->prep_local_api_url();
        
        $result = $this->router->getBaseURL();

        $this->assertEquals($this->base_url, $result);
    }

    public function test_get_addon_url()
    {
        $this->prep_local_api_url();

        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $this->mockedModule->shouldReceive('getName')
            ->once()
            ->andReturn($this->addon_module_name);

        $result = $this->router->getAddonURL();

        $this->assertEquals($this->raw_addon_url, $result);
    }

    public function test_get_current_url_without_remove_params()
    {
        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $_SERVER['REQUEST_URI'] = $this->addon_url;
        $_SERVER['DOCUMENT_URI'] = $this->addon_url;

        $result = $this->router->getCurrentURL();
        $this->assertEquals('http://dev.domain.com/admin/addonmodules.php?', $result);
    }

    public function test_get_current_url_with_remove_params()
    {
        $_SERVER['DOCUMENT_URI'] = $this->addon_url;
        $_SERVER['REQUEST_URI'] = $this->addon_url . '?extra_param=value';

        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $result = $this->router->getCurrentURL(['extra_param']);
        $this->assertEquals('http://dev.domain.com/admin/addonmodules.php?', $result);
    }

    public function test_load_routes()
    {
        $routePath = dirname(__FILE__).'/../dependencies/';
        $routes = include($routePath . 'routes/admin.php');

        $this->mockedCore->shouldReceive('getLevel')
            ->once()
            ->andReturn('admin');
        
        $this->mockedPath->shouldReceive('getModulePath')
            ->once()
            ->andReturn($routePath);
        
        $result = $this->router->getRoutes();
        $this->assertEquals($routes, $result);
    }

    protected function prep_local_api_url($url = '')
    {
        if($url == '')
            $url = $this->base_url;
        
        $mock = PHPMockery::mock('WeDevelopCoffee\wPower\Core', "localAPI")
            ->with('GetConfigurationValue', ['setting' => 'SystemURL'])
            ->once()
            ->andReturn(['value' => $url]);
    }
    
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedModule = Mockery::mock(Module::class);
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedPath = Mockery::mock(Path::class);

        $this->router   = new Router($this->mockedModule, $this->mockedPath, $this->mockedCore);
    }
    
    
}