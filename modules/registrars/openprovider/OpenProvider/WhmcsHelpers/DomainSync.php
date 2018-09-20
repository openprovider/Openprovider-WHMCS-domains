<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule,
	OpenProvider\WhmcsHelpers\Schemes\DomainSyncScheme,
	Carbon\Carbon;

/**
 * Helper to synchronize the domain info.
 * WhmcsHelper
 *
 * @copyright Copyright (c) WeDevelop.coffee 2018
 */
class DomainSync
{

	/**
	 * Synchronize the DomoainSync table.
	 *
	 * @return void
	 **/
	public static function sync_DomainSync_table($registrar)
	{
		$table_name = DomainSyncScheme::get_table_name($registrar);

		$domains = Capsule::table('tbldomains')
				->leftJoin($table_name, 'tbldomains.id', '=', $table_name . '.id')
                ->where('tbldomains.registrar', $registrar)
                ->whereNull($table_name.'.id')
                ->select('tbldomains.*')
               	->get();

        // Stop when there are no domains
        if(empty($domains))
        	return;

        // We have domains to be synced.
        foreach($domains as $domain)
        {
        	// Insert the domain and set the lsast sync date to 0.
        	Capsule::table($table_name)->insert(
			    ['id' => $domain->id, 'last_sync' => '0000-00-00 00:00:00']
			);
        }
	}
	
	/**
	 * List all unprocessed domains for $registrar
	 *
	 * @return object all the domains
	 **/
	public static function get_unprocessed_domains($registrar, $limit = 0)
	{
		$table_name = DomainSyncScheme::get_table_name($registrar);

		try {
			$domains = Capsule::table('tbldomains')
					->leftJoin($table_name, 'tbldomains.id', '=', $table_name . '.id')
	                ->where('tbldomains.registrar', $registrar)
	                ->where($table_name.'.last_sync', '<', Carbon::now()->subHour(Registrar::get('updateInterval')))
	                ->select('tbldomains.*')
                    ->orderBy($table_name.'.last_sync', 'ASC');

	        if($limit != 0)
	        	$domains->take($limit);
	        
	        return $domains->get();
		} catch (\Exception $e) {
			return null;
		}
	}
} // END class DomainSync