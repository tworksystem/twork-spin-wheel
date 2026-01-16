<?php
/**
 * Uninstall script
 * 
 * Runs when the plugin is uninstalled
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user capabilities
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if we should delete data
$delete_data = get_option('twork_spin_wheel_delete_data_on_uninstall', false);

if ($delete_data) {
    global $wpdb;

    // Delete database tables
    $tables = array(
        $wpdb->prefix . 'twork_spin_wheels',
        $wpdb->prefix . 'twork_spin_wheel_prizes',
        $wpdb->prefix . 'twork_spin_wheel_history',
        $wpdb->prefix . 'twork_spin_wheel_analytics',
        $wpdb->prefix . 'twork_spin_wheel_logs',
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete options
    $options = array(
        'twork_spin_wheel_version',
        'twork_spin_wheel_db_version',
        'twork_spin_wheel_enable_cache',
        'twork_spin_wheel_enable_logging',
        'twork_spin_wheel_enable_rate_limiting',
        'twork_spin_wheel_notify_on_spin',
        'twork_spin_wheel_log_retention_days',
        'twork_spin_wheel_delete_data_on_uninstall',
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('twork_spin_wheel_cleanup_logs');
    wp_clear_scheduled_hook('twork_spin_wheel_update_analytics');

    // Clear cache
    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group('twork_spin_wheel');
    }
}

