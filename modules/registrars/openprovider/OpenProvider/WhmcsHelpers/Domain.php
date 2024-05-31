<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule,
	OpenProvider\WhmcsHelpers\Schemes\DomainSyncScheme,
	Carbon\Carbon;

/**
 * Helper for domain data.
 * WhmcsHelper
 *
 * @copyright Copyright (c) WeDevelop.coffee 2018
 */
class Domain
{
	/**
	 * Update the domain data
	 *
	 * @param  integer $domain_id The domain id
	 * @param  array $update_domain_data The updated domain data
	 * @param  array $registrar The registrar
	 * @return void
	 **/
	public static function save($domain_id, $update_domain_data, $registrar)
	{
		$table_name = DomainSyncScheme::get_table_name($registrar);
		// Update sync date
		try {
		    $updatedUserCount = Capsule::table($table_name)
		        ->where('id', $domain_id)
		        ->update(
		            [ 'last_sync' => Carbon::now() ]
		        );

		} catch (\Exception $e) {
		    // Some kind of error occured, let's log the data.
			logModuleCall($registrar, 'update_domain_data', null, $e->getMessage(), $update_domain_data);
		}

		if(empty($update_domain_data))
			return;

		try {
		    $updatedUserCount = Capsule::table('tbldomains')
		        ->where('id', $domain_id)
		        ->update(
		            $update_domain_data
		        );

		} catch (\Exception $e) {
		    // Some kind of error occured, let's log the data.
			logModuleCall($registrar, 'update_domain_data', null, $e->getMessage(), $update_domain_data);
		}
	}

} // END class Domain