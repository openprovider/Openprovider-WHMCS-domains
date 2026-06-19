<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

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
            $decoded = json_decode($row->data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // Corrupted or invalid JSON in cache: remove entry and treat as cache miss
            Capsule::table(DatabaseTable::ModOpenProviderCache)
                ->where('cache_key', $key)
                ->where('mode', $mode)
                ->delete();
        }

        // Cache miss or expired
        $value = $callback();

        $encodedValue = json_encode($value);
        // If encoding fails, do not cache the value to avoid corrupt entries.
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }
        Capsule::table(DatabaseTable::ModOpenProviderCache)->updateOrInsert(
            [
                'cache_key' => $key,
                'mode'      => $mode,
            ],
            [
                'data'       => $encodedValue,
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

        // WHMCS modules do not support deploy-time migrations.
        // Table creation is intentionally handled at runtime to ensure
        // the cache table exists even if the module is enabled without
        // an installation/activation hook being executed.
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
