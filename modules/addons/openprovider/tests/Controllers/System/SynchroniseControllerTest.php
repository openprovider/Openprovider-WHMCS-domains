<?php

namespace OpenProvider\WhmcsDomainAddon\Tests\Controllers\System;

use Mockery;
use OpenProvider\API\API;
use OpenProvider\API\Domain as openprovider_domain;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsDomainAddon\Controllers\System\SynchroniseController;
use OpenProvider\WhmcsDomainAddon\Models\Domain;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainRenewal;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainTransfer;
use OpenProvider\WhmcsDomainAddon\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use Illuminate\Database\Eloquent\ModelNotFoundException;


/**
 * Class SynchroniseControllerTest
 * @package OpenProvider\WhmcsDomainAddon\Tests\Controllers\System
 */
class SynchroniseControllerTest extends TestCase
{
    /**
     * @var Mockery\MockInterface|Core
     */
    protected $mockedCore;
    /**
     * @var SynchroniseController
     */
    protected $SynchroniseController;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Domain
     */
    protected $mockedDomain;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ScheduledDomainTransfer
     */
    protected $mockedScheduledDomainTransfer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|OpenProvider
     */
    protected $mockedOpenProvider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ScheduledDomainRenewal
     */
    protected $mockedScheduledDomainRenewal;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|API
     */
    protected $mockedApi;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|openprovider_domain
     */
    protected $mockedOpenproviderDomain;

    public function test_empty_response_openprovider()
    {
        /**
         * TEST updateScheduledDomainTransferTable
         */

        $this->mockedApi->shouldreceive('searchDomain')
            ->with([
                'offset' => 0,
                'limit' => 1000,
                'status' => 'SCH'
            ])
            ->andReturn([
                'results' => null,
                'total' => '0'
            ]);

        // Perform the test
        $this->SynchroniseController->updateScheduledDomainTransferTable();
    }

    /**
     * Test new domains
     */
    public function test_new_pending_domains()
    {
        /**
         * TEST updateScheduledDomainTransferTable
         */
        $test_domain = 'testdomain.com';
        $result_items = [
            0 => [
                'domain' => explode('.', $test_domain),
                'status' => 'SCH'
            ]
        ];

        $this->mockedApi->shouldreceive('searchDomain')
            ->with([
                'offset' => 0,
                'limit' => 1000,
                'status' => 'SCH'
            ])
            ->andReturn([
                'results' => $result_items,
                'total' => '1'
            ]);

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('firstOrNew')
            ->with(['domain' => $test_domain])
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('save');

        $this->SynchroniseController->updateScheduledDomainTransferTable();
    }

    public function test_empty_linkDomainsToWhmcsDomains(): void
    {
        $this->mockedScheduledDomainTransfer->shouldReceive('where')
            ->with('domain_id', null)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn([])
            ->once();// Should be class Illuminate\Database\Eloquent\Collection

        // Perform the test
        $this->SynchroniseController->linkDomainsToWhmcsDomains();
    }

    public function test_one_domain_not_in_whmcs_linkDomainsToWhmcsDomains(): void
    {
        $test_domain_id = 2;
        $test_domain = 'testdomain.com';
        $this->mockedScheduledDomainTransfer->domain_id = $test_domain_id;
        $this->mockedScheduledDomainTransfer->domain = $test_domain;
        $this->mockedScheduledDomainTransfer->status = 'SCH';

        $result_items = [
            0 => $this->mockedScheduledDomainTransfer
        ];

        $this->mockedScheduledDomainTransfer->shouldReceive('where')
            ->with('domain_id', null)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn($result_items)
            ->once();// Should be class Illuminate\Database\Eloquent\Collection

        $this->mockedDomain
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedDomain)
            ->shouldReceive('firstOrFail')
            ->andThrow(ModelNotFoundException::class, 'Not found');

