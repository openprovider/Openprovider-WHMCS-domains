<?php

namespace OpenProvider\WhmcsRegistrar\Helpers;

use WHMCS\Database\Capsule;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

class DbCacheHelper
{
    /**
     * Remember a value in cache with TTL.
     *
     * @param string   $key
     * @param string   $mode   'test' or 'live'
     * @param int      $ttl    Time to live in seconds
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, string $mode, int $ttl, callable $callback)
    {
        self::ensureTableExists();

        $now = time();

        $row = Capsule::table(DatabaseTable::ModOpenProviderCache)
            ->where('cache_key', $key)
            ->where('mode', $mode)
            ->first();

        // Cache hit and not expired
        if ($row && (int) $row->expires_at > $now) {
            return json_decode($row->data, true);
        }

        //Cache miss or expired
        $value = $callback();

        Capsule::table(DatabaseTable::ModOpenProviderCache)->updateOrInsert(
            [
                'cache_key' => $key,
                'mode'      => $mode,
            ],
            [
                'data'       => json_encode($value),
                'expires_at' => $now + $ttl,
            ]
        );

        return $value;
    }

    /**
     * Forget a cache key.
     */
    public static function forget(string $key, string $mode): void
    {
        self::ensureTableExists();

        Capsule::table(DatabaseTable::ModOpenProviderCache)
            ->where('cache_key', $key)
            ->where('mode', $mode)
            ->delete();
    }

    /**
     * Cleanup expired cache entries.
     */
    public static function cleanup(): void
    {
        self::ensureTableExists();

        Capsule::table(DatabaseTable::ModOpenProviderCache)
            ->where('expires_at', '<', time())
            ->delete();
    }

    /**
     * Ensure cache table exists.
     */
    private static function ensureTableExists(): void
    {
        if (Capsule::schema()->hasTable(DatabaseTable::ModOpenProviderCache)) {
            return;
        }

        Capsule::schema()->create(DatabaseTable::ModOpenProviderCache, function ($table) {
            $table->string('cache_key', 255);
            $table->string('mode', 10);
            $table->mediumText('data');
            $table->unsignedInteger('expires_at');

            $table->primary(['cache_key', 'mode']);
            $table->index('expires_at');
        });
    }
}
