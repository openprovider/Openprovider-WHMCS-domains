<?php
namespace Tests\Controllers;

use WeDevelopCoffee\wPower\Controllers\AdminDispatcher;

class AdminDispatcherTest extends Base
{
    protected $level = 'admin';
    protected $dispatcherClass = AdminDispatcher::class;


    /**
     * Sample settings
     */

    protected $moduleType = 'registrar';
    protected $moduleName = 'wPowerModule';
    protected $moduleNamespace = '\Tests\dependencies';

    /**
     * Sample routes
     */
    protected $routes = [
        'some-page' => 'AdminController@somePage',
        'index' => 'AdminController@index',
    ];


    public function test_dispatch_with_action()
    {
        $action = 'some-page';

        $this->mockedModule->shouldReceive('getType')
            ->once()
            ->andReturn($this->moduleType);

        $this->mockedModule->shouldReceive('getName')
            ->once()
            ->andReturn($this->moduleName);

        $this->mockedWPower->shouldReceive('launch')
            ->with('\Tests\dependencies\Controllers\Admin\AdminController')
            ->andReturn( new \Tests\dependencies\Controllers\Admin\AdminController())
            ->once();

        $result = $this->dispatcher->dispatch($action, []);

        $this->assertEquals('successSomePage', $result);
    }

    public function test_dispatch_without_action()
    {
        $action = '';
        $this->mockedModule->shouldReceive('getType')
            ->once()
            ->andReturn($this->moduleType);

        $this->mockedModule->shouldReceive('getName')
            ->once()
            ->andReturn($this->moduleName);

        $this->mockedWPower->shouldReceive('launch')
            ->with('\Tests\dependencies\Controllers\Admin\AdminController')
            ->andReturn( new \Tests\dependencies\Controllers\Admin\AdminController())
            ->once();

        $result = $this->dispatcher->dispatch($action, []);

        $this->assertEquals('successIndex', $result);
    }
}