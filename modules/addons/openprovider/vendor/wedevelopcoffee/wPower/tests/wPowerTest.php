<?php
namespace Tests;
use PHPUnit\Framework\TestCase;
use WeDevelopCoffee\wPower\wPower;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Module\Module;
use WeDevelopCoffee\wPower\View\View;

class wPowerTest extends TestCase
{
    /**
     * The wPower instance
     *
     * @var instance
     */
    protected $wPower;

    public function test_create_core_instance()
    {
        $core = $this->wPower->Core();

        $this->assertInstanceOf(Core::class, $core);
    }

    public function test_create_module_instance()
    {
        $module = $this->wPower->Module();

        $this->assertInstanceOf(Module::class, $module);
    }

    public function test_create_path_instance()
    {
        $path = $this->wPower->Path();

        $this->assertInstanceOf(Path::class, $path);
    }

    public function test_create_router_instance()
    {
        $router = $this->wPower->Router();

        $this->assertInstanceOf(Router::class, $router);
    }

    public function test_create_view_instance()
    {
        $view = $this->wPower->View();

        $this->assertInstanceOf(View::class, $view);
    }

    public function test_launch_class()
    {
        $class = Core::class;

        $core = $this->wPower->launch($class);

        $this->assertInstanceOf($class, $core);
    }

    /**
    * setUp
    * 
    * @return void
    */
    public function setUp ()
    {
        $this->wPower = new wPower();
    }
}