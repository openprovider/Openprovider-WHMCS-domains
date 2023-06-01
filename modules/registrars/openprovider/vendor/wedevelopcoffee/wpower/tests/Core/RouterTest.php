<?php

namespace WeDevelopCoffee\wPower\Tests\Launch;

use Mockery;
use WeDevelopCoffee\wPower\Tests\TestCase;
use phpmock\mockery\PHPMockery;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Router;

class RouterTest extends TestCase
{
    protected $router;
    protected $mockedCore;
    protected $mockedModule;
    protected $mockedPath;

    public function test_get_url_with_addon_url_without_params()
    {
        $_SERVER['REQUEST_URI']     = $this->testData['adminAddonUrl'] . '?module=' . $this->testData['moduleName'];
        $_SERVER['DOCUMENT_URI']    = $this->testData['adminAddonUrl'];

        $result = $this->router->getURL();

        $this->assertEquals($this->testData['adminAddonUrl']  . '?module=' . $this->testData['moduleName'], $result);
    }

    public function test_get_url_with_addon_url_with_params()
    {
        $url = $this->testData['adminAddonUrl'];
        $_SERVER['REQUEST_URI']     = $url . '?module=' . $this->testData['moduleName'];
        $_SERVER['DOCUMENT_URI']    = $url;

        $this->router->setParams(['test' => 'testvalue']);
        $result = $this->router->getURL();

        $this->assertEquals( $this->testData['adminAddonUrl'] . '?module=' . $this->testData['moduleName'] . '&test=testvalue', $result);
    }

    public function test_get_url_with_normal_url()
    {
        $url = $this->testData['adminAddonUrl'];
        $_SERVER['REQUEST_URI']     = $url;
        $_SERVER['DOCUMENT_URI']    = $url;

        $result = $this->router->getURL();

        $this->assertEquals( $this->testData['adminAddonUrl'] . '?', $result);
    }

    public function test_get_admin_url()
    {
        $this->prep_local_api_url();
        $url = $this->testData['adminUrl'];
        $_SERVER['REQUEST_URI']     = $url;
        $_SERVER['DOCUMENT_URI']    = $url;

        $GLOBALS['whmcs'] = Mockery::mock( \whmcs::class);
        $GLOBALS['whmcs']->shouldReceive('get_admin_folder_name')
            ->once()
            ->andReturn($this->testData['customAdminFolder']);

        $result = $this->router->getAdminURL();

        $this->assertEquals( $this->testData['baseUrl'] . $this->testData['customAdminFolder'] . '/', $result);
    }

    public function test_get_base_url()
    {
        $this->prep_local_api_url();

        $result = $this->router->getBaseURL();

        $this->assertEquals($this->testData['baseUrl'], $result);
    }

    public function test_get_addon_url()
    {
        $this->prep_local_api_url();

        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $this->mockedCore->shouldReceive('getModuleName')
            ->once()
            ->andReturn($this->testData['moduleName']);

        $result = $this->router->getAddonURL();

        $this->assertEquals($this->testData['addonUrl'], $result);
    }

    public function test_get_current_url_without_remove_params()
    {
        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $_SERVER['REQUEST_URI'] = $this->testData['adminAddonUrl'];
        $_SERVER['DOCUMENT_URI'] = $this->testData['adminAddonUrl'];

        $result = $this->router->getCurrentURL();
        $this->assertEquals($this->testData['adminAddonUrl'] . '?', $result);
    }

    public function test_get_current_url_with_remove_params()
    {
        $_SERVER['DOCUMENT_URI'] = $this->testData['adminAddonUrl'];
        $_SERVER['REQUEST_URI'] = $this->testData['adminAddonUrl'] . '?extra_param=value';

        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(false);

        $result = $this->router->getCurrentURL(['extra_param']);
        $this->assertEquals($this->testData['adminAddonUrl'] . '?', $result);
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

    public function test_find_route()
    {
        $routePath = dirname(__FILE__).'/../dependencies/';
        $routes = include($routePath . 'routes/admin.php');

        $this->mockedCore->shouldReceive('getLevel')
            ->times(3)
            ->andReturn('admin');

        $this->mockedPath->shouldReceive('getModulePath')
            ->times(2)
            ->andReturn($routePath);

        $result = $this->router->getRoutes();
        $this->assertEquals($routes, $result);

        $foundRoute = $this->router->findRoute('index');

        $expectation = [
            'class' => 'SupportController',
            'function' => 'index'
        ];

        $this->assertEquals($foundRoute, $expectation);
    }

    /**
     * setUp
     *
     */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedPath = Mockery::mock(Path::class);

        $this->router   = new Router($this->mockedCore, $this->mockedPath);
    }

    /**
     * Helpers
     *
     */
    protected function prep_local_api_url($url = '')
    {
        if($url == '')
            $url = $this->testData['baseUrl'];

        $mock = PHPMockery::mock('WeDevelopCoffee\wPower\Core', "localAPI")
            ->with('GetConfigurationValue', ['setting' => 'SystemURL'])
            ->once()
            ->andReturn(['value' => $url]);
    }

}
