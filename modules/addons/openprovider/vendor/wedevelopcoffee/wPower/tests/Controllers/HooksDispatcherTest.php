<?php
namespace Tests\Controllers;

use WeDevelopCoffee\wPower\Controllers\HooksDispatcher;

class HooksDispatcherTest extends Base
{
    protected $level = 'hooks';
    protected $dispatcherClass = HooksDispatcher::class;

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
        'some-hook-point' => [
                    'hookPoint' => 'SomeWhmcsHookPoint',
                    'priority' =>  1,
                    'controller' => 'HookController@some'
                ],
        'index' => [
            'hookPoint' => 'indexWhmcsHookPoint',
            'priority' =>  1,
            'controller' => 'HookController@index'
        ],
    ];


    public function test_dispatch_with_action()
    {
        $action = 'some-hook-point';

        $this->mockedModule->shouldReceive('getType')
            ->twice()
            ->andReturn($this->moduleType);

        $this->mockedModule->shouldReceive('getName')
            ->twice()
            ->andReturn($this->moduleName);

        $this->dispatcher->launch();

        // Result is stored in GLOBALS
        $result = $GLOBALS['test']['add_hook'];

        // Set expectations
        $expectedResult[] = [
            'hookPoint' => 'SomeWhmcsHookPoint',
            'priority' => $this->routes[$action]['priority'],
            'function' => [
                0 => new \Tests\dependencies\Controllers\Hooks\HookController(),
                1 => 'some'
            ]
        ];
        $expectedResult[] = [
            'hookPoint' => 'indexWhmcsHookPoint',
            'priority' => $this->routes[$action]['priority'],
            'function' => [
                    0 => new \Tests\dependencies\Controllers\Hooks\HookController(),
                    1 => 'index'
                ]
        ];

        $this->assertEquals($expectedResult, $result);
    }


    /**
     * Done
     */
    public function tearDown()
    {
        unset($GLOBALS['test']['add_hook']);
    }
}