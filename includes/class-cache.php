<?php
/**
 * Cache Management Class
 * 
 * Handles caching for improved performance
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Cache
 */
class TWork_Spin_Wheel_Cache
{
    /**
     * Cache group name
     */
    const CACHE_GROUP = 'twork_spin_wheel';

    /**
     * Cache expiration time (in seconds)
     */
    const CACHE_EXPIRATION = 3600; // 1 hour

    /**
     * Get cached value
     *
     * @param string $key Cache key.
     * @return mixed|false Cached value or false if not found.
     */
    public static function get($key)
    {
        $enabled = get_option('twork_spin_wheel_enable_cache', true);
        if (!$enabled) {
            return false;
        }

        return wp_cache_get($key, self::CACHE_GROUP);
    }

    /**
     * Set cache value
     *
     * @param string $key Cache key.
     * @param mixed $value Value to cache.
     * @param int $expiration Expiration time in seconds.
     * @return bool True on success, false on failure.
     */
    public static function set($key, $value, $expiration = null)
    {
        $enabled = get_option('twork_spin_wheel_enable_cache', true);
        if (!$enabled) {
            return false;
        }

        if ($expiration === null) {
            $expiration = self::CACHE_EXPIRATION;
        }

        return wp_cache_set($key, $value, self::CACHE_GROUP, $expiration);
    }

    /**
     * Delete cached value
     *
     * @param string $key Cache key.
     * @return bool True on success, false on failure.
     */
    public static function delete($key)
    {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Clear all cache
     *
     * @return bool True on success, false on failure.
     */
    public static function clear_all()
    {
        // Clear specific cache keys
        $keys = array(
            'wheel_config',
            'prizes_list',
            'user_spins_left',
            'statistics',
        );

        foreach ($keys as $key) {
            self::delete($key);
        }

        // Flush object cache if available
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::CACHE_GROUP);
        }

        return true;
    }

    /**
     * Get wheel configuration with cache
     *
     * @param int $wheel_id Wheel ID.
     * @return object|false Wheel configuration or false.
     */
    public static function get_wheel_config($wheel_id = 0)
    {
        $cache_key = 'wheel_config_' . ($wheel_id ?: 'default');

        $config = self::get($cache_key);
        if ($config !== false) {
            return $config;
        }

        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $wheels_table = $database->get_wheels_table();

        $where = $wheel_id > 0
            ? $wpdb->prepare('WHERE id = %d AND is_active = 1', $wheel_id)
            : 'WHERE is_active = 1 AND is_default = 1';

        $config = $wpdb->get_row("SELECT * FROM {$wheels_table} {$where} ORDER BY id DESC LIMIT 1");

        if ($config) {
            self::set($cache_key, $config, 1800); // Cache for 30 minutes
        }

        return $config;
    }

    /**
     * Invalidate wheel config cache
     *
     * @param int $wheel_id Wheel ID.
     * @return void
     */
    public static function invalidate_wheel_config($wheel_id = 0)
    {
        self::delete('wheel_config_' . ($wheel_id ?: 'default'));
        self::delete('wheel_config_0'); // Also clear default
    }

    /**
     * Get user spins left with cache
     *
     * @param int $user_id User ID.
     * @param int $wheel_id Wheel ID.
     * @return int Number of spins left.
     */
    public static function get_user_spins_left($user_id, $wheel_id = 0)
    {
        $cache_key = 'user_spins_left_' . $user_id . '_' . $wheel_id;

        $spins_left = self::get($cache_key);
        if ($spins_left !== false) {
            return $spins_left;
        }

        $spins_left = TWork_Spin_Wheel_Helpers::get_user_spins_left_today($user_id, $wheel_id);

        // Cache for 5 minutes (short cache for user-specific data)
        self::set($cache_key, $spins_left, 300);

        return $spins_left;
    }

    /**
     * Invalidate user spins cache
     *
     * @param int $user_id User ID.
     * @param int $wheel_id Wheel ID.
     * @return void
     */
    public static function invalidate_user_spins($user_id, $wheel_id = 0)
    {
        self::delete('user_spins_left_' . $user_id . '_' . $wheel_id);
        self::delete('user_spins_left_' . $user_id . '_0'); // Also clear default
    }
}

