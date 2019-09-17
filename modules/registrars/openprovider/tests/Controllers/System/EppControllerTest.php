<?php

namespace OpenProvider\WhmcsRegistrar\Tests\Controllers\System;

use Mockery;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\WhmcsRegistrar\Controllers\System\ContactController;
use OpenProvider\WhmcsRegistrar\Controllers\System\EppController;
use OpenProvider\WhmcsRegistrar\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Handles\Models\Handle;
use WHMCS\Domains\DomainLookup\DomainObj;

/**
 * Class TestConfigController
 * @package OpenProvider\WhmcsRegistrar\Tests\Controllers\System
 */
class EppControllerTest extends TestCase
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
    private $EppController;
    /**
     * @var Mockery\MockInterface|Domain
     */
    public $mockedDomain;

    public function test_get_epp_code()
    {
        $eppCode = 'some-epp-code';
        $params = $this->prep_fetch_epp_code();

        // Mock
        $this->mockedAPI->shouldReceive('getEPPCode')
            ->with($this->mockedDomain)
            ->once()
            ->andReturn($eppCode);

        // Expectations
        $expectedResult["eppcode"] = $eppCode;

        // Execute
        $result = $this->EppController->get($params);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_failed_get_epp_code()
    {
        // Config
        $eppCode = '';
        $params = $this->prep_fetch_epp_code();

        // Mock
        $this->mockedAPI->shouldReceive('getEPPCode')
            ->with($this->mockedDomain)
            ->once()
            ->andReturn($eppCode);

        // Expectations
        $expectedResult["error"] = 'EPP code is not set';

        // Execute
        $result = $this->EppController->get($params);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_failed_request_to_registrar_to_get_epp_code()
    {
        // Config
        $eppCode = '';
        $params = $this->prep_fetch_epp_code();
        $some_error_message = 'some-error-message';

        // Mock
        $this->mockedAPI->shouldReceive('getEPPCode')
            ->with($this->mockedDomain)
            ->once()
            ->andThrow(\Exception::class, $some_error_message);

        // Expectations
        $expectedResult["error"] = $some_error_message;

        // Execute
        $result = $this->EppController->get($params);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }


    /**
     * setUp
     *
     */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedAPI = Mockery::mock(API::class);
        $this->mockedDomain = Mockery::mock(Domain::class);
        $this->EppController   = new EppController($this->mockedCore, $this->mockedAPI, $this->mockedDomain);
    }

    /**
     * @return array
     */
    protected function prep_fetch_epp_code()
    {
        // Config
        $params = $this->params;

        $domainObj = new DomainObj();
        $domainObj->setSecondLevel($params['sld']);
        $domainObj->setTopLevel($params['tld']);

        $params['original']['domainObj'] = $domainObj;


        // Mock
        $this->mockedDomain->shouldReceive('load')
            ->with([
                'name' => $params['sld'],
                'extension' => $params['tld']
            ])
            ->once()
            ->andReturn($this->mockedDomain);

        $this->mockedAPI->shouldReceive('setParams')
            ->with($params)
            ->once();

        return $params;
    }
}