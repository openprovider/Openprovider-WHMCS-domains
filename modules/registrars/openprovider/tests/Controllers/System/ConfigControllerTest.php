<?php

namespace OpenProvider\WhmcsRegistrar\Tests\Controllers\System;

use Mockery;
use OpenProvider\API\API;
use OpenProvider\WhmcsRegistrar\Controllers\System\ConfigController;
use OpenProvider\WhmcsRegistrar\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class TestConfigController
 * @package OpenProvider\WhmcsRegistrar\Tests\Controllers\System
 */
class ConfigControllerTest extends TestCase
{
    /**
     * @var Mockery\MockInterface|Core
     */
    private $mockedCore;
    /**
     * @var Mockery\MockInterface|API
     */
    private $mockedAPI;
    /**
     * @var ConfigController
     */
    private $ConfigController;

    public function test_return_config_with_failed_login()
    {
        // Configure

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($this->params)
            ->once();

        $this->mockedAPI->shouldReceive('searchTemplateDnsRequest')
            ->andThrow(\Exception::class)
            ->once();

        // Execute
        $result = $this->ConfigController->getConfig($this->params);

        // Assert
        $this->assertIsArray($result);

        $this->assertContains('Tomato', $result['OpenproviderAPI']['FriendlyName']);
    }

    public function test_return_config_with_successful_login()
    {
        // Configure
        $templates = [
            'total' => 2,
            'results' => [
                0 => [
                    'name' => 'name-1'
                ],
                1 => [
                    'name' => 'name-2'
                ]
            ]
        ];

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($this->params)
            ->once();

        $this->mockedAPI->shouldReceive('searchTemplateDnsRequest')
            ->andReturn($templates)
            ->once();

        // Execute
        $result = $this->ConfigController->getConfig($this->params);

        // Assert
        $this->assertIsArray($result);

        $this->assertNotContains('Tomato', $result['OpenproviderAPI']['FriendlyName']);
        $this->assertEquals('None,name-1,name-2', $result['dnsTemplate']['Options']);
    }

    public function test_if_cache_is_used()
    {
        // Configure
        $templates = [
            'total' => 2,
            'results' => [
                0 => [
                    'name' => 'name-1'
                ],
                1 => [
                    'name' => 'name-2'
                ]
            ]
        ];

        // Mock cache
        $GLOBALS['op_registrar_module_config_dnsTemplate'] = [
            'Options' => 'None,name-1,name-2'
        ];

        // Mock
        // No mocks needed as we are testing the cache.

        // Execute
        $result = $this->ConfigController->getConfig($this->params);

        // Assert
        $this->assertIsArray($result);

        $this->assertNotContains('Tomato', $result['OpenproviderAPI']['FriendlyName']);
        $this->assertEquals('None,name-1,name-2', $result['dnsTemplate']['Options']);
    }

    public function test_return_config_with_request_data_and_a_successful_login()
    {
        // Configure
        $templates = [
            'total' => 2,
            'results' => [
                0 => [
                    'name' => 'name-1'
                ],
                1 => [
                    'name' => 'name-2'
                ]
            ]
        ];

        $_REQUEST['action'] = 'save';
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/html/configregistrars.php';
        $_REQUEST['Username'] = 'username2';
        $_REQUEST['Password'] = 'password2';

        $params = $this->params;
        $params['Username'] = $_REQUEST['Username'];
        $params['Password'] = $_REQUEST['Password'];

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($params)
            ->once();

        $this->mockedAPI->shouldReceive('searchTemplateDnsRequest')
            ->andReturn($templates)
            ->once();

        // Execute
        $result = $this->ConfigController->getConfig($this->params);

        // Assert
        $this->assertIsArray($result);

        $this->assertNotContains('Tomato', $result['OpenproviderAPI']['FriendlyName']);
        $this->assertEquals('None,name-1,name-2', $result['dnsTemplate']['Options']);
    }

    /**
     * setUp
     *
     */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedAPI = Mockery::mock(API::class);
        $this->ConfigController   = new ConfigController($this->mockedCore, $this->mockedAPI);

        unset($GLOBALS['op_registrar_module_config_dnsTemplate']);
    }
}