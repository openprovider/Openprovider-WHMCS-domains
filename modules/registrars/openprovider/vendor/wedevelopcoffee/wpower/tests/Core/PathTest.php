<?php

namespace WeDevelopCoffee\wPower\Tests\Core;

use Mockery;
use WeDevelopCoffee\wPower\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class PathTest
 * @package WeDevelopCoffee\wPower\Tests\Core
 */
class PathTest extends TestCase
{
    protected $path;
    protected $mockedCore;
    protected $mockedModule;

    public function test_get_doc_root()
    {
        $result = $this->path->getDocRoot();

        $this->assertContains(getcwd(), $result);
    }

    public function test_get_addons_path()
    {
        $result = $this->path->getAddonsPath();

        // Overriding __DIR__ is tricky, instead we have to rely that this works.

        $this->assertContains('modules/addons/', $result);
    }

    public function test_get_module_path()
    {
        $moduleName = 'some-module';

        $this->mockedCore->shouldReceive('getModuleName')
            ->once()
            ->andReturn($moduleName);

        $this->mockedCore->shouldReceive('getModuleType')
            ->once()
            ->andReturn('addon');

        $result = $this->path->getModulePath();

        // Overriding __DIR__ is tricky, instead we have to rely that this works.

        $this->assertContains('modules/addons/'.$moduleName.'/' , $result);
    }

    public function test_get_addon_migration_path()
    {
        $moduleName = 'some-module';

        $this->mockedCore->shouldReceive('getModuleName')
            ->once()
            ->andReturn($moduleName);

        $this->mockedCore->shouldReceive('getModuleType')
            ->once()
            ->andReturn('addon');

        $result = $this->path->getModuleMigrationPath();

        $this->assertContains('modules/addons/'.$moduleName.'/migrations/' , $result);
    }

    /**
     * setUp
     *
     */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->path   = new Path($this->mockedCore);

        $this->mockedCore->shouldReceive('isCli')
            ->once()
            ->andReturn(true);
    }


}