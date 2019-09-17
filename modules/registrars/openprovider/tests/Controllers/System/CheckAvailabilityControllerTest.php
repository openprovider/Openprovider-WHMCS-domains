<?php

namespace OpenProvider\WhmcsRegistrar\Tests\Controllers\System;

use Mockery;
use OpenProvider\API\API;
use OpenProvider\WhmcsRegistrar\Controllers\System\CheckAvailabilityController;
use OpenProvider\WhmcsRegistrar\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use OpenProvider\API\Domain;


/**
 * Class TestConfigController
 * @package OpenProvider\WhmcsRegistrar\Tests\Controllers\System
 */
class CheckAvailabilityControllerTest extends TestCase
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
     * @var
     */
    private $CheckAvailabilityController;
    /**
     * @var Mockery\MockInterface|Domain
     */
    private $mockedDomain;
    /**
     * @var Mockery\MockInterface|ResultsList
     */
    private $mockedResultsList;

    public function test_unavailable_domain()
    {
        // Prepare
        list($params, $domain, $sld, $tld) = $this->prepareParamsAndDomain();

        $returnResults = [];
        $returnResults[] = [
            'domain' => $domain,
            'status' => 'free'
        ];

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($params)
            ->once();

        $this->mockedAPI->shouldReceive('checkDomainArray')
            ->withAnyArgs()
            ->once()
            ->andReturn($returnResults);

        // Execute
        $result = $this->CheckAvailabilityController->check($params);

        // Assert
        $this->assertIsObject($result);
        $this->assertInstanceOf( 'WHMCS\Domains\DomainLookup\ResultsList', $result);
    }

    public function test_premium_domain_with_support_disabled_in_openprovider()
    {
        // Prepare
        list($params, $domain, $sld, $tld) = $this->prepareParamsAndDomain();

        $returnResults = [];
        $returnResults[] = [
            'domain' => $domain,
            'premium' => true,
            'status' => 'free'
        ];

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($params)
            ->once();

        $this->mockedAPI->shouldReceive('checkDomainArray')
            ->withAnyArgs()
            ->once()
            ->andReturn($returnResults);

        // Execute
        $result = $this->CheckAvailabilityController->check($params);

        // Assert
        $this->assertIsObject($result);
        $this->assertInstanceOf( 'WHMCS\Domains\DomainLookup\ResultsList', $result);
        $this->assertEquals($result->result[0]->status, 3);
    }

    public function test_premium_domain_with_support_enabled_in_openprovider()
    {
        // Prepare
        list($params, $domain, $sld, $tld) = $this->prepareParamsAndDomain();
        $params['premiumEnabled'] = true;
        $params['OpenproviderPremium'] = true;

        $returnResults = [];
        $returnResults[] = [
            'domain' => $domain,
            'premium' => true,
            'status' => 'free'
        ];

        $retrievePriceArgumentsCreate = [
            'domain' => [
                'name' => $sld,
                'extension' => $tld
            ],
            'operation' => 'create'
        ];

        $returnRetrievePriceArgumentsCreate ['price'] ['reseller'] ['price'] = '123';
        $returnRetrievePriceArgumentsCreate ['price'] ['reseller'] ['currency'] = 'EUR';

        $retrievePriceArgumentsTransfer = $retrievePriceArgumentsCreate;
        $retrievePriceArgumentsTransfer ['operation'] = 'transfer';

        // Expectations
        $expectedPricing['register'] = $returnRetrievePriceArgumentsCreate ['price']['reseller']['price'];
        $expectedPricing['renew'] = $returnRetrievePriceArgumentsCreate ['price']['reseller']['price'];
        $expectedPricing['CurrencyCode'] = $returnRetrievePriceArgumentsCreate ['price']['reseller']['currency'];

        // Mock
        $this->mockedAPI->shouldReceive('setParams')
            ->with($params)
            ->once();

        $this->mockedAPI->shouldReceive('sendRequest')
            ->with('retrievePriceDomainRequest', $retrievePriceArgumentsCreate)
            ->andReturn($returnRetrievePriceArgumentsCreate)
            ->once();

        $this->mockedAPI->shouldReceive('sendRequest')
            ->with('retrievePriceDomainRequest', $retrievePriceArgumentsTransfer)
            ->andReturn($returnRetrievePriceArgumentsCreate)
            ->once();

        $this->mockedAPI->shouldReceive('checkDomainArray')
            ->withAnyArgs()
            ->once()
            ->andReturn($returnResults);

        // Execute
        $result = $this->CheckAvailabilityController->check($params);

        // Assert
        $this->assertIsObject($result);
        $this->assertInstanceOf( 'WHMCS\Domains\DomainLookup\ResultsList', $result);
        $this->assertEquals($result->result[0]->status, 1);
        $this->assertEquals($result->result[0]->premiumDomain, true);
        $this->assertEquals($result->result[0]->premiumCostPricing, $expectedPricing);
    }

    /**
     * setUp
     *
     */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedAPI = Mockery::mock(API::class);
        $this->mockedResultsList = Mockery::mock(ResultsList::class);
        $this->mockedDomain = Mockery::mock(Domain::class);
        $this->CheckAvailabilityController   = new CheckAvailabilityController($this->mockedCore,
            $this->mockedAPI,
            $this->mockedDomain,
            $this->mockedResultsList);
    }

    /**
     * @return array
     */
    protected function prepareParamsAndDomain()
    {
        $params = $this->params;
        $params['tldsToInclude'][] = '.com';
        $params['isIdnDomain'] = false;
        $params['punyCodeSearchTerm'] = false;
        $params['premiumEnabled'] = false;
        $params['searchTerm'] = 'some-domain';

        $sld = $params['searchTerm'];
        $tld = 'com';
        $domain = $params['searchTerm'] . '.' . $tld;
        return array ($params, $domain, $sld, $tld);
    }
}