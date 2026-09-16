<?php

namespace WeDevelopCoffee\wPower\Module;

use Illuminate\Database\Migrations\Migrator;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;

/**
 * Class Setup
 * @package WeDevelopCoffee\wPower\Module
 */
class Setup
{
    /**
     * How long a "nothing pending" result may be trusted before we hit the
     * database again to check for new migrations. hooks.php runs on every
     * WHMCS admin and client page, so without this throttle migrate() was
     * running its DB queries on every single page load.
     */
    const CHECK_INTERVAL_SECONDS = 300;

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
        // migrate('run') is invoked from hooks.php on every single WHMCS
        // page load (admin and client). Once we've confirmed there is
        // nothing pending, skip the DB round trips for a while instead of
        // re-checking on every request.
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
     * Whether we verified "nothing pending" recently enough to trust that
     * result without hitting the database again.
     */
    private function isMigrationCheckFresh(): bool
    {
        $marker = $this->getMigrationCheckMarkerPath();

        if ($marker === null || !is_file($marker)) {
            return false;
        }

        $checkedAt = (int) file_get_contents($marker);

        return $checkedAt > 0 && (time() - $checkedAt) < self::CHECK_INTERVAL_SECONDS;
    }

    /**
     * Record that we just confirmed there is nothing pending to migrate.
     */
    private function touchMigrationCheck(): void
    {
        $marker = $this->getMigrationCheckMarkerPath();

        if ($marker !== null) {
            @file_put_contents($marker, (string) time());
        }
    }

    /**
     * Marker file lives next to the module's own migrations directory, so
     * the throttle is per module install and needs no new DB storage.
     */
    private function getMigrationCheckMarkerPath(): ?string
    {
        try {
            $moduleMigrationPath = $this->path->getModuleMigrationPath();
        } catch (\Throwable $e) {
            return null;
        }

        $dir = rtrim($moduleMigrationPath, '/');

        return is_dir($dir) && is_writable($dir) ? $dir . '/.last_checked' : null;
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