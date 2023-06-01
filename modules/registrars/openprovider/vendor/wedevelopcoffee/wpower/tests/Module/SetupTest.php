<?php
namespace WeDevelopCoffee\wPower\Tests\Module;
use Illuminate\Database\Migrations\Migrator;
use Mockery;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Module\Setup;
use WeDevelopCoffee\wPower\Tests\TestCase;

class SetupTest extends TestCase
{
    protected $setup;
    private $mockedMigrator;
    private $mockedPath;


    public function test_create_repository ()
    {
        // Configuration
        $path = realpath(dirname(__FILE__) . '/../dependencies/migrations');

        // Expectation


        // Mock
        $this->mockedPath->shouldReceive('getModuleMigrationPath')
            ->once()
            ->andReturn($path);

        $this->mockedMigrator->shouldReceive('repositoryExists')
            ->once()
            ->andReturn(false);

        $this->mockedMigrator->shouldReceive('getRepository')
            ->once()
            ->andReturn($this->mockedMigrator);

        $this->mockedMigrator->shouldReceive('createRepository')
            ->once();

        $this->mockedMigrator->shouldReceive('run')
            ->with($path)
            ->once();

        // Execute
        $result = $this->setup->activate();

        // Assert true. We only need to make sure that the mocks are used.
        $this->assertTrue(true);

    }

    public function test_create_with_existing_repository ()
    {
        // Configuration
        $path = realpath(dirname(__FILE__) . '/../dependencies/migrations');

        // Expectation


        // Mock
        $this->mockedPath->shouldReceive('getModuleMigrationPath')
            ->once()
            ->andReturn($path);

        $this->mockedMigrator->shouldReceive('repositoryExists')
            ->once()
            ->andReturn(true);

        $this->mockedMigrator->shouldReceive('run')
            ->with($path)
            ->once();

        // Execute
        $result = $this->setup->activate();

        // Assert true. We only need to make sure that the mocks are used.
        $this->assertTrue(true);

    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedMigrator   = Mockery::mock(Migrator::class);
        $this->mockedPath       = Mockery::mock(Path::class);
        
        $this->setup = new Setup($this->mockedMigrator, $this->mockedPath);
    }
    
    
}