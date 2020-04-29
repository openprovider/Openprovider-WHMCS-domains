<?php

namespace WeDevelopCoffee\wPower\Tests\Core;

use Mockery;
use WeDevelopCoffee\wPower\Core\Hooks;
use WeDevelopCoffee\wPower\Core\Instance;
use WeDevelopCoffee\wPower\Core\Launch;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Tests\TestCase;

/**
 * Class LaunchTest
 * @package WeDevelopCoffee\wPower\Tests\Launch
 */
class LaunchTest extends TestCase
{
    protected $launch;
    protected $mockedCore;
    protected $mockedRouter;
    protected $mockedInstance;
    protected $mockedHooks;

    public function test_output_without_action()
    {
        // Configuration
        $action     = 'supportDownload';
        $function   = 'download';
        $params     = ['someParam'];

        $routerReturn = [
            'class' => 'SupportController',
            'function' => $function
        ];

        // Expectations
        $expectedReturn     = 'some-data';

        // Prepare mocks

        $this->mockedRouter->shouldReceive('findRoute')
            ->with($action)
            ->andReturn($routerReturn)
            ->once();

        $this->mockedInstance->shouldReceive('createInstance')
            ->with($routerReturn['class'])
            ->once();

        $this->mockedInstance->shouldReceive('execute')
            ->with($function, $params)
            ->once()
            ->andReturn($expectedReturn);

        $result = $this->launch->output($params, $action);

        $this->assertEquals($expectedReturn, $result);
    }

    public function test_adding_all_hooks()
    {
        // Configuration
        $routes = $this->testData['hookRoutes'];

        // Expectations
        //

        // Prepare mocks
        $this->mockedRouter->shouldReceive('getRoutes')
            ->once()
            ->andReturn($routes);

        foreach($routes as $key => $route) {
            $this->mockedHooks->shouldReceive('add_hook')
                ->with($route['hookPoint'], $route['priority'], [$this->launch, 'hook_' . $key])
                ->once();
        }

        // Execute
        $this->launch->hooks();

        // Just a fake assert, we'll just need to make sure that the mocks have run.
        $this->assertTrue(true);
    }

    public function test_run_hook ()
    {
        // Configuration
        $name       = 'some_hook';
        $arguments  = [
            'some-argument'
        ];

        $route      = [
            'class'     => 'some_class',
            'function'  => 'some_function'
        ];

        // Expectation
        $expectedReturnData = 'some-data';

        // Mock
        $this->mockedRouter->shouldReceive('findRoute')
            ->with($name)
            ->andReturn($route)
            ->once();

        $this->mockedInstance->shouldReceive('createInstance')
            ->with($route['class'])
            ->andReturn($expectedReturnData)
            ->once();

        $this->mockedInstance->shouldReceive('execute')
            ->with($route['function'], $arguments)
            ->andReturn($expectedReturnData)
            ->once();

        // Execute
        $functionName = 'hook_' . $name;

        $result = $this->launch->$functionName($arguments);

        // Assert
        $this->assertEquals($expectedReturnData, $result);
    }

    public function setUp()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedRouter = Mockery::mock(Router::class);
        $this->mockedInstance = Mockery::mock(Instance::class);
        $this->mockedHooks = Mockery::mock(Hooks::class);

        $this->launch   = new Launch($this->mockedCore, $this->mockedRouter, $this->mockedInstance, $this->mockedHooks);
    }

}