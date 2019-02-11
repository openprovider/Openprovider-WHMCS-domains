<?php
namespace Tests\Core;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;

class CoreTest extends TestCase
{
    protected $core;


    public function test_cli()
    {
        $result = $this->core->isCli();
        $this->assertTrue($result);
    }

    public function test_get_level()
    {
        $result = $this->core->getLevel();
        $this->assertEquals('hooks', $result);
    }

    public function test_set_level()
    {
        $level = 'admin';

        $this->core->setLevel($level);
        $result = $this->core->getLevel();
        $this->assertEquals($level, $result);
    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->core   = new Core();
    }
    
    
}