<?php

namespace WeDevelopCoffee\wPower\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository as BaseDatabaseMigrationRepository;
use WHMCS\Database\Capsule;
/**
 * Class DatabaseMigrationRepository
 * @package WeDevelopCoffee\wPower\Core
 */
class DatabaseMigrationRepository extends BaseDatabaseMigrationRepository
{
    public $type;
    public $moduleName;

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        $version = Capsule::table('tblconfiguration')->where('setting', 'Version')->first();
        $major_version = substr($version->value,0,1);

        $query = $this->table()
            ->where('module', $this->moduleName)
            ->where('type', $this->type)
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration');

        if($major_version == 8)
            return $query->all();

        // WHMCS V7 support
        return $query;
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('module', $this->moduleName)
            ->where('type', $this->type)
            ->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('migration', 'desc')->get();
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['module' => $this->moduleName, 'type' => $this->type, 'migration' => $file, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param  object  $migration
     * @return void
     */
    public function delete($migration)
    {
        $this->table()->where('module', $this->moduleName)
            ->where('type', $this->type)->where('migration', $migration->migration)->delete();
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->where('module', $this->moduleName)
            ->where('type', $this->type)
            ->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->string('module');
            $table->string('type');
            $table->string('migration');

            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

}