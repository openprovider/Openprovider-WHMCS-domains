<?php
namespace OpenProvider\WhmcsHelpers;
use OpenProvider\WhmcsRegistrar\Models\Domain as DomainModel;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Models\Registrar;
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
            // Insert the domain and set the last sync date to 0.
            Capsule::table($table_name)->insert(
                ['id' => $domain->id, 'last_sync' => '0000-00-00 00:00:00']
            );
        }
    }

    /**
     * Remove doubles from the DomainSync table.
     *
     * @return void
     **/
    public static function remove_DomainSync_table_doubles($registrar)
    {
        $table_name = DomainSyncScheme::get_table_name($registrar);

        $domains = Capsule::table('tbldomains')
            ->leftJoin($table_name, 'tbldomains.id', '=', $table_name . '.id')
            ->where('tbldomains.registrar', $registrar)
            ->select('tbldomains.*')
            ->groupby('domain')
            ->havingRaw('COUNT(domain) > 1')
            ->get();

        // Stop when there are no domains
        if(empty($domains))
            return;

        // We have domains to be synced.
        foreach($domains as $domain)
        {
            // Fetch the oldest id.
            $domainModel = Capsule::table('tbldomains')
                ->where('domain', $domain->domain)
                ->orderBy('id', 'asc')
                ->first();

            // Update the status.
            Capsule::table('tbldomains')
                ->where('id', $domainModel->id)
                ->update(['status' => 'Expired']);


            // Remove from the sync table.
            Capsule::table($table_name)
                ->where('id', $domainModel->id)
                ->delete();

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
            $domains = DomainModel::leftJoin($table_name, 'tbldomains.id', '=', $table_name . '.id')
                ->where('tbldomains.registrar', $registrar)
                ->where($table_name.'.last_sync', '<', Carbon::now()->subHour(Configuration::getOrDefault('updateInterval', 2)))
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