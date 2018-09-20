<?php
namespace Tests\Core;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Launcher;

class LauncherTest extends TestCase
{
    protected $launcher;

    public function test_launch_wPower()
    {
        $class = \WeDevelopCoffee\wPower\wPower::class;
        $result = $this->launcher->launchClass($class);

        $this->assertInstanceOf($class, $result);
        $this->assertEquals($result, $GLOBALS['wPower']);
    }

    public function test_launch_class_with_dependencies()
    {
        $class = \WeDevelopCoffee\wPower\View\View::class;
        $result = $this->launcher->launchClass($class);

        $this->assertInstanceOf($class, $result);
    }

    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->launcher   = new Launcher();
    }
    
    
}