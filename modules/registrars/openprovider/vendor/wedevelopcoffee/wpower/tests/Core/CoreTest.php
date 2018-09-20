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
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->core   = new Core();
    }
    
    
}