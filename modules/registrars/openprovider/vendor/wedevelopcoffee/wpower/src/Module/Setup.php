<?php

namespace WeDevelopCoffee\wPower\Module;

use Illuminate\Database\Migrations\Migrator;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;
use WHMCS\Database\Capsule;

/**
 * Class Setup
 * @package WeDevelopCoffee\wPower\Module
 */
class Setup
{
    const CHECK_INTERVAL_SECONDS = 300; // Cache the migration check to avoid running database queries on every page load.

    /**
     * @var Migrator
     */
    private $migrator;

    /**
     * @var Path
     */
    private $path;

    /**
     * @var migration paths
     */
    private $migrationPaths;

    public function __construct(Migrator $migrator, Path $path)
    {

        $this->migrator = $migrator;
        $this->path = $path;
    }

    public function activate()
    {
        $this->findModuleMigrationPath();
        $this->migrate();
    }

    public function deactivate()
    {
        $this->findModuleMigrationPath();
        $this->migrate('reset');
    }

    public function upgrade()
    {
        $this->findModuleMigrationPath();
        $this->migrate('upgrade');
    }

    /**
     * Enable the features for database migrations
     *
     * @return void
     */
    public function enableFeature ($feature)
    {
        if($feature == 'handles')
            $this->addFeatureMigrationPath('Handles');

        return $this;
    }

    /**
     * Generate the addon path
     *
     */
    protected function addFeatureMigrationPath ($feature)
    {
        $path = realpath(dirname(__FILE__) . '/../' . $feature . '/Migrations/');
        $this->addMigrationPath($path);
    }

    /**
     * Add path to the list of migration paths
     *
     * @return void
     */
    public function addMigrationPath ($path)
    {
        $this->migrationPaths[] = $path;
        return $this;
    }

    /**
     * Migrate!
     *
     * @return array
     */
    public function migrate ($action = 'run')
    {
        // Skip the database migration check if it was recently completed with nothing pending.
        if ($action === 'run' && $this->isMigrationCheckFresh()) {
            return true;
        }

        // Check if the repository exists.
        if(!$this->migrator->repositoryExists())
        {
            // Let's create the repository.
            $repository = $this->migrator->getRepository();
            $repository->createRepository();
        }

        if(!empty($this->migrationPaths))
        {
            foreach($this->migrationPaths as $path)
            {
                if($action == 'run') {
                    $files = $this->migrator->getMigrationFiles($path);

                    $ran = $this->migrator->getRepository()->getRan();

                    if ($ran instanceof \Illuminate\Support\Collection) {
                        $ran = $ran->toArray();
                    }

                    $pending = \Illuminate\Support\Collection::make($files)
                        ->reject(function ($file) use ($ran) {
                            return in_array($this->migrator->getMigrationName($file), $ran, true);
                        })
                        ->values()
                        ->all();

                    $this->migrator->requireFiles($pending);
                    $this->migrator->runPending($pending, []);
                }  
                elseif($action == 'reset')
                {
                    $files = $this->migrator->getMigrationFiles($path);
                    $this->migrator->requireFiles($path, $files);
                    $this->migrator->reset();
                }
            }
        }

        if ($action === 'run') {
            $this->touchMigrationCheck();
        }

        return true;
    }

    /**
     * Check if the previous migration check is still cached in WHMCS's shared transient data table.
     */
    private function isMigrationCheckFresh(): bool
    {
        $cacheKey = $this->getMigrationCheckCacheKey();

        if ($cacheKey === null) {
            return false;
        }

        try {
            $row = Capsule::table('tbltransientdata')->where('name', $cacheKey)->first();
        } catch (\Throwable $e) {
            return false;
        }

        return $row !== null && (int) $row->expires > time();
    }

    /**
     * Record that there are no pending migrations.
     */
    private function touchMigrationCheck(): void
    {
        $cacheKey = $this->getMigrationCheckCacheKey();

        if ($cacheKey === null) {
            return;
        }

        try {
            Capsule::table('tbltransientdata')->updateOrInsert(
                ['name' => $cacheKey],
                ['data' => '1', 'expires' => time() + self::CHECK_INTERVAL_SECONDS]
            );
        } catch (\Throwable $e) {
            // Best effort - if this fails, migrate() just runs its full check again next time.
        }
    }

    /**
     * Generate a stable, module-specific cache key based on its migration path.
     */
    private function getMigrationCheckCacheKey(): ?string
    {
        try {
            $moduleMigrationPath = $this->path->getModuleMigrationPath();
        } catch (\Throwable $e) {
            return null;
        }

        return 'openprovider.migrationCheck.' . md5($moduleMigrationPath);
    }

    /**
     * Find the migration path for the module.
     */
    protected function findModuleMigrationPath()
    {
        $migrationPath = $this->path->getModuleMigrationPath();

        if (is_dir($migrationPath)) {
            $this->migrationPaths [] = $migrationPath;
        }
    }
}