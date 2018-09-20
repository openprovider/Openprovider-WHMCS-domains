<?php
namespace Tests\Module;
use Mockery;
use Tests\TestCase;
use phpmock\mockery\PHPMockery;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Module\Module;

class ModuleTest extends TestCase
{
    protected $module;
    protected $mockedCore;

    /**
     * Need to run this in a separate process, otherwise the debug_backtrace override will fail.
     * @runInSeparateProcess
     */
    public function test_check_addon()
    {
        $this->do_checkmodule_test('addons', 'some-addon');   
    }

    /**
     * Need to run this in a separate process, otherwise the debug_backtrace override will fail.
     * @runInSeparateProcess
     */
    public function test_check_gateway()
    {
        $this->do_checkmodule_test('gateways', 'some-gateway');   
    }

    /**
     * Need to run this in a separate process, otherwise the debug_backtrace override will fail.
     * @runInSeparateProcess
     */
    public function test_check_registrar()
    {
        $this->do_checkmodule_test('registrars', 'some-registrar');   
    }

    /**
    * do_checkmodule_test
    * 
    * @return 
    */
    public function do_checkmodule_test ($type, $expectedModuleName)
    {   
        $exampleDebugBacktrace[]    = ['file' => 'ignore'];
        $exampleDebugBacktrace[]    = ['file' => '/home/user/domains/domain.com/private_html/modules/'.$type.'/'.$expectedModuleName.'/'.$expectedModuleName.'.php'];

        $mock = PHPMockery::mock('\WeDevelopCoffee\wPower\Module', "debug_backtrace")
            ->times(4)
            ->andReturn($exampleDebugBacktrace);

        $this->module           = new Module($this->mockedCore);

        $this->assertEquals($expectedModuleName, $this->module->getName());

        $expectedType = substr($type, 0, '-1');

        $this->assertEquals($expectedType, $this->module->getType());
    }

    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedCore   = Mockery::mock(Core::class);
    }
    
    
}