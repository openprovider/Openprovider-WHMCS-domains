<?php

namespace OpenProvider\WhmcsRegistrar\Tests\Controllers\System;

use Mockery;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\WhmcsRegistrar\Controllers\System\ContactController;
use OpenProvider\WhmcsRegistrar\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\WhmcsRegistrar\src\Handle;
use WHMCS\Domains\DomainLookup\DomainObj;

/**
 * Class TestConfigController
 * @package OpenProvider\WhmcsRegistrar\Tests\Controllers\System
 */
class ContactControllerTest extends TestCase
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
    private $ContactController;
    /**
     * @var Mockery\MockInterface|Handle
     */
    public $mockedHandle;
    /**
     * @var Mockery\MockInterface|Domain
     */
    public $mockedDomain;

    public function test_get_details()
    {
        // Configure
        $params = $this->params;

        $domainObj = new DomainObj();
        $domainObj->setSecondLevel($params['sld']);
        $domainObj->setTopLevel($params['tld']);

        $params['original']['domainObj'] = $domainObj;

        $returnValue = ['some-data'];

        // Expectations
        $expectedResult = $returnValue;

        // Mocks
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

        $this->mockedAPI->shouldReceive('getContactDetails')
            ->with($this->mockedDomain)
            ->andReturn($returnValue);

        // Execute
        $result = $this->ContactController->getDetails($params);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_get_exception_when_getting_contact_details()
    {
        // Configure
        $params = $this->params;

        $domainObj = new DomainObj();
        $domainObj->setSecondLevel($params['sld']);
        $domainObj->setTopLevel($params['tld']);

        $params['original']['domainObj'] = $domainObj;

        $errorMessage = 'some-error';

        // Expectations
        $expectedResult = ['error' => $errorMessage];

        // Mocks
        $this->mockedDomain->shouldReceive('load')
            ->with([
                'name' => $params['sld'],
                'extension' => $params['tld']
            ])
            ->once()
            ->andThrow(\Exception::class, $errorMessage);

        // Execute
        $result = $this->ContactController->getDetails($params);

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
        $this->mockedHandle = Mockery::mock(Handle::class);
        $this->ContactController   = new ContactController($this->mockedCore, $this->mockedAPI, $this->mockedDomain, $this->mockedHandle);
    }
}