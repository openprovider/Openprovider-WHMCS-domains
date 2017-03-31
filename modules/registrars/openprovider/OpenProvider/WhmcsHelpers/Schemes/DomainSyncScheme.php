<?php
namespace OpenProvider\WhmcsHelpers\Schemes;
use WHMCS\Database\Capsule;

/**
 * Creates the DomainSync scheme (if it does not exist)
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class DomainSyncScheme
{
	/**
	 * Create the DomainSync table for $registrar
	 *
	 * @return void
	 **/
	public static function up($registrar)
	{
		$table_name = self::get_table_name($registrar);

		if(Capsule::schema()->hasTable($table_name))
			return;

		try {
		    Capsule::schema()->create(
		        $table_name,
		        function ($table) {
		            /** @var \Illuminate\Database\Schema\Blueprint $table */
		            $table->increments('id');
		            $table->datetime('last_sync');
		        }
		    );
		} catch (\Exception $e) {
		    logModuleCall($registrar, 'create_registrar_table', null, $e->getMessage(), null);
		}
	}

	/**
	 * Removes the DomainSync table for $registrar
	 *
	 * @return void
	 **/
	public static function down($registrar)
	{
		$table_name = self::get_table_name($registrar);

		if(!Capsule::schema()->hasTable($table_name))
			return;

		try {
		    Capsule::schema()->drop(
		        $table_name
		    );
		} catch (\Exception $e) {
		    logModuleCall($registrar, 'drop_registrar_table', null, $e->getMessage(), null);
		}
	}

	/**
	 * Generate the table name
	 *
	 * @return string $registrar
	 **/
	public static function get_table_name($registrar)
	{
		$registrar  = ucfirst(preg_replace("/[^a-zA-Z0-9]+/", "", $registrar));
		$table_name = 'tblDomainSync' . $registrar;

		return $table_name;
	}
} // END class DomainSyncScheme