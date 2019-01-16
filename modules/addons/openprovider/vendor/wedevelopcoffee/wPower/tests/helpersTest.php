<?php
namespace Tests;
use WeDevelopCoffee\wPower\wPower;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\Module\Module;
use WeDevelopCoffee\wPower\View\View;

class helpersTest extends TestCase
{
    public function test_launch_class()
    {
        $class = Core::class;

        $core = wLaunch($class);

        $this->assertInstanceOf($class, $core);
    }

    public function test_wPower()
    {
        $class = wPower::class;

        $wPower = wPower();

        $this->assertInstanceOf($class, $wPower);
    }

    public function test_wView()
    {
        $view = 'helper';

        /**
         * We expect that the Smarty Class is assigned with the correct view.
         * Since no such template exists, we only want to assert the correct path
         * to the view in the exception message.
         */
        $this->expectException(\SmartyException::class);
        $this->expectExceptionMessageRegExp('/resources\/views\/'.$view.'\.tpl/');

        $wPower = wView('helper',[]);
    }

}