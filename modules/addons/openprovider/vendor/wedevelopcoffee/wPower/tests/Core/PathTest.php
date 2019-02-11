<?php
namespace Tests\Core;
use Mockery;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Module\Module;

class PathTest extends TestCase
{
    protected $path;
    protected $mockedCore;
    protected $mockedModule;

    public function test_get_doc_root()
    {
        $result = $this->path->getDocRoot();

        $this->assertContains('src/Core', $result);
    }

    public function test_get_addons_path()
    {
        $result = $this->path->getAddonsPath();

        // Overriding __DIR__ is tricky, instead we have to rely that this works.

        $this->assertContains('src/Core/modules/addons/', $result);
    }

    public function test_get_addon_path()
    {
        $moduleName = 'some-module';

        $this->mockedModule->shouldReceive('getName')
            ->once()
            ->andReturn($moduleName);
        
        $result = $this->path->getAddonPath();

        // Overriding __DIR__ is tricky, instead we have to rely that this works.

        $this->assertContains('src/Core/modules/addons/'.$moduleName.'/' , $result);
    }
    
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedModule = Mockery::mock(Module::class);
        $this->mockedCore = Mockery::mock(Core::class);
        $this->path   = new Path($this->mockedCore, $this->mockedModule);

        $this->mockedCore->shouldReceive('isCli')
            ->once()
            ->andReturn(true);
    }
    
    
}