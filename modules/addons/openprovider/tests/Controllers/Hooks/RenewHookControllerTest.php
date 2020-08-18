<?php

namespace OpenProvider\WhmcsDomainAddon\Tests\Controllers\Hooks;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use OpenProvider\API\API;
use OpenProvider\API\Domain as openprovider_domain;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsDomainAddon\Controllers\Hooks\RenewHookController;
use OpenProvider\WhmcsDomainAddon\Controllers\System\SynchroniseController;
use OpenProvider\WhmcsDomainAddon\Models\Domain;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainRenewal;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainTransfer;
use OpenProvider\WhmcsDomainAddon\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Database\Capsule;


/**
 * Class RenewHookControllerTest
 * @package OpenProvider\WhmcsDomainAddon\Tests\Controllers\Hooks
 */
class RenewHookControllerTest extends TestCase
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
    /**
     * @var SynchroniseController
     */
    protected $RenewHookController;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Capsule
     */
    protected $mockedCapsule;


    public function test_renewal_with_openprovider()
    {
        $test_domain_id = 2;
        $params ['params']['domainid'] = $test_domain_id;

        $result_class = new \stdClass();
        $result_class->registrar = 'openprovider';

        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('find')
            ->with($test_domain_id)
            ->andReturn($result_class);


        $result = $this->RenewHookController->processRenewal($params);

        $this->assertTrue($result);
    }

    public function test_renewal_with_other_registrar_scheduled_for_transfer()
    {
        $test_domain = 'somedomain.com';
        $test_domain_id = 2;
        $params ['params']['domainid'] = $test_domain_id;

        $result_class = $this->mockedScheduledDomainRenewal;
        $result_class->domain = $test_domain;
        $result_class->registrar = 'other registrar';

        $mock_scheduled_domain_return = new \stdClass();
        $mock_scheduled_domain_return->status = 'SCH';

        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('find')
            ->with($test_domain_id)
            ->andReturn($result_class);

        $this->mockedScheduledDomainRenewal
            ->shouldReceive('save')
            ->once();

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andReturn($mock_scheduled_domain_return);

        $expected_result = array (
            'abortWithSuccess' => true,
        );

        $result = $this->RenewHookController->processRenewal($params);

        $this->assertEquals($result, $expected_result);
    }

    public function test_renewal_with_other_registrar_scheduled_for_transfer_but_not_stored_in_local_table()
    {
        $test_domain = 'somedomain.com';
        $test_domain_id = 2;
        $params ['params']['domainid'] = $test_domain_id;

        $result_class = $this->mockedScheduledDomainRenewal;
        $result_class->domain = $test_domain;
        $result_class->registrar = 'other registrar';

        $mock_scheduled_domain_return = new \stdClass();
        $mock_scheduled_domain_return->status = 'SCH';

        $openprovider_api_response = [
            'status' => 'SCH',
        ];

        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('find')
            ->with($test_domain_id)
            ->andReturn($result_class);

        $this->mockedScheduledDomainRenewal
            ->shouldReceive('save')
            ->once();

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andThrow(ModelNotFoundException::class, 'MODEL NOT FOUND');

        $this->mockedApi->shouldReceive('retrieveDomainRequest')
            ->with(Mockery::any())
            ->andReturn($openprovider_api_response)
            ->once();

        $this->mockedOpenproviderDomain
            ->shouldReceive('convertOpStatusToWhmcs')
            ->andReturn('Pending Transfer');

        $expected_result = array (
            'abortWithSuccess' => true,
        );

        $result = $this->RenewHookController->processRenewal($params);

        $this->assertEquals($result, $expected_result);
    }

    public function test_renewal_with_other_registrar_not_scheduled_for_transfer()
    {
        $test_domain = 'somedomain.com';
        $test_domain_id = 2;
        $params ['params']['domainid'] = $test_domain_id;

        $result_class = $this->mockedScheduledDomainRenewal;
        $result_class->domain = $test_domain;
        $result_class->registrar = 'other registrar';

        $mock_scheduled_domain_return = new \stdClass();
        $mock_scheduled_domain_return->status = 'SCH';

        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('find')
            ->with($test_domain_id)
            ->andReturn($result_class);

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andThrow(ModelNotFoundException::class, 'MODEL NOT FOUND');

        $this->mockedApi->shouldReceive('retrieveDomainRequest')
            ->with(Mockery::any())
            ->andThrow(\Exception::class)
            ->once();

        $result = $this->RenewHookController->processRenewal($params);

        $this->assertFalse($result);
    }

    public function test_disable_autorenewal_setting_for_scheduled_domain_transfer()
    {
        $test_domain = 'somedomain.com';
        $test_domain_id = 2;
        $params['domainid'] = $test_domain_id;

        $_POST['domain'] = 'somedata';

        $result_class = $this->mockedScheduledDomainRenewal;
        $result_class->id = $test_domain_id;
        $result_class->domain = $test_domain;
        $result_class->donotrenew = 1;
        $result_class->registrar = 'other registrar';

        $mock_scheduled_domain_return = $this->mockedScheduledDomainTransfer;
        $mock_scheduled_domain_return->domain_id = $test_domain_id;
        $mock_scheduled_domain_return->status = 'SCH';

        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('where')
            ->with('id', $test_domain_id )
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('get')
            ->andReturn([$result_class]);

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andReturn($mock_scheduled_domain_return);

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain_id', $test_domain_id)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('where')
            ->with('status', 'SCH')
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andReturn($mock_scheduled_domain_return);

        $this->mockedApi->shouldReceive('requestDelete')
            ->with($this->mockedOpenproviderDomain)
            ->once();

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('delete')
            ->once();

        $result = $this->RenewHookController->processAutorenewalSetting($params);

        $this->assertTrue($result);
    }

    public function test_disable_autorenewal_setting_for_not_scheduled_domain_transfer()
    {
        $test_domain = 'somedomain.com';
        $test_domain_id = 2;
        $params['domainid'] = $test_domain_id;

        $_POST['domain'] = 'somedata';

        $result_class = $this->mockedScheduledDomainRenewal;
        $result_class->id = $test_domain_id;
        $result_class->domain = $test_domain;
        $result_class->donotrenew = 1;
        $result_class->registrar = 'other registrar';

        $mock_scheduled_domain_return = $this->mockedScheduledDomainTransfer;
        $mock_scheduled_domain_return->domain_id = $test_domain_id;
        $mock_scheduled_domain_return->status = 'SCH';


        $this->mockedCapsule->shouldReceive('table')
            ->with('tbldomains')
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('where')
            ->with('id', $test_domain_id )
            ->andReturn($this->mockedCapsule)
            ->shouldReceive('get')
            ->andReturn([$result_class]);

        $this->mockedScheduledDomainTransfer
            ->shouldReceive('where')
            ->with('domain', $test_domain)
            ->andReturn($this->mockedScheduledDomainTransfer)
            ->shouldReceive('firstOrFail')
            ->andThrow(ModelNotFoundException::class, 'MODEL NOT FOUND');

        $this->mockedApi->shouldReceive('retrieveDomainRequest')
            ->with(Mockery::any())
            ->andThrow(\Exception::class)
            ->once();

        $result = $this->RenewHookController->processAutorenewalSetting($params);

        $this->assertFalse($result);
    }

    /**
     * setUp
     *
     */
    public function setUp()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedScheduledDomainTransfer = Mockery::mock(ScheduledDomainTransfer::class);
        $this->mockedScheduledDomainTransfer->tbldomain = $this->mockedDomain;
        $this->mockedOpenProvider = Mockery::mock(OpenProvider::class);
        $this->mockedApi = Mockery::mock(API::class);
        $this->mockedOpenProvider->api = $this->mockedApi;
        $this->mockedScheduledDomainRenewal = Mockery::mock(ScheduledDomainRenewal::class);
        $this->mockedOpenproviderDomain = Mockery::mock(openprovider_domain::class);
        $this->mockedCapsule = Mockery::mock(Capsule::class);

        $this->RenewHookController = new RenewHookController($this->mockedCore,
            $this->mockedScheduledDomainTransfer,
            $this->mockedScheduledDomainRenewal,
            $this->mockedOpenProvider,
            $this->mockedCapsule,
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