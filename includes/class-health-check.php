<?php
/**
 * Health Check Class
 * 
 * System health monitoring and diagnostics
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Health_Check
 */
class TWork_Spin_Wheel_Health_Check
{
    /**
     * Database instance
     *
     * @var TWork_Spin_Wheel_Database
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = new TWork_Spin_Wheel_Database();
    }

    /**
     * Run full health check
     *
     * @return array Health check results.
     */
    public function run_health_check()
    {
        $results = array(
            'database' => $this->check_database(),
            'tables' => $this->check_tables(),
            'permissions' => $this->check_permissions(),
            'cache' => $this->check_cache(),
            'api' => $this->check_api(),
            'overall' => 'good',
        );

        // Determine overall status
        $has_errors = false;
        $has_warnings = false;

        foreach ($results as $key => $result) {
            if ($key === 'overall') {
                continue;
            }
            if (isset($result['status'])) {
                if ($result['status'] === 'error') {
                    $has_errors = true;
                } elseif ($result['status'] === 'warning') {
                    $has_warnings = true;
                }
            }
        }

        if ($has_errors) {
            $results['overall'] = 'error';
        } elseif ($has_warnings) {
            $results['overall'] = 'warning';
        }

        return $results;
    }

    /**
     * Check database connectivity
     *
     * @return array Check result.
     */
    private function check_database()
    {
        global $wpdb;

        $result = array(
            'status' => 'good',
            'message' => __('Database connection is healthy.', 'twork-spin-wheel'),
        );

        if (!$wpdb || !$wpdb->dbh) {
            $result['status'] = 'error';
            $result['message'] = __('Database connection failed.', 'twork-spin-wheel');
            return $result;
        }

        // Test query
        $test_query = $wpdb->query("SELECT 1");
        if ($test_query === false) {
            $result['status'] = 'error';
            $result['message'] = __('Database query test failed.', 'twork-spin-wheel');
        }

        return $result;
    }

    /**
     * Check database tables
     *
     * @return array Check result.
     */
    private function check_tables()
    {
        global $wpdb;

        $tables = array(
            $this->database->get_wheels_table(),
            $this->database->get_prizes_table(),
            $this->database->get_history_table(),
            $this->database->get_analytics_table(),
        );

        $missing_tables = array();
        $total_rows = 0;

        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($exists !== $table) {
                $missing_tables[] = $table;
            } else {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                $total_rows += absint($count);
            }
        }

        $result = array(
            'status' => empty($missing_tables) ? 'good' : 'error',
            'message' => empty($missing_tables) 
                ? sprintf(__('All tables exist. Total rows: %d', 'twork-spin-wheel'), $total_rows)
                : sprintf(__('Missing tables: %s', 'twork-spin-wheel'), implode(', ', $missing_tables)),
            'missing_tables' => $missing_tables,
            'total_rows' => $total_rows,
        );

        return $result;
    }

    /**
     * Check file permissions
     *
     * @return array Check result.
     */
    private function check_permissions()
    {
        $upload_dir = wp_upload_dir();
        $writable = wp_is_writable($upload_dir['basedir']);

        $result = array(
            'status' => $writable ? 'good' : 'warning',
            'message' => $writable 
                ? __('Upload directory is writable.', 'twork-spin-wheel')
                : __('Upload directory is not writable. Export functionality may be limited.', 'twork-spin-wheel'),
        );

        return $result;
    }

    /**
     * Check cache system
     *
     * @return array Check result.
     */
    private function check_cache()
    {
        $cache_enabled = get_option('twork_spin_wheel_enable_cache', true);

        if (!$cache_enabled) {
            return array(
                'status' => 'good',
                'message' => __('Caching is disabled (as configured).', 'twork-spin-wheel'),
            );
        }

        // Test cache
        $test_key = 'twork_health_check_' . time();
        $test_value = 'test';

        $set = TWork_Spin_Wheel_Cache::set($test_key, $test_value, 60);
        $get = TWork_Spin_Wheel_Cache::get($test_key);
        TWork_Spin_Wheel_Cache::delete($test_key);

        if ($set && $get === $test_value) {
            return array(
                'status' => 'good',
                'message' => __('Cache system is working correctly.', 'twork-spin-wheel'),
            );
        }

        return array(
            'status' => 'warning',
            'message' => __('Cache system may not be working correctly.', 'twork-spin-wheel'),
        );
    }

    /**
     * Check API endpoints
     *
     * @return array Check result.
     */
    private function check_api()
    {
        $rest_api = rest_url('twork/v1/spin-wheel/banner');
        $response = wp_remote_get($rest_api, array('timeout' => 5));

        if (is_wp_error($response)) {
            return array(
                'status' => 'warning',
                'message' => __('REST API endpoint test failed.', 'twork-spin-wheel'),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'status' => 'good',
                'message' => __('REST API endpoints are accessible.', 'twork-spin-wheel'),
            );
        }

        return array(
            'status' => 'warning',
            'message' => sprintf(__('REST API returned status code: %d', 'twork-spin-wheel'), $status_code),
        );
    }

    /**
     * Get system information
     *
     * @return array System info.
     */
    public function get_system_info()
    {
        global $wpdb;

        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => TWORK_SPIN_WHEEL_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'active_wheels' => $this->get_active_wheels_count(),
            'total_prizes' => $this->get_total_prizes_count(),
            'total_spins' => $this->get_total_spins_count(),
        );
    }

    /**
     * Get active wheels count
     *
     * @return int Count.
     */
    private function get_active_wheels_count()
    {
        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();
        return absint($wpdb->get_var("SELECT COUNT(*) FROM {$wheels_table} WHERE is_active = 1"));
    }

    /**
     * Get total prizes count
     *
     * @return int Count.
     */
    private function get_total_prizes_count()
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();
        return absint($wpdb->get_var("SELECT COUNT(*) FROM {$prizes_table}"));
    }

    /**
     * Get total spins count
     *
     * @return int Count.
     */
    private function get_total_spins_count()
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();
        return absint($wpdb->get_var("SELECT COUNT(*) FROM {$history_table}"));
    }
}

