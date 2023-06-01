<?php

namespace WeDevelopCoffee\wPower\Tests\Core;

use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Tests\TestCase;

/**
 * Class LaunchTest
 * @package WeDevelopCoffee\wPower\Tests\Launch
 */
class CoreTest extends TestCase
{
    /**
     * @var object $launch The \WeDevelopCoffee\wPower\Core\Launch::class
     */
    protected $launch;


    public function test_launch_core()
    {
        $core = new Core();

        // Configure the instance.
        $core->setNamespace($this->testData['namespace'])
            ->setModuleType($this->testData['moduleType'])
            ->setModuleName($this->testData['moduleName'])
            ->setLevel($this->testData['level']);

        $this->launch = $core->launch();

        $this->assertIsObject($this->launch);
    }

}