        // Perform the test
        $this->SynchroniseController->linkDomainsToWhmcsDomains();
    }

    public function test_one_domain_in_whmcs_linkDomainsToWhmcsDomains(): void
    {
        $test_domain_id = 2;
        $test_domain = 'testdomain.com';
        $this->mockedScheduledDomainTransfer->domain_id = $test_domain_id;
        $this->mockedScheduledDomainTransfer->domain = $test_domain;
        $this->mockedScheduledDomainTransfer->status = 'SCH';

        $result_items = [
            0 => $this->mockedScheduledDomainTransfer
        ];

        $this->mockedScheduledDomainTransfer->shouldReceive('where')
            ->with('domain_id', null)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn($result_items)
            ->once();// Should be class Illuminate\Database\Eloquent\Collection

        $this->mockedDomain
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedDomain)
            ->shouldReceive('firstOrFail')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->once();

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('save')
            ->once();

        // Perform the test
        $this->SynchroniseController->linkDomainsToWhmcsDomains();
    }

    /**
     * Test no finished scheduled domain transfer
     */
    public function test_no_finished_scheduled_domain_transfer()
    {
        $this->mockedScheduledDomainTransfer->shouldReceive('whereRaw')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('where')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn([])
            ->once();// Should be class Illuminate\Database\Eloquent\Collection

        // Perform the test
        $this->SynchroniseController->processNotUpdatedDomains();
    }

    /**
     * Test one finished scheduled domain transfer
     */
    public function test_one_finished_scheduled_domain_transfer()
    {
        /**
         * TEST processNotUpdatedDomains
         */

        $test_domain_id = 2;
        $test_domain = 'testdomain.com';
        $this->mockedScheduledDomainTransfer->domain_id = $test_domain_id;
        $this->mockedScheduledDomainTransfer->domain = $test_domain;
        $this->mockedScheduledDomainTransfer->status = 'SCH';

        $result_items = [
            0 => $this->mockedScheduledDomainTransfer
        ];

        $openprovider_return_array = [
            'status' => 'ACT',
            'expirationDate' => '2099-09-09'
        ];

        $this->mockedScheduledDomainTransfer->shouldReceive('whereRaw')
            ->with('DATE(cron_run_date) <> DATE(NOW())')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('where')
            ->with('domain_id', '!=', null)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn($result_items)
            ->once();// Should be class Illuminate\Database\Eloquent\Collection

        // Prepare API
        $this->mockedApi->shouldReceive('retrieveDomainRequest')
            ->andReturn($openprovider_return_array);

        // Prepare check domain status
        $this->mockedOpenproviderDomain
            ->shouldReceive('convertOpStatusToWhmcs')
            ->with('ACT')
            ->andReturn('Active');

        // Prepare save
        $this->mockedScheduledDomainTransfer
            ->shouldReceive('save')
            ->once();

        // Prepare tbldomain
        $this->mockedDomain->shouldReceive('save')
            ->once();

        // Renewal
        $this->mockedScheduledDomainRenewal
            ->shouldReceive('where')
            ->with('domain_id', $test_domain_id)
            ->andReturn($this->mockedScheduledDomainRenewal)
            ->shouldReceive('firstOrFail')
            ->andThrow(ModelNotFoundException::class, 'Not found');

        // Perform the test
        $this->SynchroniseController->processNotUpdatedDomains();
    }

    public function test_processDomainsWithDisabledAutorenewal(): void
    {
        $this->mockedScheduledDomainTransfer->shouldReceive('where')
            ->with('status', 'SCH')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('whereHas')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn([]);;// Should be class Illuminate\Database\Eloquent\Collection

        $this->SynchroniseController->processDomainsWithDisabledAutorenewal();
    }

    public function test_one_domain_processDomainsWithDisabledAutorenewal(): void
    {
        $test_domain_id = 2;
        $test_domain = 'testdomain.com';
        $this->mockedScheduledDomainTransfer->domain_id = $test_domain_id;
        $this->mockedScheduledDomainTransfer->domain = $test_domain;
        $this->mockedScheduledDomainTransfer->status = 'SCH';

        $result_items = [
            0 => $this->mockedScheduledDomainTransfer
        ];

        $this->mockedScheduledDomainTransfer->shouldReceive('where')
            ->with('status', 'SCH')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('whereHas')
            ->with('tbldomain',\Mockery::on(function ($argument) {
                if(is_callable($argument))
                {
                    $mockedClass = Mockery::mock(Illuminate\Database\Eloquent\Builder::class);
                    $mockedClass->shouldReceive('where')
                        ->with('donotrenew', 1)
                        ->once();
                    $argument($mockedClass);
                    return true;
                }
            }))
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('get')
            ->andReturn($result_items);;// Should be class Illuminate\Database\Eloquent\Collection

        $this->SynchroniseController->processDomainsWithDisabledAutorenewal();
    }

    /**
     * setUp
     *
     */
    public function setUp()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedDomain = Mockery::mock(Domain::class);
        $this->mockedScheduledDomainTransfer = Mockery::mock(ScheduledDomainTransfer::class);
        $this->mockedScheduledDomainTransfer->tbldomain = $this->mockedDomain;
        $this->mockedOpenProvider = Mockery::mock(OpenProvider::class);
        $this->mockedApi = Mockery::mock(API::class);
        $this->mockedOpenProvider->api = $this->mockedApi;
        $this->mockedScheduledDomainRenewal = Mockery::mock(ScheduledDomainRenewal::class);
        $this->mockedOpenproviderDomain = Mockery::mock(openprovider_domain::class);

        $this->SynchroniseController = new SynchroniseController($this->mockedCore,
            $this->mockedDomain,
            $this->mockedScheduledDomainTransfer,
            $this->mockedScheduledDomainRenewal,
            $this->mockedOpenProvider,
            $this->mockedOpenproviderDomain
        );
    }

    public function tearDown()
    {
        // Add Mockery results.
        $this->addToAssertionCount(
            \Mockery::getContainer()->mockery_getExpectationCount()
        );

        Mockery::close();
    }
}