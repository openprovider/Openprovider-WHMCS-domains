<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

/**
 * Caches data for one request.
 */
class Cache
{
    /**
     * @var array Cached items
     */
    protected static $cache;

    /**
     * Set a cached item.
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function set($key, $value)
    {
        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Check if a cached item exitss.
     * @param $key
     * @return mixed
     */
    public static function has($key)
    {
        return isset(self::$cache[$key]);
    }

    /**
     * Get the cached item.
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::$cache[$key];
    }

    /**
     * Get all cached items.
     * @return array
     */
    public static function all()
    {
        return self::$cache;
    }

    /**
     * Removed the cached item.
     * @param $key
     */
    public static function delete($key)
    {
        unset(self::$cache[$key]);
        return;
    }
}
