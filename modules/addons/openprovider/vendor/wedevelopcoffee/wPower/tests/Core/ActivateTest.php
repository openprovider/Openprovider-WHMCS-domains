<?php
namespace Tests\Core;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Mockery;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Activate;
use WeDevelopCoffee\wPower\Core\Core;

class ActivateTest extends TestCase
{
    // Mocks
    protected $migrator;

    // Class
    protected $activate;


    public function test_migrate_without_repository()
    {
        $this->migrator->shouldReceive('repositoryExists')
            ->andReturn(false)
            ->once();

        $repository = Mockery::mock(MigrationRepositoryInterface::class);
        $repository->shouldReceive('createRepository')
            ->once();

        $this->migrator->shouldReceive('getRepository')
            ->andReturn($repository)
            ->once();

        $this->migrator->shouldReceive('run')
            ->with(realpath(__DIR__ . '/../../src/Handles/migrations/'))
            ->once();

        $this->activate->enableFeature('handles');
        $result = $this->activate->Migrate();

        $this->assertTrue($result);
    }

    public function test_migrate_with_repository()
    {
        $this->migrator->shouldReceive('repositoryExists')
            ->andReturn(true)
            ->once();

        $this->migrator->shouldReceive('run')
            ->with(realpath(__DIR__ . '/../../src/Handles/migrations/'))
            ->once();

        $this->activate->enableFeature('handles');
        $result = $this->activate->Migrate();

        $this->assertTrue($result);
    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->migrator = Mockery::mock(Migrator::class);
        $this->activate   = new Activate($this->migrator);
    }
    
    
}