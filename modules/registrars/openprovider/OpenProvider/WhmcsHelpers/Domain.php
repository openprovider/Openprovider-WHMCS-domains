<?php
namespace OpenProvider\WhmcsHelpers;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
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

	//Get WHMCS domain ID
	public static function getDomainId($domainName)
	{
		try {
			$domain = Capsule::table('tbldomains')
				->where('domain', $domainName)
				->first();

			if ($domain) {
				return $domain->id;
			}

			return null;			
		} catch (\Exception $e) {
			logModuleCall('WHMCS DB', 'get_domain_id', '{domain: $domainName}', $e->getMessage(), $domainName);

			return null; 
		}
	}

	//Store OP domain ID
	public static function storeDomainId($whmcsId, $opId, $domainName)
    {
        if (!DBHelper::checkTableExist(DatabaseTable::OPDomains)) {
            try {
                Capsule::schema()
                    ->create(
                        DatabaseTable::OPDomains,
                        function ($table) {
                            $table->increments('id');
                            $table->bigInteger('opid');
                            $table->bigInteger('whmcsid');
                            $table->string('domain');
                            $table->timestamps();
                        }
                    );
            } catch (\Exception $e) {}
        }

        $domain = Capsule::table(DatabaseTable::OPDomains)
                    ->where('domain', $domainName)
                    ->first();

        if ($domain == null) {
            try {
                Capsule::table(DatabaseTable::OPDomains)
                    ->updateOrInsert( 
                        ['opid' => $opId],
                        ['whmcsid' => $whmcsId, 'domain' => $domainName, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]                 
                    );
				return;
            } catch (\Exception $e) {}
        }

		if($domain->opid != $opId) {
			try {
				Capsule::table(DatabaseTable::OPDomains)
					->where('domain', $domainName)
					->update(
						['opid' => $opId, 'updated_at' => Carbon::now(), 'whmcsid' => $whmcsId]
					);
				return;
			} catch (\Exception $e) {}
		}        
    }

	//Get OP domain ID
	public static function getOpenproviderId($domainName)
	{
		try {
			$domain = Capsule::table(DatabaseTable::OPDomains)
				->where('domain', $domainName)
				->first();

			if ($domain) {
				return $domain->opid;
			}

			return null;			
		} catch (\Exception $e) {
			logModuleCall('WHMCS DB', 'get_domain_op_id', '{domain: $domainName}', $e->getMessage(), $domainName);
			return null; 
		}
	}

} // END class Domain
